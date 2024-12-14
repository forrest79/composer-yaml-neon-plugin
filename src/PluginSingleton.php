<?php declare(strict_types=1);

namespace Forrest79\ComposerYamlNeonPlugin;

use Composer\Factory;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Composer is sometimes cloning plugin instance (with a different name) and we need this only in one instance to works correctly.
 */
final class PluginSingleton
{
	private string|FALSE $initialWorkingDirectory;

	private IOInterface|NULL $io = NULL;

	public ComposerFile|NULL $composerFile = NULL;

	private static self|NULL $instance = NULL;


	public function __construct()
	{
		$this->initialWorkingDirectory = getcwd();
	}


	public function __destruct()
	{
		if ($this->composerFile === NULL) {
			return;
		}

		$newConfigFile = $this->composerFile->clean();

		if ($this->io !== NULL && $this->composerFile->hasDetectedConfigFile() && !$this->composerFile->isJson()) {
			$newConfigInfo = $newConfigFile === NULL ? '' : sprintf(' Generated file \'%s\' was changed during the operation, new data was saved to the \'%s\'.', $this->composerFile->getConfigJsonFile(), $newConfigFile);
			$this->io->write(PHP_EOL . sprintf('<question>Data from the \'%s\' was used.%s</question>', $this->composerFile->getDetectedConfigFile(), $newConfigInfo));
		}
	}


	public function hasIO(): bool
	{
		return $this->io !== NULL;
	}


	public function setIO(IOInterface $io): void
	{
		$this->io = $io;
	}


	public function getComposerFile(): ComposerFile
	{
		return $this->composerFile ?? throw new Exceptions\RuntimeException('ComposerFile is not set.');
	}


	public function onPreCommandRun(InputInterface $input): void
	{
		if ($this->composerFile !== NULL) {
			return;
		}

		$composerFile = new ComposerFile($this->getWorkingDirectory($input), Factory::getComposerFile());

		try {
			$composerFile->prepareJson();
		} catch (Exceptions\TooManyConfigsException $e) {
			if ($this->io !== NULL) {
				$this->io->writeError(sprintf(
					'Config files \'%s\' are presented in working directory - use just one of them.',
					implode('\' and \'', $e->getExistingConfigs()),
				));
			}
		}

		$this->composerFile = $composerFile;
	}


	private function getWorkingDirectory(InputInterface $input): string
	{
		// vendor/composer/composer/src/Composer/Console/Application.php::getNewWorkingDir()
		$workingDir = $input->getParameterOption(['--working-dir', '-d']);
		assert($workingDir === FALSE || is_string($workingDir));

		if ($workingDir !== FALSE && !is_dir($workingDir)) {
			throw new Exceptions\RuntimeException(sprintf('Invalid working directory specified, %s does not exist.', $workingDir));
		}

		return $workingDir === FALSE
			? ($this->initialWorkingDirectory === FALSE ? throw new Exceptions\RuntimeException('Can\'t get initial working directory') : $this->initialWorkingDirectory)
			: $workingDir;
	}


	public static function get(): self
	{
		if (self::$instance === NULL) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
