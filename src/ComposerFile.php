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

	private string|null $detectedConfigPath = null;

	private string|null $originalComposerJsonContent = null;

	private bool $removeComposerJsonFileOnClean = true;


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
		$existingConfigs = [];
		foreach ([self::JSON, self::YAML, self::YML, self::NEON] as $extension) {
			$checkPath = $basePath . $extension;
			if (is_file($checkPath)) {
				$existingConfigs[] = pathinfo($checkPath, PATHINFO_BASENAME);
				$this->detectedConfigPath = $checkPath;
			}
		}

		if (count($existingConfigs) > 1) {
			$this->detectedConfigPath = null;
			throw new Exceptions\TooManyConfigsException($existingConfigs);
		}

		if ($this->isJson()) {
			$this->keepJson();
		} else if ($this->detectedConfigPath !== null) {
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
		return $this->hasComposerConfigFileExtension(self::JSON);
	}


	public function isNeon(): bool
	{
		return $this->hasComposerConfigFileExtension(self::NEON);
	}


	public function isYaml(): bool
	{
		return $this->hasComposerConfigFileExtension(self::YAML) || $this->hasComposerConfigFileExtension(self::YML);
	}


	/**
	 * @param array<mixed> $data
	 */
	private function writeJson(array $data): void
	{
		(new Composer\Json\JsonFile($this->composerJsonPath))->write($data);

		$this->originalComposerJsonContent = self::fileGetContent($this->composerJsonPath);
	}


	public function clean(): string|null
	{
		$newConfigFile = null;

		if ($this->detectedConfigPath !== null) {
			if ($this->originalComposerJsonContent !== null) {
				$newComposerJsonContent = self::fileGetContent($this->composerJsonPath);

				if ($this->originalComposerJsonContent !== $newComposerJsonContent) {
					$json = json_decode($newComposerJsonContent, true, flags: JSON_THROW_ON_ERROR);

					$newConfigPath = $this->detectedConfigPath . '.' . time();
					$newConfig = '';
					if ($this->isNeon()) {
						$newConfig = trim(Neon::encode($json, true)) . PHP_EOL;
					} else if ($this->isYaml()) {
						$newConfig = Yaml\Yaml::dump($json, 100);
					}

					file_put_contents($newConfigPath, $newConfig);

					$newConfigFile = pathinfo($newConfigPath, PATHINFO_BASENAME);
				}
			}

			if ($this->removeComposerJsonFileOnClean) {
				@unlink($this->composerJsonPath); // intentionally @ - file may not exists
			}
		}

		return $newConfigFile;
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
		return $this->detectedConfigPath !== null;
	}


	public function getDetectedConfigFile(): string
	{
		if ($this->detectedConfigPath === null) {
			throw new Exceptions\RuntimeException('There is no detected config file.');
		}

		if ($this->composerJsonFile === './composer.json' || $this->composerJsonFile === 'composer.json') {
			return 'composer.' . pathinfo($this->detectedConfigPath, PATHINFO_EXTENSION);
		}

		return $this->detectedConfigPath;
	}


	public function keepJson(): void
	{
		$this->removeComposerJsonFileOnClean = false;
	}


	private function hasComposerConfigFileExtension(string $extension): bool
	{
		if ($this->detectedConfigPath !== null && is_file($this->detectedConfigPath)) {
			return strtolower(pathinfo($this->detectedConfigPath, PATHINFO_EXTENSION)) === strtolower($extension);
		}

		return false;
	}


	private static function fileGetContent(string $path): string
	{
		$data = @file_get_contents($path); // intentionally @
		if ($data === false) {
			throw new Exceptions\RuntimeException(sprintf('File \'%s\' not exists or is not readable.', $path));
		}

		return $data;
	}

}
