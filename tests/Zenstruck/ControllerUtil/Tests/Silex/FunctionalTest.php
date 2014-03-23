<?php

namespace Zenstruck\ControllerUtil\Tests\Silex;

use JMS\Serializer\SerializerBuilder;
use Silex\Application;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\ControllerUtil\FlashRedirect;
use Zenstruck\ControllerUtil\Forward;
use Zenstruck\ControllerUtil\Redirect;
use Zenstruck\ControllerUtil\Silex\ControllerUtilServiceProvider;
use Zenstruck\ControllerUtil\Template;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class FunctionalTest extends WebTestCase
{
    public function testForward()
    {
        $client = $this->createClient();
        $client->request('GET', '/forward');

        $this->assertSame('Forwarded.', $client->getResponse()->getContent());
    }

    public function testRedirect()
    {
        $client = $this->createClient();
        $client->followRedirects(false);
        $client->request('GET', '/redirect');
        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect('/redirect-endpoint'));
        $this->assertSame(302, $response->getStatusCode());

        $client->followRedirect();

        $this->assertSame('Redirected.', $client->getResponse()->getContent());
    }

    public function testFlashRedirect()
    {
        $client = $this->createClient();
        $client->request('GET', '/flash-redirect');
        $response = $client->getResponse();

        $this->assertTrue($response->isRedirect('/redirect-endpoint'));
        $this->assertSame(302, $response->getStatusCode());

        $client->followRedirect();

        $this->assertSame('Redirected with "info" flash: "This is a flash message."', $client->getResponse()->getContent());
    }

    /**
     * @dataProvider viewDataProvider
     */
    public function testView($uri, $expectedContent, $expectedContentType)
    {
        $client = $this->createClient();
        $client->request('GET', $uri);
        $response = $client->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedContent, $response->getContent());
        $this->assertSame($expectedContentType, $response->headers->get('content-type'));
    }

    public function viewDataProvider()
    {
        return array(
            array('/view', "This is a rendered view with data: foo\n", 'text/html; charset=UTF-8'),
            array('/view.json', '"foo"', 'application/json'),
            array('/view.xml', "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<result><![CDATA[foo]]></result>\n", 'text/xml; charset=UTF-8')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function createApplication()
    {
        $app = new Application();
        $app->register(new TwigServiceProvider(), array('twig.path' => __DIR__.'/Fixtures'));
        $app->register(new UrlGeneratorServiceProvider());
        $app->register(new SessionServiceProvider());
        $app->register(new ControllerUtilServiceProvider());
        $app['serializer'] = SerializerBuilder::create()->build();
        $app['debug'] = true;
        $app['session.test'] = true;
        $app['exception_handler']->disable();

        $app->get('/forward', function () {
                return new Forward(function () { return new Response('Forwarded.'); });
            }
        )->bind('forward');

        $app->get('/redirect', function () {
                return new Redirect('redirect_endpoint');
            }
        )->bind('redirect');

        $app->get('/flash-redirect', function () {
                return FlashRedirect::createSimple('redirect_endpoint', 'This is a flash message.');
            }
        )->bind('flash_redirect');

        $app->get('/redirect-endpoint', function () use ($app) {
                $flashBag = $app['session']->getFlashBag();

                if ($flashBag->has('info')) {
                    return new Response(sprintf('Redirected with "info" flash: "%s"', implode($flashBag->get('info'))));
                }

                return new Response('Redirected.');
            }
        )->bind('redirect_endpoint');

        $app->get('/view.{_format}', function () {
                return new Template('view.html.twig', 'foo');
            }
        )->bind('view')->assert('_format', 'html|json|xml')->value('_format', 'html');

        return $app;
    }
}
