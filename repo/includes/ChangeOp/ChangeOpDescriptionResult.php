<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class ChangeOpDescriptionResult
 */
class ChangeOpDescriptionResult extends GenericChangeOpResult implements LanguageBoundChangeOpResult {
	private $languageCode;
	private $oldDescription;
	private $newDescription;

	/**
	 * @param EntityId|null $entityId
	 * @param string $languageCode
	 * @param $oldDescription
	 * @param $newDescription
	 * @param bool $isEntityChanged
	 */
	public function __construct( $entityId, $languageCode, $oldDescription, $newDescription, $isEntityChanged = false ) {
		parent::__construct( $entityId, $isEntityChanged );
		$this->languageCode = $languageCode;
		$this->oldDescription = $oldDescription;
		$this->newDescription = $newDescription;
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
