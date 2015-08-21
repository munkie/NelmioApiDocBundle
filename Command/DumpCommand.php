<?php

/*
 * This file is part of the NelmioApiDocBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Command;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor;
use Nelmio\ApiDocBundle\Formatter\FormatterInterface;
use Nelmio\ApiDocBundle\Formatter\HtmlFormatter;
use Nelmio\ApiDocBundle\Formatter\SimpleFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class DumpCommand extends Command
{
    /**
     * @var ApiDocExtractor
     */
    protected $extractor;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array|FormatterInterface[]
     */
    protected $formatters;

    /**
     * @param ApiDocExtractor $extractor
     * @param ContainerInterface $container
     * @param array|FormatterInterface[] $formatters
     */
    public function __construct(ApiDocExtractor $extractor, ContainerInterface $container, array $formatters)
    {
        $this->extractor = $extractor;
        $this->container = $container;
        $this->formatters = $formatters;

        parent::__construct();
    }

    protected function configure()
    {
        $formats = array_keys($this->formatters);

        $this
            ->setName('api:doc:dump')
            ->setDescription('')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED,
                'Output format like: ' . implode(', ', $formats),
                $formats[0]
            )
            ->addOption('view', null, InputOption::VALUE_OPTIONAL, 'Dump specified view', ApiDoc::DEFAULT_VIEW)
            ->addOption('no-sandbox', null, InputOption::VALUE_NONE, 'Disable sandbox when html format used')
            ->addoption('pretty', null, InputOption::VALUE_NONE, 'Dump as prettified JSON when json format used')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $format = $input->getOption('format');
        $view = $input->getOption('view');

        if (!isset($this->formatters[$format])) {
            throw new \RuntimeException(sprintf('Format "%s" not supported.', $format));
        }

        $formatter = $this->formatters[$format];

        if ($formatter instanceof HtmlFormatter) {
            if ($input->getOption('no-sandbox')) {
                $formatter->setEnableSandbox(false);
            }
            $this->container->enterScope('request');
            $this->container->set('request', new Request(), 'request');
        }

        $extractedDoc = $this->extractor->all($view);
        $formattedDoc = $formatter->format($extractedDoc);

        if ($formatter instanceof SimpleFormatter) {
            $output->writeln(json_encode($formattedDoc, $input->getOption('pretty') ? JSON_PRETTY_PRINT : 0));
        } else {
            $output->writeln($formattedDoc);
        }
    }
}
