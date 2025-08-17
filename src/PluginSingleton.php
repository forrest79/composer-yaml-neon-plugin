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
	private string|false $initialWorkingDirectory;

	private IOInterface|null $io = null;

	public ComposerFile|null $composerFile = null;

	private static self|null $instance = null;


	public function __construct()
	{
		$this->initialWorkingDirectory = getcwd();
	}


	public function __destruct()
	{
		if ($this->composerFile === null) {
			return;
		}

		$newConfigFile = $this->composerFile->clean();

		if ($this->io !== null && $this->composerFile->hasDetectedConfigFile() && !$this->composerFile->isJson()) {
			$newConfigInfo = $newConfigFile === null ? '' : sprintf(' Generated file \'%s\' was changed during the operation, new data was saved to the \'%s\'.', $this->composerFile->getConfigJsonFile(), $newConfigFile);
			$this->io->write(PHP_EOL . sprintf('<question>Data from the \'%s\' was used.%s</question>', $this->composerFile->getDetectedConfigFile(), $newConfigInfo));
		}
	}


	public function hasIO(): bool
	{
		return $this->io !== null;
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
		if ($this->composerFile !== null) {
			return;
		}

		$composerFile = new ComposerFile($this->getWorkingDirectory($input), Factory::getComposerFile());
		$composerFile->prepareJson();

		$this->composerFile = $composerFile;
	}


	private function getWorkingDirectory(InputInterface $input): string
	{
		// vendor/composer/composer/src/Composer/Console/Application.php::getNewWorkingDir()
		$workingDir = $input->getParameterOption(['--working-dir', '-d']);
		assert($workingDir === false || is_string($workingDir));

		if ($workingDir !== false && !is_dir($workingDir)) {
			throw new Exceptions\RuntimeException(sprintf('Invalid working directory specified, %s does not exist.', $workingDir));
		}

		return $workingDir === false
			? ($this->initialWorkingDirectory === false ? throw new Exceptions\RuntimeException('Can\'t get initial working directory') : $this->initialWorkingDirectory)
			: $workingDir;
	}


	public static function get(): self
	{
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
