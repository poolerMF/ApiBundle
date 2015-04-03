<?php

namespace Plr\Bundle\ApiBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebDebugToolbarListener implements EventSubscriberInterface {

    protected $twig;

    public function __construct(\Twig_Environment $twig) {
        $this->twig = $twig;
    }

    public function onKernelResponse(FilterResponseEvent $event) {
        $response = $event->getResponse();
        $request = $event->getRequest();

        if($request->isXmlHttpRequest()) {
            return;
        }

        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$response->headers->has('X-Debug-Token')
            || $response->isRedirection()
            || ($response->headers->has('Content-Type') && false === strpos($response->headers->get('Content-Type'), 'html'))
            || 'html' !== $request->getRequestFormat()
        ) {
            return;
        }

        $this->injectToolbar($response);
    }

    protected function injectToolbar(Response $response) {
        $content = $response->getContent();
        $pos = strripos($content, '</body>');

        if (false !== $pos) {
            $toolbar = "\n" . str_replace("\n", '', $this->twig->render("PlrApiBundle:Collector:toolbar_js.html.twig")) . "\n";
            $content = substr($content, 0, $pos) . $toolbar . substr($content, $pos);
            $response->setContent($content);
        }
    }

    public static function getSubscribedEvents() {
        return array(KernelEvents::RESPONSE => array('onKernelResponse', -128),);
    }
}
