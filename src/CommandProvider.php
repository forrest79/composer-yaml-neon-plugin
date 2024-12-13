<?php declare(strict_types=1);

namespace Forrest79\ComposerYamlNeonPlugin;

use Composer\Command\BaseCommand;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

final class CommandProvider implements CommandProviderCapability
{

	/**
	 * @return list<BaseCommand>
	 */
	public function getCommands(): array
	{
		return [new GenerateComposerJsonCommand()];
	}

}
