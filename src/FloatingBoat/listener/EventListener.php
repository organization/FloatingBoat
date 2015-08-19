<?php

namespace FloatingBoat\listener;

use FloatingBoat\database\PluginData;
use pocketmine\event\Listener;
use pocketmine\plugin\Plugin;
use FloatingBoat\listener\other\ListenerLoader;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\network\protocol\InteractPacket;
use onebone\boat\entity\Boat;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\Server;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\block\Block;
use pocketmine\network\Network;

class EventListener implements Listener {
	/**
	 *
	 * @var Plugin
	 */
	private $plugin;
	/**
	 *
	 * @var ListenerLoader
	 */
	private $listenerloader;
	/**
	 *
	 * @var Server
	 */
	private $server;
	private $db;
	private $riding;
	private $waterField;
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		$this->db = PluginData::getInstance ();
		$this->listenerloader = ListenerLoader::getInstance ();
		$this->server = Server::getInstance ();
	}
	public function registerCommand($name, $permission, $description, $usage) {
		$name = $this->db->get ( $name );
		$description = $this->db->get ( $description );
		$usage = $this->db->get ( $usage );
		$this->db->registerCommand ( $name, $permission, $description, $usage );
	}
	public function getServer() {
		return $this->server;
	}
	public function onDataPacketReceiveEvent(DataPacketReceiveEvent $event) {
		$packet = $event->getPacket ();
		$player = $event->getPlayer ();
		if ($packet instanceof InteractPacket) {
			if ($boat instanceof Boat) {
				if ($packet->action === 1) {
					$this->riding [$player->getName ()] = $packet->target;
				} elseif ($packet->action === 3) {
					if (isset ( $this->riding [$player->getName ()] )) {
						unset ( $this->riding [$player->getName ()] );
						$this->removeWaterField ( $player );
					}
				}
			}
		} elseif ($packet instanceof MovePlayerPacket) {
			if (isset ( $this->riding [$player->getName ()] )) {
				$boat = $player->getLevel ()->getEntity ( $this->riding [$player->getName ()] );
				if ($boat instanceof Boat) {
					$x = ( int ) floor ( $boat->x );
					$y = ( int ) floor ( $boat->y ) - 1;
					$z = ( int ) floor ( $boat->z );
					if (isset ( $this->waterField [$player->getName ()] ["pos"] ) and $this->waterField [$player->getName ()] ["pos"] == "{$x}:{$y}:{$z}")
						return;
					$this->setWaterField ( $player, $x, $y, $z, $player->getLevel () );
				}
			}
		}
		//
	}
	public function setWaterField(Player $player, $x, $y, $z, Level $level) {
		if (isset ( $this->waterField [$player->getName ()] ["pos"] ) and $this->waterField [$player->getName ()] ["pos"] == "{$x}:{$y}:{$z}")
			return;
		$downId = $level->getBlockIdAt ( $x, $y, $z );
		$pk = new UpdateBlockPacket ();
		
		if (isset ( $this->waterField [$player->getName ()] ["pos"] )) {
			$pos = explode ( ":", $this->waterField [$player->getName ()] ["pos"] );
			$sides = [ 
					[ 
							1,
							1 
					],
					[ 
							1,
							0 
					],
					[ 
							1,
							1 
					],
					[ 
							0,
							- 1 
					],
					[ 
							0,
							0 
					],
					[ 
							0,
							1 
					],
					[ 
							- 1,
							- 1 
					],
					[ 
							- 1,
							0 
					],
					[ 
							- 1,
							0 
					] 
			];
			foreach ( $sides as $side ) {
				$downId = $level->getBlockIdAt ( $pos [0] + $side [0], $pos [2], $pos [1] + $side [1] );
				$downDmg = $level->getBlockDataAt ( $pos [0] + $side [0], $pos [2], $pos [1] + $side [1] );
				$pk->records [] = [ 
						$pos [0] + $side [0],
						$pos [1] + $side [1],
						$pos [2],
						$downId,
						$downDmg,
						UpdateBlockPacket::FLAG_NONE 
				];
			}
		}
		if ($downId != Block::STILL_WATER) {
			$pos = explode ( ":", $this->waterField [$player->getName ()] ["pos"] );
			$sides = [ 
					[ 
							1,
							1 
					],
					[ 
							1,
							0 
					],
					[ 
							1,
							1 
					],
					[ 
							0,
							- 1 
					],
					[ 
							0,
							0 
					],
					[ 
							0,
							1 
					],
					[ 
							- 1,
							- 1 
					],
					[ 
							- 1,
							0 
					],
					[ 
							- 1,
							0 
					] 
			];
			foreach ( $sides as $side ) {
				$downId = $level->getBlockIdAt ( $pos [0] + $side [0], $pos [2], $pos [1] + $side [1] );
				$downDmg = $level->getBlockDataAt ( $pos [0] + $side [0], $pos [2], $pos [1] + $side [1] );
				$pk->records [] = [ 
						$pos [0] + $side [0],
						$pos [1] + $side [1],
						$pos [2],
						$downId,
						$downDmg,
						UpdateBlockPacket::FLAG_NONE 
				];
			}
		}
		$player->directDataPacket ( $pk->setChannel ( Network::CHANNEL_BLOCKS ) );
		$this->waterField [$player->getName ()] ["pos"] = "{$x}:{$z}:{$y}";
	}
	public function removeWaterField(Player $player) {
		$level = $player->getLevel ();
		$pk = new UpdateBlockPacket ();
		if (isset ( $this->waterField [$player->getName ()] ["pos"] )) {
			$pos = explode ( ":", $this->waterField [$player->getName ()] ["pos"] );
			$sides = [ 
					[ 
							1,
							1 
					],
					[ 
							1,
							0 
					],
					[ 
							1,
							1 
					],
					[ 
							0,
							- 1 
					],
					[ 
							0,
							0 
					],
					[ 
							0,
							1 
					],
					[ 
							- 1,
							- 1 
					],
					[ 
							- 1,
							0 
					],
					[ 
							- 1,
							0 
					] 
			];
			foreach ( $sides as $side ) {
				$downId = $level->getBlockIdAt ( $pos [0] + $side [0], $pos [2], $pos [1] + $side [1] );
				$downDmg = $level->getBlockDataAt ( $pos [0] + $side [0], $pos [2], $pos [1] + $side [1] );
				$pk->records [] = [ 
						$pos [0] + $side [0],
						$pos [1] + $side [1],
						$pos [2],
						$downId,
						$downDmg,
						UpdateBlockPacket::FLAG_NONE 
				];
			}
			$player->directDataPacket ( $pk->setChannel ( Network::CHANNEL_BLOCKS ) );
			unset ( $this->waterField [$player->getName ()] ["pos"] );
		}
	}
}

?>