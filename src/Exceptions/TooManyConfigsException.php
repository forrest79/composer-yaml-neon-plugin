<?php declare(strict_types=1);

namespace Forrest79\ComposerYamlNeonPlugin\Exceptions;

final class TooManyConfigsException extends Exception
{

	/**
	 * @param list<string> $existingConfigs
	 */
	public function __construct(array $existingConfigs)
	{
		parent::__construct(sprintf(
			'Config files \'%s\' are presented in working directory - use just one of them.',
			implode('\' and \'', $existingConfigs),
		));
	}

}
