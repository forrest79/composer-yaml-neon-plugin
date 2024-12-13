<?php declare(strict_types=1);

namespace Forrest79\ComposerYamlNeonPlugin\Exceptions;

final class TooManySourcesException extends Exception
{
	/** @var list<string> */
	private array $existingSources;


	/**
	 * @param list<string> $existingSources
	 */
	public function __construct(array $existingSources)
	{
		parent::__construct();
		$this->existingSources = $existingSources;
	}


	/**
	 * @return list<string>
	 */
	public function getExistingSources(): array
	{
		return $this->existingSources;
	}

}
