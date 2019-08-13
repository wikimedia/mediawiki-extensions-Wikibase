<?php


namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class ChangeOpLabelResult
 */
class ChangeOpLabelResult implements LanguageBoundChangeOpResult {

	private $languageCode;
	private $newLabel;
	private $oldLabel;
	private $entityId;
	private $isEntityChange;

	/**
	 * @param EntityId|null $entityId
	 * @param string $languageCode
	 * @param $oldLabel
	 * @param $newLabel
	 * @param bool $isEntityChange
	 */
	public function __construct( $entityId, $languageCode, $oldLabel, $newLabel, $isEntityChange = false ) {
		$this->languageCode = $languageCode;
		$this->entityId = $entityId;
		$this->isEntityChange = $isEntityChange;
		$this->oldLabel = $oldLabel;
		$this->newLabel = $newLabel;
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

	public function getNewLabel() {
		return $this->newLabel;
	}

	public function getOldLabel() {
		return $this->oldLabel;
	}

}
