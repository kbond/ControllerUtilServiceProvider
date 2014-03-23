<?php

namespace Zenstruck\ControllerUtil\Tests\Silex;

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

    public function testView()
    {
        $client = $this->createClient();
        $client->request('GET', '/view');

        $this->assertSame("This is a rendered view with data: foo\n", $client->getResponse()->getContent());
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

        $app->get('/view', function () {
                return new Template('view.html.twig', 'foo');
            }
        )->bind('view');

        return $app;
    }
}
