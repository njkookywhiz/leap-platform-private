<?php

namespace Leap\PanelBundle\EventSubscriber;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseSubscriber
{
    const CACHEABLE_ACTIONS = [
        "Leap\PanelBundle\Controller\ViewTemplateController::contentAction",
        "Leap\PanelBundle\Controller\ViewTemplateController::htmlAction",
        "Leap\PanelBundle\Controller\ViewTemplateController::cssAction",
        "Leap\PanelBundle\Controller\ViewTemplateController::jsAction"
    ];
    const CACHE_MAX_AGE = 60 * 60 * 24;

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $controller = $event->getRequest()->attributes->get('_controller');
        if (in_array($controller, self::CACHEABLE_ACTIONS)) {
            $response->headers->addCacheControlDirective('max-age', self::CACHE_MAX_AGE);
        }

        $event->setResponse($response);
    }

}