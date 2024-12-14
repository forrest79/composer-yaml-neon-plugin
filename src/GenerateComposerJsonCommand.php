<?php declare(strict_types=1);

namespace Forrest79\ComposerYamlNeonPlugin;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class GenerateComposerJsonCommand extends BaseCommand
{

	public function __construct()
	{
		parent::__construct('generate-composer-json');
	}


	protected function configure(): void
	{
		$this->setDescription('Generate composer.json from composer.yaml or composer.neon');
	}


	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$composerFile = PluginSingleton::get()->getComposerFile();

		if ($composerFile->hasDetectedConfigFile()) {
			if ($composerFile->isJson()) {
				$output->writeln(sprintf('<comment>There is already \'%s\', nothing was generated.</comment>', $composerFile->getConfigJsonFile()));
			} else {
				$composerFile->keepJson();

				$output->writeln(sprintf('<info>\'%s\' was generated from \'%s\'.</info>', $composerFile->getConfigJsonFile(), $composerFile->getDetectedConfigFile()));
			}
		} else {
			$output->writeln('<error>There is no composer config file.</error>');
		}

		return self::SUCCESS;
	}

}
