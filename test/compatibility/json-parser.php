<?php
/**
 * Dependency-free JSON response regressions. Run with:
 * php test/compatibility/json-parser.php
 */

use DTS\eBaySDK\Parser\JsonParser;
use DTS\eBaySDK\Taxonomy\Types as Taxonomy;

spl_autoload_register(function ($class) {
    $prefix = 'DTS\\eBaySDK\\';
    if (strpos($class, $prefix) === 0) {
        $path = dirname(dirname(__DIR__)).'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

function jsonParserCheckSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message.'; expected '.var_export($expected, true)
            .', got '.var_export($actual, true));
    }
}

$previousReporting = error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require dirname(dirname(__DIR__)).'/src/functions.php';

    foreach (['', " \t\r\n", null, 'null', ' null ', '{}', '[]'] as $json) {
        $response = new Taxonomy\GetItemAspectsForCategoryRestResponse([
            'aspects' => [['localizedAspectName' => 'Existing']]
        ], 204, ['X-Request-ID' => 'example']);
        JsonParser::parseAndAssignProperties($response, $json);
        jsonParserCheckSame('Existing', $response->aspects[0]->localizedAspectName, 'Empty/null input preserves properties');
        jsonParserCheckSame(204, $response->getStatusCode(), 'Empty/null input preserves HTTP status');
        jsonParserCheckSame('example', $response->getHeaderLine('x-request-id'), 'Empty/null input preserves headers');
    }
    echo "PASS: empty, whitespace-only, null and empty-container JSON responses\n";

    foreach (['{', '<html>Bad gateway</html>', "{\"value\":\"\xB1\"}", str_repeat('[', 513).str_repeat(']', 513)] as $json) {
        $response = new Taxonomy\GetItemAspectsForCategoryRestResponse();
        try {
            JsonParser::parseAndAssignProperties($response, $json);
            throw new RuntimeException('Malformed JSON must be rejected');
        } catch (UnexpectedValueException $exception) {
            jsonParserCheckSame(0, strpos($exception->getMessage(), 'Unable to parse JSON response: '), 'Useful JSON error message');
            if ($exception->getCode() === JSON_ERROR_NONE) {
                throw new RuntimeException('JSON exception must retain the decoder error code');
            }
        }
        // Empty bodies must not reuse a previous json_decode() failure.
        JsonParser::parseAndAssignProperties($response, null);
        JsonParser::parseAndAssignProperties($response, '');
        jsonParserCheckSame([], $response->toArray(), 'Invalid JSON must not assign partial data');
    }
    echo "PASS: malformed JSON, HTML, invalid UTF-8 and excessive nesting are rejected clearly\n";

    foreach (['true', 'false', '0', '1.5', '"text"', '""'] as $json) {
        try {
            JsonParser::parseAndAssignProperties(new Taxonomy\Aspect(), $json);
            throw new RuntimeException('Scalar JSON must be rejected');
        } catch (UnexpectedValueException $exception) {
            jsonParserCheckSame('Expected a JSON object or array in the response.', $exception->getMessage(), 'Useful scalar error message');
        }
    }
    echo "PASS: scalar JSON is rejected before property assignment\n";

    // A valid call after errors must not use the previous decoder error state.
    $response = new Taxonomy\GetItemAspectsForCategoryRestResponse();
    JsonParser::parseAndAssignProperties($response, file_get_contents(__DIR__.'/item-aspects.json'));
    jsonParserCheckSame('Brand', $response->aspects[0]->localizedAspectName, 'Valid nested response');
    jsonParserCheckSame('Acme', $response->aspects[0]->aspectValues[0]->localizedValue, 'Nested repeatable SDK types');
    jsonParserCheckSame(123, $response->warnings[0]->errorId, 'Inherited response fields');

    $aspect = new Taxonomy\Aspect(['localizedAspectName' => 'Existing']);
    JsonParser::parseAndAssignProperties($aspect, '{"localizedAspectName":null,"unknown":123,"aspectConstraint":{"aspectRequired":false,"aspectMaxLength":0},"aspectValues":[null,{"localizedValue":"Acme"}]}');
    jsonParserCheckSame('Existing', $aspect->localizedAspectName, 'Null properties remain ignored');
    jsonParserCheckSame(false, $aspect->aspectConstraint->aspectRequired, 'False values remain valid');
    jsonParserCheckSame(0, $aspect->aspectConstraint->aspectMaxLength, 'Zero values remain valid');
    jsonParserCheckSame(1, count($aspect->aspectValues), 'Null repeated entries remain ignored');
    jsonParserCheckSame('Acme', $aspect->aspectValues[0]->localizedValue, 'Remaining repeated value');
    echo "PASS: valid nested parsing, unknown/null fields, false and zero values\n";
} finally {
    restore_error_handler();
    error_reporting($previousReporting);
}
