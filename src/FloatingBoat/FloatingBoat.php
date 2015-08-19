<?php

namespace FloatingBoat;

use FloatingBoat\database\PluginData;
use FloatingBoat\listener\EventListener;
use FloatingBoat\listener\other\ListenerLoader;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\command\PluginCommand;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use FloatingBoat\task\AutoSaveTask;

class FloatingBoat extends PluginBase implements Listener {
	private $database;
	private $eventListener;
	private $listenerLoader;
	/**
	 * Called when the plugin is enabled
	 *
	 * @see \pocketmine\plugin\PluginBase::onEnable()
	 */
	public function onEnable() {
		$this->database = new PluginData ( $this );
		$this->eventListener = new EventListener ( $this );
		$this->listenerLoader = new ListenerLoader ( $this );
		$this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
		// $this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new AutoSaveTask ( $this ), 12000 );
	}
	/**
	 * Called when the plugin is disabled Use this to free open things and finish actions
	 *
	 * @see \pocketmine\plugin\PluginBase::onDisable()
	 */
	public function onDisable() {
		$this->save ();
	}
	/**
	 * Save plug-in configs
	 *
	 * @param string $async        	
	 */
	public function save($async = false) {
		$this->database->save ( $async );
	}
	/**
	 * Handles the received command
	 *
	 * @see \pocketmine\plugin\PluginBase::onCommand()
	 */
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		return $this->eventListener->onCommand ( $sender, $command, $label, $args );
	}
	/**
	 * Return Plug-in Database
	 */
	public function getDataBase() {
		return $this->database;
	}
	/**
	 * Return Plug-in Event Listener
	 */
	public function getEventListener() {
		return $this->eventListener;
	}
	/**
	 * Return Other Plug-in Event Listener
	 */
	public function getListenerLoader() {
		return $this->listenerLoader;
	}
}

?>