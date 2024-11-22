<?php

namespace Leap\PanelBundle\Command;

use Leap\PanelBundle\Repository\ScheduledTaskRepository;
use Leap\PanelBundle\Service\AdministrationService;
use Leap\PanelBundle\Service\GitService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Leap\PanelBundle\Entity\ScheduledTask;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Templating\EngineInterface;

class LeapTaskGitEnableCommand extends LeapScheduledTaskCommand
{
    private $templating;
    private $gitService;
    private $localGitRepoPath;

    /** @var OutputInterface */
    private $output;

    public function __construct(AdministrationService $administrationService, $administration, ManagerRegistry $doctrine, EngineInterface $templating, GitService $gitService, ScheduledTaskRepository $scheduledTaskRepository)
    {
        $this->templating = $templating;
        $this->gitService = $gitService;

        parent::__construct($administrationService, $administration, $doctrine, $scheduledTaskRepository);
    }

    protected function configure()
    {
        parent::configure();
        $this->setName("leap:task:git:enable")->setDescription("Git enable");
        $this->getDefinition()->getOption("content-block")->setDefault(1);
        $this->addOption("instructions", "i", InputOption::VALUE_REQUIRED, "Import instructions", null);
    }

    public function getTaskDescription(ScheduledTask $task)
    {
        return $this->templating->render("@LeapPanel/Administration/task_git_enable.html.twig", array());
    }

    public function getTaskInfo(InputInterface $input)
    {
        return array_merge(parent::getTaskInfo($input), [
            "instructions" => $input->getOption("instructions")
        ]);
    }

    public function getTaskType()
    {
        return ScheduledTask::TYPE_GIT_ENABLE;
    }

    protected function executeTask(ScheduledTask $task, OutputInterface $output)
    {
        $this->output = $output;
        $this->localGitRepoPath = $this->gitService->getGitRepoPath();
        $info = json_decode($task->getInfo(), true);
        $instructions = $info["instructions"];

        if (!$this->cleanUp()) {
            return 1;
        }

        if (!$this->gitService->isEnabled()) {
            $output->writeln("Git not initialized.");
            return 1;
        }
        if (!$this->clone()) return 1;

        $updateSuccessful = $this->gitService->update($instructions, $updateOutput);
        $output->writeln($updateOutput);
        if (!$updateSuccessful) {
            return 1;
        }

        $this->gitService->setGitRepoOwner();
        return 0;
    }

    private function getCloneCommand()
    {
        $url = $this->gitService->getUrl();
        $branch = $this->gitService->getBranch();
        $login = $this->gitService->getLogin();
        $password = $this->gitService->getPassword();
        $exec = $this->gitService->getGitExecPath();

        $urlWithCreds = str_replace("://", "://$login:$password@", $url);

        return "$exec clone $urlWithCreds -b $branch " . $this->localGitRepoPath;
    }

    private function cleanUp()
    {
        $this->output->writeln("cleaning contents of " . $this->localGitRepoPath);
        $fs = new Filesystem();
        $rdi = new \RecursiveDirectoryIterator($this->localGitRepoPath, \FilesystemIterator::SKIP_DOTS);
        $fs->remove($rdi);
        $this->output->writeln("contents of " . $this->localGitRepoPath . " cleared successfully");
        return true;
    }

    private function clone()
    {
        $command = $this->getCloneCommand();
        $process = new Process($command);
        $process->start();
        $process->setTimeout(null);
        $process->wait();

        if (!empty($process->getOutput())) $this->output->writeln($process->getOutput());
        if (!empty($process->getErrorOutput())) $this->output->writeln($process->getErrorOutput());
        return $process->getExitCode() === 0;
    }
}
