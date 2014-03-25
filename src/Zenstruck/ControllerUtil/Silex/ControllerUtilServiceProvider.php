<?php

namespace Zenstruck\ControllerUtil\Silex;

use JMS\Serializer\SerializerInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Zenstruck\ControllerUtil\EventListener\ForwardListener;
use Zenstruck\ControllerUtil\EventListener\HasFlashesListener;
use Zenstruck\ControllerUtil\EventListener\RedirectListener;
use Zenstruck\ControllerUtil\EventListener\SerializerViewListener;
use Zenstruck\ControllerUtil\EventListener\TwigViewListener;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class ControllerUtilServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        // noop
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $this->registerForwardListener($app);
        $this->registerRedirectListener($app);
        $this->registerHasFlashesListener($app);
        $this->registerTwigViewListener($app);
        $this->registerSerializerViewListener($app);
    }

    private function registerForwardListener(Application $app)
    {
        $app['dispatcher']->addListener(
            KernelEvents::VIEW,
            array(new ForwardListener(), 'onKernelView')
        );
    }

    private function registerRedirectListener(Application $app)
    {
        if (!isset($app['url_generator']) || !$app['url_generator'] instanceof UrlGeneratorInterface) {
            return;
        }

        $app['dispatcher']->addListener(
            KernelEvents::VIEW,
            array(new RedirectListener($app['url_generator']), 'onKernelView')
        );
    }

    private function registerHasFlashesListener(Application $app)
    {
        if (!isset($app['session']) || !$app['session'] instanceof Session) {
            return;
        }

        $app['dispatcher']->addListener(
            KernelEvents::VIEW,
            array(new HasFlashesListener($app['session']->getFlashBag()), 'onKernelView'),
            10 // before other events
        );
    }

    private function registerTwigViewListener(Application $app)
    {
        if (!isset($app['twig']) || !$app['twig'] instanceof \Twig_Environment) {
            return;
        }

        $app['dispatcher']->addListener(
            KernelEvents::VIEW,
            array(new TwigViewListener($app['twig']), 'onKernelView')
        );
    }

    private function registerSerializerViewListener(Application $app)
    {
        if (!isset($app['serializer']) || !$app['serializer'] instanceof SerializerInterface) {
            return;
        }

        $app['dispatcher']->addListener(
            KernelEvents::VIEW,
            array(new SerializerViewListener($app['serializer']), 'onKernelView'),
            5 // before other events
        );
    }
}
