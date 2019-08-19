<?php


namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class ChangeOpDescriptionResult
 */
class ChangeOpDescriptionResult implements LanguageBoundChangeOpResult {
	private $languageCode;
	private $entityId;
	private $oldDescription;
	private $newDescription;
	private $isEntityChange;

	/**
	 * @param EntityId|null $entityId
	 * @param String $languageCode
	 * @param $oldDescription
	 * @param $newDescription
	 * @param bool $isEntityChange
	 */
	public function __construct( $entityId, $languageCode, $oldDescription, $newDescription, $isEntityChange = false ) {
		$this->languageCode = $languageCode;
		$this->entityId = $entityId;
		$this->oldDescription = $oldDescription;
		$this->newDescription = $newDescription;
		$this->isEntityChange = $isEntityChange;
	}

	public function getEntityId() {
		return $this->entityId;
	}

	public function isEntityChanged() {
		return $this->isEntityChange;
	}

	public function getLanguageCode() {
		return $this->languageCode;
	}

	public function getNewDescription() {
		return $this->newDescription;
	}

	public function getOldDescription() {
		return $this->oldDescription;
	}

}
