<?php
/**
 * Regressions for the PHP 8.4 fixes outside the type constructors and JSON parser.
 * Run with: php test/compatibility/runtime.php
 *
 * The HTTP checks need Guzzle, so they only run when vendor/autoload.php exists.
 */

use DTS\eBaySDK\Exceptions\InvalidPropertyTypeException;
use DTS\eBaySDK\Exceptions\UnknownPropertyException;
use DTS\eBaySDK\HttpHandler;
use DTS\eBaySDK\JmesPath\AstRuntime;
use DTS\eBaySDK\JmesPath\CompilerRuntime;
use DTS\eBaySDK\JmesPath\Parser;
use DTS\eBaySDK\JmesPath\TreeInterpreter;
use DTS\eBaySDK\OAuth\Services\OAuthService;
use DTS\eBaySDK\Parser\XmlParser;
use DTS\eBaySDK\Taxonomy\Services\TaxonomyService;
use DTS\eBaySDK\Taxonomy\Types as Taxonomy;
use DTS\eBaySDK\Trading\Types\GetOrdersResponseType;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

$root = dirname(dirname(__DIR__));
$hasDependencies = is_file($root.'/vendor/autoload.php');

spl_autoload_register(function ($class) use ($root) {
    $prefix = 'DTS\\eBaySDK\\';
    if (strpos($class, $prefix) === 0) {
        $path = $root.'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

function runtimeCheckSame($expected, $actual, $message)
{
    if ($expected !== $actual) {
        throw new RuntimeException($message.'; expected '.var_export($expected, true)
            .', got '.var_export($actual, true));
    }
}

function runtimeOAuthService($handler = null)
{
    return new OAuthService([
        'credentials' => ['appId' => 'test-app', 'certId' => 'test-cert', 'devId' => 'test-dev'],
        'ruName' => 'test-redirect',
        'httpHandler' => $handler ?: function () {
            throw new RuntimeException('No HTTP request expected');
        },
    ]);
}

$previousReporting = error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    // Dependencies report their own deprecations with @trigger_error, which the SDK cannot fix here.
    if (!(error_reporting() & $severity)) {
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    if ($hasDependencies) {
        require $root.'/vendor/autoload.php';
    } else {
        require $root.'/src/functions.php';
    }

    // Implicitly nullable parameters are reported when a file is compiled.
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src'));
    $loaded = 0;
    foreach ($files as $file) {
        $path = str_replace('\\', '/', $file->getPathname());
        if (!$file->isFile() || $file->getExtension() !== 'php'
            || strpos($path, '/Types/') !== false || basename($path) === 'functions.php') {
            continue;
        }
        require_once $path;
        ++$loaded;
    }
    if ($loaded < 100) {
        throw new RuntimeException('Expected to load the SDK classes; only found '.$loaded);
    }
    echo 'PASS: loaded '.$loaded." SDK source files without diagnostics\n";

    $parser = new XmlParser(GetOrdersResponseType::class);
    $response = $parser->parse('<GetOrdersResponse xmlns="urn:ebay:apis:eBLBaseComponents"><Ack>Success</Ack><Unknown><Nested>ignored</Nested></Unknown><Version>1325</Version><OrderArray><Order><OrderID>1 &amp; 2</OrderID></Order><Order><OrderID>456</OrderID></Order></OrderArray></GetOrdersResponse>');
    runtimeCheckSame('Success', $response->Ack, 'XML inherited property');
    runtimeCheckSame('1325', $response->Version, 'XML element after an unknown element');
    runtimeCheckSame(2, count($response->OrderArray->Order), 'XML repeated elements');
    runtimeCheckSame('1 & 2', $response->OrderArray->Order[0]->OrderID, 'XML entities');
    runtimeCheckSame('456', $response->OrderArray->Order[1]->OrderID, 'XML second repeated element');
    runtimeCheckSame(null, (new XmlParser(GetOrdersResponseType::class))->parse('<html>Bad gateway'), 'Malformed XML has no root object');
    echo "PASS: XML handlers parse namespaces, entities, unknown and repeated elements\n";

    $runtime = new AstRuntime(null, null);
    $data = ['items' => [['sku' => 'SKU-001'], ['sku' => 'SKU-002']]];
    runtimeCheckSame(['SKU-001', 'SKU-002'], $runtime('items[*].sku', $data), 'JMESPath array wildcard');
    runtimeCheckSame(['SKU-001'], $runtime('items.*.sku', ['items' => ['first' => ['sku' => 'SKU-001']]]), 'JMESPath object wildcard');
    runtimeCheckSame('SKU-001', (new TreeInterpreter(null))->visit((new Parser(null))->parse('items[0].sku'), $data), 'JMESPath interpreter');
    new CompilerRuntime(null, null);
    echo "PASS: JMESPath accepts null dependencies and parses wildcards\n";

    $previous = new RuntimeException('Previous error');
    runtimeCheckSame(null, (new UnknownPropertyException('missing', 0, null))->getPrevious(), 'Null previous exception');
    runtimeCheckSame($previous, (new InvalidPropertyTypeException('value', 'string', 'integer', 0, $previous))->getPrevious(), 'Chained previous exception');
    echo "PASS: exceptions accept null and chained previous exceptions\n";

    $url = runtimeOAuthService()->redirectUrlForUser(['state' => 'a b', 'scope' => ['one', 'two']]);
    runtimeCheckSame('https://auth.ebay.com/oauth2/authorize?client_id=test-app&redirect_uri=test-redirect&response_type=code&state=a%20b&scope=one%20two', $url, 'OAuth redirect URL');
    echo "PASS: OAuth redirect URL\n";

    if (!$hasDependencies) {
        echo "SKIP: HTTP checks need vendor/autoload.php\n";
        return;
    }

    runtimeCheckSame(true, new HttpHandler() instanceof HttpHandler && new HttpHandler(null) instanceof HttpHandler, 'HTTP handler default client');

    $requests = [];
    $respondWith = function ($status, $body) use (&$requests) {
        return function (RequestInterface $request, array $options) use (&$requests, $status, $body) {
            $requests[] = $request;
            return new FulfilledPromise(new Response($status, ['X-Request-ID' => 'example'], $body));
        };
    };

    $token = '{"access_token":"test-token","token_type":"Application Access Token","expires_in":7200}';
    foreach (['getAppToken', 'getAppTokenAsync'] as $method) {
        foreach ([[], [null]] as $arguments) {
            $requests = [];
            $response = call_user_func_array([runtimeOAuthService($respondWith(200, $token)), $method], $arguments);
            $response = $method === 'getAppTokenAsync' ? $response->wait() : $response;
            runtimeCheckSame('test-token', $response->access_token, 'OAuth token '.$method);
            parse_str((string) $requests[0]->getBody(), $body);
            runtimeCheckSame('client_credentials', $body['grant_type'], 'OAuth grant type '.$method);
        }
    }
    echo "PASS: OAuth app tokens with default and null requests, sync and async\n";

    // OAuth builds responses through their constructor and REST through JsonParser,
    // but both must treat invalid response bodies the same way.
    $callers = [
        'OAuth' => function ($status, $body) use ($respondWith) {
            return runtimeOAuthService($respondWith($status, $body))->getAppToken();
        },
        'REST' => function ($status, $body) use ($respondWith) {
            $service = new TaxonomyService(['authorization' => 'test', 'httpHandler' => $respondWith($status, $body)]);
            return $service->getItemAspectsForCategory(new Taxonomy\GetItemAspectsForCategoryRestRequest([
                'category_id' => '9355', 'category_tree_id' => '0'
            ]));
        },
    ];
    foreach ($callers as $name => $call) {
        foreach (['', " \r\n", 'null', '{}', '[]'] as $body) {
            $response = $call(502, $body);
            runtimeCheckSame(502, $response->getStatusCode(), $name.' empty body status');
            runtimeCheckSame('example', $response->getHeaderLine('x-request-id'), $name.' empty body headers');
            runtimeCheckSame([], $response->toArray(), $name.' empty body properties');
        }
        $invalid = [
            '<html>Bad gateway</html>' => 'Unable to parse JSON response: ',
            '{' => 'Unable to parse JSON response: ',
            'true' => 'Expected a JSON object or array in the response.',
            '"text"' => 'Expected a JSON object or array in the response.',
        ];
        foreach ($invalid as $body => $message) {
            try {
                $call(502, (string) $body);
                throw new RuntimeException($name.' must reject '.$body);
            } catch (UnexpectedValueException $exception) {
                runtimeCheckSame(0, strpos($exception->getMessage(), $message), $name.' error message for '.$body);
            }
        }
    }
    echo "PASS: OAuth and REST handle empty and invalid response bodies consistently\n";
} finally {
    restore_error_handler();
    error_reporting($previousReporting);
}
