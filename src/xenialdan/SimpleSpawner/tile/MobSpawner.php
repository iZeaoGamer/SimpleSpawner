<?php

namespace xenialdan\SimpleSpawner\tile;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Spawnable;
use xenialdan\SimpleSpawner\Loader;

class MobSpawner extends Spawnable {

	public function __construct(Level $level, CompoundTag $nbt) {
		if (!isset($nbt->EntityId) or !($nbt->EntityId instanceof IntTag)) {
			$nbt->EntityId = new IntTag("EntityId", 0);
		}
		if (!isset($nbt->SpawnCount) or !($nbt->SpawnCount instanceof IntTag)) {
			$nbt->SpawnCount = new IntTag("SpawnCount", 4);
		}
		if (!isset($nbt->SpawnRange) or !($nbt->SpawnRange instanceof IntTag)) {
			$nbt->SpawnRange = new IntTag("SpawnRange", 4);
		}
		if (!isset($nbt->MinSpawnDelay) or !($nbt->MinSpawnDelay instanceof IntTag)) {
			$nbt->MinSpawnDelay = new IntTag("MinSpawnDelay", 200);
		}
		if (!isset($nbt->MaxSpawnDelay) or !($nbt->MaxSpawnDelay instanceof IntTag)) {
			$nbt->MaxSpawnDelay = new IntTag("MaxSpawnDelay", 799);
		}
		if (!isset($nbt->Delay) or !($nbt->Delay instanceof IntTag)) {
			$nbt->Delay = new IntTag("Delay", mt_rand($nbt->MinSpawnDelay->getValue(), $nbt->MaxSpawnDelay->getValue()));
		}
		parent::__construct($level, $nbt);
		if ($this->getEntityId() > 0) {
			$this->scheduleUpdate();
		}
	}

	public function getEntityId() {
		return $this->namedtag["EntityId"];
	}

	public function setEntityId(int $id) {
		$this->namedtag->EntityId->setValue($id);
		$this->onChanged();
		$this->scheduleUpdate();
	}

	public function setSpawnCount(int $value) {
		$this->namedtag->SpawnCount->setValue($value);
	}

	public function setSpawnRange(int $value) {
		$this->namedtag->SpawnRange->setValue($value);
	}

	public function setMinSpawnDelay(int $value) {
		$this->namedtag->MinSpawnDelay->setValue($value);
	}

	public function setMaxSpawnDelay(int $value) {
		$this->namedtag->MaxSpawnDelay->setValue($value);
	}

	public function getName(): string {
		if ($this->getEntityId() === 0) return "Monster Spawner";
		else {
			$name = ucfirst(Loader::getTypeArray()[$this->getEntityId()]??'monster') . ' Spawner';
			return $name;
		}
	}

	public function onUpdate() {
		if ($this->closed === true) {
			return false;
		}

		$this->timings->startTiming();

		if (!($this->chunk instanceof Chunk)) {
			return false;
		}
		if ($this->canUpdate()) {
			if ($this->getDelay() <= 0) {
				$success = 0;
				for ($i = 0; $i < $this->getSpawnCount(); $i++) {
					$pos = $this->add(mt_rand() / mt_getrandmax() * $this->getSpawnRange(), mt_rand(-1, 1), mt_rand() / mt_getrandmax() * $this->getSpawnRange());
					$target = $this->getLevel()->getBlock($pos);
					if ($target->getId() == Item::AIR) {
						$success++;
						$nbt = new CompoundTag("", [
							"Pos" => new ListTag("Pos", [
								new DoubleTag("", $pos->x),
								new DoubleTag("", $pos->y),
								new DoubleTag("", $pos->z)
							]),
							"Motion" => new ListTag("Motion", [
								new DoubleTag("", 0),
								new DoubleTag("", 0),
								new DoubleTag("", 0)
							]),
							"Rotation" => new ListTag("Rotation", [
								new FloatTag("", mt_rand() / mt_getrandmax() * 360),
								new FloatTag("", 0)
							]),
						]);
						$entity = Entity::createEntity($this->getEntityId(), $this->level, $nbt);
						$entity->spawnToAll();
					}
				}
				if ($success > 0) {
					$this->setDelay(mt_rand($this->getMinSpawnDelay(), $this->getMaxSpawnDelay()));
				}
			} else {
				$this->setDelay($this->getDelay() - 1);
			}
		}

		$this->timings->stopTiming();

		return true;
	}

	public function canUpdate(): bool {
		if ($this->getEntityId() === 0) return false;
		$hasPlayer = false;
		$count = 0;
		foreach ($this->getLevel()->getEntities() as $e) {
			if ($e instanceof Player) {
				if ($e->distance($this->getBlock()) <= 15) $hasPlayer = true;
			}
			if ($e::NETWORK_ID == $this->getEntityId()) {
				$count++;
			}
		}
		if ($hasPlayer and $count < 15) { // Spawn limit = 15
			return true;
		}
		return false;
	}

	public function getDelay() {
		return $this->namedtag["Delay"];
	}

	public function getSpawnCount() {
		return $this->namedtag["SpawnCount"];
	}

	public function getSpawnRange() {
		return $this->namedtag["SpawnRange"];
	}

	public function setDelay(int $value) {
		$this->namedtag->Delay->setValue($value);
	}

	public function getMinSpawnDelay() {
		return $this->namedtag["MinSpawnDelay"];
	}

	public function getMaxSpawnDelay() {
		return $this->namedtag["MaxSpawnDelay"];
	}

	public function getSpawnCompound() {
		$c = new CompoundTag("", [
			new StringTag("id", 'MobSpawner'),
			new IntTag("x", (int)$this->x),
			new IntTag("y", (int)$this->y),
			new IntTag("z", (int)$this->z),
			new IntTag("EntityId", (int)$this->getEntityId())
		]);

		return $c;
	}
}
