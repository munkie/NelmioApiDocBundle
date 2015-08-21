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
use Nelmio\ApiDocBundle\Formatter\SwaggerFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Console command to dump Swagger-compliant API definitions.
 *
 * @author Bez Hermoso <bez@activelamp.com>
 */
class SwaggerDumpCommand extends Command
{
    /**
     * @var ApiDocExtractor
     */
    protected $extractor;

    /**
     * @var SwaggerFormatter
     */
    protected $formatter;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @param ApiDocExtractor $extractor
     * @param SwaggerFormatter $formatter
     * @param Filesystem $filesystem
     */
    public function __construct(
        ApiDocExtractor $extractor,
        SwaggerFormatter $formatter,
        Filesystem $filesystem
    ) {
        parent::__construct();

        $this->extractor = $extractor;
        $this->formatter = $formatter;
        $this->filesystem = $filesystem;
    }

    protected function configure()
    {
        $this
            ->setName('api:swagger:dump')
            ->setDescription('Dumps Swagger-compliant API definitions.')
            ->addOption('resource', 'r', InputOption::VALUE_OPTIONAL, 'A specific resource API declaration to dump.')
            ->addOption('list-only', 'l', InputOption::VALUE_NONE, 'Dump resource list only.')
            ->addOption('pretty', 'p', InputOption::VALUE_NONE, 'Dump as prettified JSON.')
            ->addOption('view', null, InputOption::VALUE_OPTIONAL, 'Dump specified view', ApiDoc::DEFAULT_VIEW)
            ->addArgument('destination', InputArgument::OPTIONAL, 'Directory to dump JSON files in.')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('list-only') && $input->getOption('resource')) {
            throw new \RuntimeException('Cannot selectively dump a resource with the --list-only flag.');
        }

        $view = $input->getOption('view');

        $apiDocs = $this->extractor->all($view);

        if ($input->getOption('list-only')) {
            $data = $this->getResourceList($apiDocs);
            $this->dump($data, null, $input, $output);

            return;
        }

        if (false != ($resource = $input->getOption('resource'))) {
            $data = $this->getApiDeclaration($apiDocs, $resource);
            if (count($data['apis']) === 0) {
                throw new \InvalidArgumentException(sprintf('Resource "%s" does not exist.', $resource));
            }
            $this->dump($data, $resource, $input, $output);

            return;
        }

        /*
         * If --list-only and --resource is not specified, dump everything.
         */
        $data = $this->getResourceList($apiDocs);

        if (!$input->getArgument('destination')) {
            $output->writeln('');
            $output->writeln('<comment>Resource list: </comment>');
        }

        $this->dump($data, null, $input, $output, false);

        foreach ($data['apis'] as $api) {

            $resource = substr($api['path'], 1);
            if (!$input->getArgument('destination')) {
                $output->writeln('');
                $output->writeln(sprintf('<comment>API declaration for <info>"%s"</info> resource: </comment>', $resource));
            }
            $data = $this->getApiDeclaration($apiDocs, $resource);
            $this->dump($data, $resource, $input, $output, false);
        }
    }

    protected function dump(array $data, $resource, InputInterface $input, OutputInterface $output, $treatAsFile = true)
    {
        $destination = $input->getArgument('destination');

        $content = json_encode($data, $input->getOption('pretty') ? JSON_PRETTY_PRINT : 0);

        if (!$destination) {
            $output->writeln($content);

            return;
        }

        if ($treatAsFile === false) {
            if (!$this->filesystem->exists($destination)) {
                $this->filesystem->mkdir($destination);
            }
        }

        if (!$resource) {

            if (!$treatAsFile) {
                $destination = sprintf('%s/api-docs.json', rtrim($destination, '\\/'));
            }
            $message = sprintf('<comment>Dumping resource list to %s: </comment>', $destination);
            $this->writeToFile($content, $destination, $output, $message);

            return;
        }

        if ($treatAsFile === false) {
            $destination = sprintf('%s/%s.json', rtrim($destination, '\\/'), $resource);
        }

        $message = sprintf('<comment>Dump API declaration to %s: </comment>', $destination);
        $this->writeToFile($content, $destination, $output, $message);

    }

    protected function writeToFile($content, $file, OutputInterface $output, $message)
    {
        try {
            $this->filesystem->dumpFile($file, $content);
            $message .= ' <info>OK</info>';
        } catch (IOException $e) {
            $message .= sprintf(' <error>NOT OK - %s</error>', $e->getMessage());
        }

        $output->writeln($message);
    }

    protected function getResourceList(array $data)
    {
        return $this->formatter->format($data);
    }

    protected function getApiDeclaration(array $data, $resource)
    {
        return $this->formatter->format($data, '/' . $resource);
    }
}
