<?php


namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class ChangeOpAliasesResult
 */
class ChangeOpAliasesResult implements LanguageBoundChangeOpResult {

	private $entityId;
	private $languageCode;
	private $isEntityChanged;
	private $oldAliases;
	private $newAliases;

	/**
	 * @param EntityId|null $entityId
	 * @param string $languageCode
	 * @param array $oldAliases
	 * @param array $newAliases
	 * @param bool $isEntityChange
	 */
	public function __construct( $entityId, $languageCode, $oldAliases, $newAliases, $isEntityChange = false ) {
		$this->entityId = $entityId;
		$this->languageCode = $languageCode;
		$this->oldAliases = $oldAliases;
		$this->newAliases = $newAliases;
		$this->isEntityChanged = $isEntityChange;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	public function isEntityChanged() {
		return $this->isEntityChanged;
	}

	public function getLanguageCode() {
		return $this->languageCode;
	}

	public function getNewAliases() {
		return $this->newAliases;
	}

	public function getOldAliases() {
		return $this->oldAliases;
	}

}
