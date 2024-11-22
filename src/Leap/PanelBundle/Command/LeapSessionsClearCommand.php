<?php

namespace Leap\PanelBundle\Command;

use Leap\PanelBundle\Service\MaintenanceService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LeapSessionsClearCommand extends Command
{

    private $maintenanceService;

    public function __construct(MaintenanceService $maintenanceService)
    {
        parent::__construct();

        $this->maintenanceService = $maintenanceService;
    }

    protected function configure()
    {
        $this->setName("leap:sessions:clear")->setDescription("Clearing session files.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->maintenanceService->deleteOldSessions();
    }

}
