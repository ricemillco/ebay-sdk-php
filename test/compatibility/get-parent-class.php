<?php
/**
 * Dependency-free regressions for constructor parent-class metadata.
 * Run with: php test/compatibility/get-parent-class.php
 *
 * The PHPUnit 4 development dependency predates PHP 8.3. Keep this runner
 * usable on current PHP without changing the package's dependency constraints.
 */

use DTS\eBaySDK\Parser\JsonParser;
use DTS\eBaySDK\Taxonomy\Types as Taxonomy;
use DTS\eBaySDK\Trading\Types as Trading;
use DTS\eBaySDK\Types\BaseType;

spl_autoload_register(function ($class) {
    $prefix = 'DTS\\eBaySDK\\';
    if (strpos($class, $prefix) === 0) {
        $path = dirname(dirname(__DIR__)).'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

function checkSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message.'; expected '.var_export($expected, true)
            .', got '.var_export($actual, true));
    }
}

function clearPropertyMetadata()
{
    $property = new ReflectionProperty('DTS\\eBaySDK\\Types\\BaseType', 'properties');
    $property->setAccessible(true);
    $property->setValue(null, []);
    return $property;
}

$previousReporting = error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    // Include E_DEPRECATED and E_USER_DEPRECATED, even if the caller used @.
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require dirname(dirname(__DIR__)).'/src/functions.php';

    // Initialize the most derived class first. Parent constructors must use their
    // own lexical parent, not get_parent_class($this) or late static binding.
    $metadata = clearPropertyMetadata();
    $response = new Taxonomy\GetItemAspectsForCategoryRestResponse([
        'aspects' => [['localizedAspectName' => 'Brand']],
        'warnings' => [['message' => 'Example warning']]
    ], 207, ['X-EBAY-C-REQUEST-ID' => 'request-123']);
    $cache = $metadata->getValue();
    $base = 'DTS\\eBaySDK\\Types\\BaseType';
    $parent = 'DTS\\eBaySDK\\Taxonomy\\Types\\AspectMetadata';
    $child = 'DTS\\eBaySDK\\Taxonomy\\Types\\GetItemAspectsForCategoryRestResponse';
    checkSame([], $cache[$base], 'Base metadata must stay empty');
    checkSame(['aspects'], array_keys($cache[$parent]), 'Parent metadata must not contain child fields');
    checkSame(['aspects', 'errors', 'warnings'], array_keys($cache[$child]), 'Child must inherit parent fields');
    checkSame('DTS\\eBaySDK\\Taxonomy\\Types\\Aspect', $response->elementMeta('aspects')->phpType, 'Inherited element type');
    checkSame(true, $response->elementMeta('aspects')->repeatable, 'Inherited element cardinality');
    checkSame('Brand', $response->aspects[0]->localizedAspectName, 'Constructor must assign inherited fields');
    checkSame('Example warning', $response->warnings[0]->message, 'Constructor must assign child fields');
    checkSame(207, $response->getStatusCode(), 'Response status');
    checkSame('request-123', $response->getHeaderLine('x-ebay-c-request-id'), 'Response headers');

    // Exercise another service and the warm-cache path as well as cold caches.
    $request = new Trading\GetItemRequestType(['ItemID' => '123', 'MessageID' => 'message-1']);
    checkSame('123', $request->ItemID, 'Trading child property');
    checkSame('message-1', $request->MessageID, 'Trading inherited property');
    checkSame('string', $request->elementMeta('MessageID')->phpType, 'Trading inherited metadata');
    $again = new Trading\GetItemRequestType(['ItemID' => '456', 'MessageID' => 'message-2']);
    checkSame('message-2', $again->MessageID, 'Cached metadata must support another instance');
    checkSame('message-1', $request->MessageID, 'Instance values must remain independent');
    echo "PASS: inherited metadata and constructor values (cold and warm caches)\n";

    clearPropertyMetadata();
    $request = new Taxonomy\GetItemAspectsForCategoryRestRequest([
        'category_id' => '9355', 'category_tree_id' => '0'
    ]);
    checkSame('9355', $request->category_id, 'Taxonomy category request');
    checkSame('0', $request->category_tree_id, 'Taxonomy tree request');
    $response = new Taxonomy\GetItemAspectsForCategoryRestResponse();
    JsonParser::parseAndAssignProperties($response, file_get_contents(__DIR__.'/item-aspects.json'));
    checkSame(1, count($response->aspects), 'Aspect count');
    $aspect = $response->aspects[0];
    checkSame(Taxonomy\Aspect::class, get_class($aspect), 'Aspect type');
    checkSame('Brand', $aspect->localizedAspectName, 'Aspect name');
    checkSame(Taxonomy\AspectConstraint::class, get_class($aspect->aspectConstraint), 'Constraint type');
    checkSame(true, $aspect->aspectConstraint->aspectRequired, 'Required constraint');
    checkSame('SINGLE', $aspect->aspectConstraint->itemToAspectCardinality, 'Constraint cardinality');
    checkSame(Taxonomy\AspectValue::class, get_class($aspect->aspectValues[0]), 'Aspect value type');
    checkSame('Acme', $aspect->aspectValues[0]->localizedValue, 'Aspect value');
    checkSame('Model', $aspect->aspectValues[0]->valueConstraints[0]->applicableForLocalizedAspectName, 'Nested constraint');
    checkSame('Example', $aspect->aspectValues[0]->valueConstraints[0]->applicableForLocalizedAspectValues[0], 'Nested repeatable value');
    checkSame(123, $response->warnings[0]->errorId, 'Response child warning field');
    checkSame(0, count($response->errors), 'Empty errors');
    echo "PASS: Taxonomy item-aspects JSON parsing and request construction\n";

    // Construct every SDK type with a fresh cache so a previously initialized
    // parent cannot conceal a regression. Check the entire inherited metadata
    // against reflection, independently of the constructor's merge logic.
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(dirname(__DIR__)).'/src'));
    $constructed = 0;
    foreach ($files as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $source = file_get_contents($file->getPathname());
        if (!preg_match('/namespace\s+([^;]+);/', $source, $namespace)
            || !preg_match('/^class\s+(\w+)\s+extends\s+/m', $source, $name)) {
            continue;
        }
        $class = $namespace[1].'\\'.$name[1];
        // Only SDK type constructors are relevant; service classes need config.
        if (strpos($class, '\\Types\\') === false || !is_subclass_of($class, BaseType::class)) {
            continue;
        }
        $metadata = clearPropertyMetadata();
        $object = new $class();
        $expected = [];
        $lineage = [];
        for ($reflection = new ReflectionClass($class); $reflection; $reflection = $reflection->getParentClass()) {
            array_unshift($lineage, $reflection);
        }
        foreach ($lineage as $reflection) {
            $defaults = $reflection->getDefaultProperties();
            if (isset($defaults['propertyTypes'])) {
                $expected = array_merge($expected, $defaults['propertyTypes']);
            }
        }
        $cache = $metadata->getValue();
        checkSame($expected, $cache[$class], $class.' inherited metadata');
        ++$constructed;
    }
    if ($constructed < 2700) {
        throw new RuntimeException('Expected to exercise the whole SDK; only found '.$constructed.' types');
    }
    echo 'PASS: constructed '.$constructed." SDK types with fresh metadata caches\n";
} finally {
    restore_error_handler();
    error_reporting($previousReporting);
}
