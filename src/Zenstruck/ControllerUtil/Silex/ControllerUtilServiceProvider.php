<?php

namespace Zenstruck\ControllerUtil\Silex;

use JMS\Serializer\SerializerInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
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
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $app['dispatcher'];

        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = isset($app['url_generator']) && $app['url_generator'] instanceof UrlGeneratorInterface
            ? $app['url_generator']
            : null
        ;

        /** @var Session $session */
        $session = isset($app['session']) && $app['session'] instanceof Session
            ? $app['session']
            : null
        ;

        /** @var \Twig_Environment $twig */
        $twig = isset($app['twig']) && $app['twig'] instanceof \Twig_Environment
            ? $app['twig']
            : null
        ;

        $serializer = isset($app['serializer']) && $app['serializer'] instanceof SerializerInterface
            ? $app['serializer']
            : null
        ;

        $eventDispatcher->addListener(
            KernelEvents::VIEW,
            array(new ForwardListener(), 'onKernelView')
        );

        if ($urlGenerator) {
            $eventDispatcher->addListener(
                KernelEvents::VIEW,
                array(new RedirectListener($urlGenerator), 'onKernelView')
            );
        }

        if ($session) {
            $eventDispatcher->addListener(
                KernelEvents::VIEW,
                array(new HasFlashesListener($session->getFlashBag()), 'onKernelView'),
                10 // before other events
            );
        }

        if ($twig) {
            $eventDispatcher->addListener(
                KernelEvents::VIEW,
                array(new TwigViewListener($twig), 'onKernelView')
            );
        }

        if ($serializer) {
            $eventDispatcher->addListener(
                KernelEvents::VIEW,
                array(new SerializerViewListener($serializer), 'onKernelView'),
                5
            );
        }
    }
}
