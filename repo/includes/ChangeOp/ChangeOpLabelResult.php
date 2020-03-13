<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class ChangeOpLabelResult
 */
class ChangeOpLabelResult extends GenericChangeOpResult implements LanguageBoundChangeOpResult {

	private $languageCode;
	private $newLabel;
	private $oldLabel;

	/**
	 * @param EntityId|null $entityId
	 * @param string $languageCode
	 * @param $oldLabel
	 * @param $newLabel
	 * @param bool $isEntityChanged
	 */
	public function __construct( $entityId, $languageCode, $oldLabel, $newLabel, $isEntityChanged = false ) {
		parent::__construct( $entityId, $isEntityChanged );
		$this->languageCode = $languageCode;
		$this->oldLabel = $oldLabel;
		$this->newLabel = $newLabel;
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
