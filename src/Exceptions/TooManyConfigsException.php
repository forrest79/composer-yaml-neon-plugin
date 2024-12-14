<?php declare(strict_types=1);

namespace Forrest79\ComposerYamlNeonPlugin\Exceptions;

final class TooManyConfigsException extends Exception
{
	/** @var list<string> */
	private array $existingConfigs;


	/**
	 * @param list<string> $existingConfigs
	 */
	public function __construct(array $existingConfigs)
	{
		parent::__construct();
		$this->existingConfigs = $existingConfigs;
	}


	/**
	 * @return list<string>
	 */
	public function getExistingConfigs(): array
	{
		return $this->existingConfigs;
	}

}
