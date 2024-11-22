<?php

namespace Leap\PanelBundle\Controller;

use Leap\PanelBundle\Service\TestSessionService;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Templating\EngineInterface;

class TestSessionController {

    private $templating;
    private $service;
    private $translator;

    public function __construct(EngineInterface $templating, TestSessionService $service, TranslatorInterface $translator) {
        $this->templating = $templating;
        $this->service = $service;
        $this->translator = $translator;
    }
}
