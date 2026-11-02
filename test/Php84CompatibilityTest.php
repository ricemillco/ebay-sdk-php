<?php
namespace DTS\eBaySDK\Test;

use DTS\eBaySDK\Exceptions\InvalidPropertyTypeException;
use DTS\eBaySDK\Exceptions\UnknownPropertyException;
use DTS\eBaySDK\HttpHandler;
use DTS\eBaySDK\JmesPath\AstRuntime;
use DTS\eBaySDK\JmesPath\CompilerRuntime;
use DTS\eBaySDK\JmesPath\Parser;
use DTS\eBaySDK\JmesPath\TreeInterpreter;
use DTS\eBaySDK\OAuth\Services\OAuthService;
use DTS\eBaySDK\Parser\XmlParser;
use DTS\eBaySDK\Trading\Types\GetOrdersResponseType;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

class Php84CompatibilityTest extends \PHPUnit\Framework\TestCase
{
    public function testHttpHandlerAcceptsDefaultAndNullClients(): void
    {
        $this->assertInstanceOf(HttpHandler::class, new HttpHandler());
        $this->assertInstanceOf(HttpHandler::class, new HttpHandler(null));
    }

    public function testExceptionsAcceptNullAndChainedPreviousExceptions(): void
    {
        $previous = new \RuntimeException('Previous error');

        $this->assertNull((new UnknownPropertyException('missing', 0, null))->getPrevious());
        $this->assertSame($previous, (new UnknownPropertyException('missing', 0, $previous))->getPrevious());
        $this->assertNull((new InvalidPropertyTypeException('value', 'string', 'integer', 0, null))->getPrevious());
        $this->assertSame($previous, (new InvalidPropertyTypeException('value', 'string', 'integer', 0, $previous))->getPrevious());
    }

    public function testJmesPathAcceptsNullDependenciesAndParsesWildcards(): void
    {
        $runtime = new AstRuntime(null, null);
        $parser = new Parser(null);
        $interpreter = new TreeInterpreter(null);
        $data = ['items' => [['sku' => 'SKU-001'], ['sku' => 'SKU-002']]];

        $this->assertSame(['SKU-001', 'SKU-002'], $runtime('items[*].sku', $data));
        $this->assertSame(['SKU-001', 'SKU-002'], $runtime('items.*.sku', ['items' => [
            'first' => ['sku' => 'SKU-001'],
            'second' => ['sku' => 'SKU-002'],
        ]]));
        $this->assertSame('SKU-001', $interpreter->visit($parser->parse('items[0].sku'), $data));
        $this->assertInstanceOf(CompilerRuntime::class, new CompilerRuntime(null, null));
    }

    public function testTradingXmlPreservesInheritedPropertiesAndRepeatedOrders(): void
    {
        $parser = new XmlParser(GetOrdersResponseType::class);
        $response = $parser->parse('<GetOrdersResponse xmlns="urn:ebay:apis:eBLBaseComponents"><Ack>Success</Ack><Version>1325</Version><OrderArray><Order><OrderID>123</OrderID></Order><Order><OrderID>456</OrderID></Order></OrderArray></GetOrdersResponse>');

        $this->assertSame('Success', $response->Ack);
        $this->assertSame('1325', $response->Version);
        $this->assertCount(2, $response->OrderArray->Order);
        $this->assertSame('123', $response->OrderArray->Order[0]->OrderID);
        $this->assertSame('456', $response->OrderArray->Order[1]->OrderID);
    }

    public function testOAuthRedirectPreservesQueryParameters(): void
    {
        $service = new OAuthService([
            'credentials' => ['appId' => 'test-app', 'certId' => 'test-cert', 'devId' => 'test-dev'],
            'ruName' => 'test-redirect',
        ]);

        $url = $service->redirectUrlForUser(['state' => 'test-state', 'scope' => ['scope-one', 'scope-two']]);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        $this->assertSame('test-app', $query['client_id']);
        $this->assertSame('test-redirect', $query['redirect_uri']);
        $this->assertSame('test-state', $query['state']);
        $this->assertSame('scope-one scope-two', $query['scope']);
    }

    /**
     * @dataProvider appTokenRequests
     */
    public function testOAuthAcceptsDefaultAndNullAppTokenRequests(string $method, bool $passNull): void
    {
        $requests = [];
        $service = new OAuthService([
            'credentials' => ['appId' => 'test-app', 'certId' => 'test-cert', 'devId' => 'test-dev'],
            'ruName' => 'test-redirect',
            'httpHandler' => static function (RequestInterface $request, array $options) use (&$requests): PromiseInterface {
                $requests[] = $request;

                return new FulfilledPromise(new Response(200, [], '{"access_token":"test-token","token_type":"Application Access Token","expires_in":7200}'));
            },
        ]);

        $response = $passNull ? $service->{$method}(null) : $service->{$method}();

        if ($response instanceof PromiseInterface) {
            $response = $response->wait();
        }

        $this->assertSame('test-token', $response->access_token);
        $this->assertCount(1, $requests);

        parse_str((string) $requests[0]->getBody(), $body);

        $this->assertSame('client_credentials', $body['grant_type']);
        $this->assertSame('test-redirect', $body['redirect_uri']);
        $this->assertSame('https://api.ebay.com/oauth/api_scope', $body['scope']);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function appTokenRequests(): array
    {
        return [
            'sync default' => ['getAppToken', false],
            'sync null' => ['getAppToken', true],
            'async default' => ['getAppTokenAsync', false],
            'async null' => ['getAppTokenAsync', true],
        ];
    }
}
