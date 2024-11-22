<?php

namespace Leap\TestBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Leap\TestBundle\Entity\TestSessionCount;
use Leap\TestBundle\Service\TestSessionCountService;

class LeapSessionsLogCommand extends Command {

    private $sessionCountService;

    public function __construct(TestSessionCountService $sessionCountService) {
        parent::__construct();

        $this->sessionCountService = $sessionCountService;
    }

    protected function configure() {
        $this->setName("leap:sessions:log")->setDescription("Log session count.");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->sessionCountService->updateCountRecord();
    }

}
