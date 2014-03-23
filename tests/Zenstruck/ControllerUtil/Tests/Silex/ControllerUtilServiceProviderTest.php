<?php

namespace Zenstruck\ControllerUtil\Tests\Silex;

use JMS\Serializer\SerializerBuilder;
use Silex\Application;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\KernelEvents;
use Zenstruck\ControllerUtil\Silex\ControllerUtilServiceProvider;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class ControllerUtilServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultListeners()
    {
        $app = new Application();
        $app->register(new ControllerUtilServiceProvider());
        $app->boot();

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $app['dispatcher'];
        $viewListeners = $eventDispatcher->getListeners(KernelEvents::VIEW);

        $this->assertCount(2, $viewListeners);
        $this->assertInstanceOf('Zenstruck\ControllerUtil\EventListener\ForwardListener', $viewListeners[0][0]);
    }

    public function testAllListeners()
    {
        $app = new Application();
        $app->register(new TwigServiceProvider());
        $app->register(new UrlGeneratorServiceProvider());
        $app->register(new SessionServiceProvider());
        $app->register(new ControllerUtilServiceProvider());
        $app['serializer'] = SerializerBuilder::create()->build();
        $app['session.test'] = true;
        $app->boot();

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $app['dispatcher'];
        $viewListeners = $eventDispatcher->getListeners(KernelEvents::VIEW);

        $this->assertCount(6, $viewListeners);
        $this->assertInstanceOf('Zenstruck\ControllerUtil\EventListener\HasFlashesListener', $viewListeners[0][0]);
        $this->assertInstanceOf('Zenstruck\ControllerUtil\EventListener\SerializerViewListener', $viewListeners[1][0]);
        $this->assertInstanceOf('Zenstruck\ControllerUtil\EventListener\ForwardListener', $viewListeners[2][0]);
        $this->assertInstanceOf('Zenstruck\ControllerUtil\EventListener\RedirectListener', $viewListeners[3][0]);
        $this->assertInstanceOf('Zenstruck\ControllerUtil\EventListener\TwigViewListener', $viewListeners[4][0]);
    }
}
