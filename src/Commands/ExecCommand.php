<?php

namespace MVF\Servicer\Commands;

use MVF\Servicer\Actions\BuilderFacade;
use MVF\Servicer\Actions\Constant;
use MVF\Servicer\MessageConsumer;
use MVF\Servicer\Queues;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Functional\each;

class ExecCommand extends Command
{
    const QUEUE = 'queue';
    const ACTION = 'action';
    const HEADERS = 'header';
    const BODY = 'body';

    /**
     * Allows the execute command to build the right events class for the specified queue.
     *
     * @var Queues builder
     */
    private $queues;

    /**
     * ExecCommand constructor.
     *
     * @param Queues $queues Builder
     */
    public function __construct(Queues $queues)
    {
        $this->queues = $queues;
        parent::__construct();
    }

    /**
     * Run actions based on the event in the header.
     *
     * @param array $headers Attributes of the message headers
     * @param array $body    Attributes of the message body
     *
     * @return callable
     */
    public function triggerAction(array $headers, array $body): callable
    {
        return function ($action) use ($headers, $body) {
            $action = BuilderFacade::buildActionFor($action);
            $action->beforeAction($headers, $body, MessageConsumer::consume($action, $headers, $body));
        };
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('exec');
        $this->setDescription('Run specified action');
        $this->setHelp('Not implemented');

        $this->addArgument(
            self::QUEUE,
            InputArgument::REQUIRED,
            'The queue where the event handler is defined'
        );

        $this->addArgument(
            self::ACTION,
            InputArgument::REQUIRED,
            'The action to be executed'
        );

        $this->addOption(
            self::HEADERS,
            '-H',
            (InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY),
            'The list of headers',
            []
        );

        $this->addOption(
            self::BODY,
            '-b',
            InputOption::VALUE_OPTIONAL,
            'The payload of the event',
            '{}'
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
        $headers = $this->getHeaders($input);
        $body = \GuzzleHttp\json_decode($input->getOption(self::BODY), true);

        $queue = $input->getArgument(self::QUEUE);
        $queueClass = $this->queues->getClass($queue);

        $actions = Constant::getActions($queueClass . '::' . $headers['event']);
        each($actions, $this->triggerAction($headers, $body));
    }

    /**
     * Converts input header options to an object.
     *
     * @param InputInterface $input Command line inputs
     *
     * @return array
     */
    private function getHeaders(InputInterface $input): array
    {
        $headers = [];
        foreach ($input->getOption(self::HEADERS) as $header) {
            $pattern = '/^(\w*)=(.*)$/';
            if (preg_match($pattern, $header, $matches) !== false) {
                [$full, $key, $value] = $matches;
                $field = strtolower($key);
                $headers[$field] = $value;
            }
        }

        $headers['event'] = $input->getArgument(self::ACTION);

        return $headers;
    }
}
