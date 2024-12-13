<?php declare(strict_types=1);

namespace Forrest79\ComposerYamlNeonPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PreCommandRunEvent;

final class Plugin implements PluginInterface, EventSubscriberInterface, Capable
{
	private PluginSingleton $pluginInstance;


	public function __construct()
	{
		$this->pluginInstance = PluginSingleton::get();
	}


	public function activate(Composer $composer, IOInterface $io): void
	{
		if (!$this->pluginInstance->hasIO()) {
			$this->pluginInstance->setIO($io);
		}
	}


	public function deactivate(Composer $composer, IOInterface $io): void
	{
		// Do nothing...
	}


	public function uninstall(Composer $composer, IOInterface $io): void
	{
		// Do nothing...
	}


	/**
	 * @return array<string, string|array{0: string, 1?: int}|array<array{0: string, 1?: int}>>
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			PluginEvents::PRE_COMMAND_RUN => [
				['onPreCommandRun', 0],
			],
		];
	}


	public function onPreCommandRun(PreCommandRunEvent $event): void
	{
		$this->pluginInstance->onPreCommandRun($event->getInput());
	}


	/**
	 * @return array<class-string<Capability\Capability>, class-string<Capability\Capability>>
	 */
	public function getCapabilities(): array
	{
		return [
			Capability\CommandProvider::class => CommandProvider::class,
		];
	}

}
