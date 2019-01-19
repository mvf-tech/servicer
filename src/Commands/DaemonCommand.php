<?php

namespace MVF\Servicer\Commands;

use MVF\Servicer\QueueInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Functional\each;

class DaemonCommand extends Command
{
    /**
     * @var QueueInterface[]
     */
    private $queues;
    private $delay = 100;

    /**
     * DaemonCommand constructor.
     *
     * @param QueueInterface ...$queues
     */
    public function __construct(QueueInterface ...$queues)
    {
        $this->queues = $queues;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('daemon');
        $this->setDescription('Run blocking daemon that listens for actions');
        $this->setHelp('Not implemented');
        $this->addOption(
            'once',
            null,
            InputOption::VALUE_NONE,
            'Run only one iteration'
        );
    }

    /**
     * Defines the behaviour of the command.
     *
     * @param InputInterface  $input  Defines inputs
     * @param OutputInterface $output Defines outputs
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $once = $input->getOption('once');

        do {
            each($this->queues, $this->handleListen($output));
            usleep($this->delay);
        } while ($once != true);
    }

    private function handleListen(OutputInterface $output): callable
    {
        return function (QueueInterface $queue) use ($output) {
            try {
                $queue->listen();
            } catch (\Exception $exception) {
                $output->writeln($exception->getMessage());
            }
        };
    }
}
