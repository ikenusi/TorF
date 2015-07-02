<?php

namespace TorF;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;

class FINISH extends PluginTask {
	public function __construct($plugin) {
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
	public function onRun($ticks) {
		$this->plugin->fini();
	}
}