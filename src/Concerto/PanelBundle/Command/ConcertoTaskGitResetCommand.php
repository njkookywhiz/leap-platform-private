<?php

namespace Leap\PanelBundle\Command;

use Leap\PanelBundle\Repository\ScheduledTaskRepository;
use Leap\PanelBundle\Service\AdministrationService;
use Leap\PanelBundle\Service\GitService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Leap\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Templating\EngineInterface;

class LeapTaskGitResetCommand extends LeapScheduledTaskCommand
{
    private $templating;
    private $gitService;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, EngineInterface $templating, GitService $gitService, ScheduledTaskRepository $scheduledTaskRepository)
    {
        $this->templating = $templating;
        $this->gitService = $gitService;

        parent::__construct($administrationService, $administration, $doctrine, $scheduledTaskRepository);
    }

    protected function configure()
    {
        parent::configure();
        $this->setName("leap:task:git:reset")->setDescription("Resets working copy to latest commit");
        $this->getDefinition()->getOption("content-block")->setDefault(1);
        $this->addOption("instructions", "i", InputOption::VALUE_REQUIRED, "Import instructions", null);
    }

    public function getTaskDescription(ScheduledTask $task)
    {
        $info = json_decode($task->getInfo(), true);
        $desc = $this->templating->render("@LeapPanel/Administration/task_git_reset.html.twig", array());
        return $desc;
    }

    public function getTaskInfo(InputInterface $input)
    {
        return array_merge(parent::getTaskInfo($input), [
            "instructions" => $input->getOption("instructions")
        ]);
    }

    public function getTaskType()
    {
        return ScheduledTask::TYPE_GIT_RESET;
    }

    protected function executeTask(ScheduledTask $task, OutputInterface $output)
    {
        $info = json_decode($task->getInfo(), true);
        $instructions = $info["instructions"];

        $resetSuccessful = $this->gitService->reset($instructions, $updateOutput);
        $output->writeln($updateOutput);
        if (!$resetSuccessful) {
            return 1;
        }

        $this->gitService->setGitRepoOwner();
        return 0;
    }
}
