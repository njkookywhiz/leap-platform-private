<?php

namespace Leap\PanelBundle\Command;

use Leap\PanelBundle\Service\GitService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class LeapGitHistoryCommand extends Command
{
    private $gitService;
    private $localGitRepoPath;

    /** @var OutputInterface */
    private $output;

    public function __construct(GitService $gitService)
    {
        $this->gitService = $gitService;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("leap:git:history")->setDescription("Returns commit history");
    }

    private function getHistoryCommand()
    {
        $exec = $this->gitService->getGitExecPath();

        return "$exec log --pretty=format:\"%h ||| %an ||| %ar ||| %s ||| %D\" --remotes=* --branches=*";
    }

    private function history()
    {
        $command = $this->getHistoryCommand();
        $process = new Process($command);
        $process->setTimeout(null);
        $process->start();
        $process->wait();

        if (!empty($process->getOutput())) $this->output->writeln($process->getOutput());
        if (!empty($process->getErrorOutput())) $this->output->writeln($process->getErrorOutput());
        return $process->getExitCode() === 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->localGitRepoPath = $this->gitService->getGitRepoPath();

        chdir($this->localGitRepoPath);

        if (!$this->gitService->isEnabled()) {
            $output->writeln("Git not initialized.");
            return 1;
        }
        if (!$this->history()) return 1;
        return 0;
    }
}