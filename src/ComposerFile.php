<?php declare(strict_types=1);

namespace Forrest79\ComposerYamlNeonPlugin;

use Composer;
use Nette\Neon\Neon;
use Symfony\Component\Yaml;

final class ComposerFile
{
	private const JSON = 'json';
	private const NEON = 'neon';
	private const YML = 'yml';
	private const YAML = 'yaml';

	private string $composerJsonPath;

	private string $composerJsonFile;

	private string|NULL $detectedConfigPath = NULL;

	private string|NULL $originalComposerJsonContent = NULL;

	private bool $removeJsonSourceFileOnClean = TRUE;


	public function __construct(string $workingDir, string $composerJsonFile)
	{
		if (str_starts_with($composerJsonFile, '/')) {
			$this->composerJsonPath = $composerJsonFile;
		} else {
			$this->composerJsonPath = rtrim($workingDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $composerJsonFile;
		}

		$this->composerJsonFile = $composerJsonFile;
	}


	public function prepareJson(): void
	{
		$basePath = substr($this->composerJsonPath, 0, -1 * strlen(pathinfo($this->composerJsonPath, PATHINFO_EXTENSION)));
		$existingSources = [];
		foreach ([self::JSON, self::YAML, self::YML, self::NEON] as $extension) {
			$checkPath = $basePath . $extension;
			if (is_file($checkPath)) {
				$existingSources[] = pathinfo($checkPath, PATHINFO_BASENAME);
				$this->detectedConfigPath = $checkPath;
			}
		}

		if (count($existingSources) > 1) {
			$this->detectedConfigPath = NULL;
			throw new Exceptions\TooManySourcesException($existingSources);
		}

		if ($this->isJson()) {
			$this->keepJson();
		} else if ($this->detectedConfigPath !== NULL) {
			$data = self::fileGetContent($this->detectedConfigPath);
			$array = [];

			if ($this->isNeon()) {
				$array = Neon::decode($data);
			} else if ($this->isYaml()) {
				$array = Yaml\Yaml::parse($data);
			}

			assert(is_array($array));
			$this->writeJson($array);
		}
	}


	public function isJson(): bool
	{
		return $this->hasComposerSourceFileExtension(self::JSON);
	}


	public function isNeon(): bool
	{
		return $this->hasComposerSourceFileExtension(self::NEON);
	}


	public function isYaml(): bool
	{
		return $this->hasComposerSourceFileExtension(self::YAML) || $this->hasComposerSourceFileExtension(self::YML);
	}


	/**
	 * @param array<mixed> $data
	 */
	private function writeJson(array $data): void
	{
		(new Composer\Json\JsonFile($this->composerJsonPath))->write($data);

		$this->originalComposerJsonContent = self::fileGetContent($this->composerJsonPath);
	}


	public function clean(): string|NULL
	{
		$newSourceFile = NULL;

		if ($this->detectedConfigPath !== NULL) {
			if ($this->originalComposerJsonContent !== NULL) {
				$newComposerJsonContent = self::fileGetContent($this->composerJsonPath);

				if ($this->originalComposerJsonContent !== $newComposerJsonContent) {
					$json = json_decode($newComposerJsonContent, TRUE, flags: JSON_THROW_ON_ERROR);

					$newSourcePath = $this->detectedConfigPath . '.' . time();
					$newSource = '';
					if ($this->isNeon()) {
						$newSource = trim(Neon::encode($json, TRUE)) . PHP_EOL;
					} else if ($this->isYaml()) {
						$newSource = Yaml\Yaml::dump($json, 100);
					}

					file_put_contents($newSourcePath, $newSource);

					$newSourceFile = pathinfo($newSourcePath, PATHINFO_BASENAME);
				}
			}

			if ($this->removeJsonSourceFileOnClean) {
				@unlink($this->composerJsonPath); // intentionally @ - file may not exists
			}
		}

		return $newSourceFile;
	}


	public function getConfigJsonFile(): string
	{
		if ($this->composerJsonFile === './composer.json' || $this->composerJsonFile === 'composer.json') {
			return 'composer.json';
		}

		return $this->composerJsonFile;
	}


	public function hasDetectedConfigFile(): bool
	{
		return $this->detectedConfigPath !== NULL;
	}


	public function getDetectedConfigFile(): string
	{
		if ($this->detectedConfigPath === NULL) {
			throw new Exceptions\RuntimeException('There is no detected config file.');
		}

		if ($this->composerJsonFile === './composer.json' || $this->composerJsonFile === 'composer.json') {
			return 'composer.' . pathinfo($this->detectedConfigPath, PATHINFO_EXTENSION);
		}

		return $this->detectedConfigPath;
	}


	public function keepJson(): void
	{
		$this->removeJsonSourceFileOnClean = FALSE;
	}


	private function hasComposerSourceFileExtension(string $extension): bool
	{
		if ($this->detectedConfigPath !== NULL && is_file($this->detectedConfigPath)) {
			return strtolower(pathinfo($this->detectedConfigPath, PATHINFO_EXTENSION)) === strtolower($extension);
		}

		return FALSE;
	}


	private static function fileGetContent(string $path): string
	{
		$data = @file_get_contents($path); // intentionally @
		if ($data === FALSE) {
			throw new Exceptions\RuntimeException(sprintf('File \'%s\' not exists or is not readable.', $path));
		}

		return $data;
	}

}
