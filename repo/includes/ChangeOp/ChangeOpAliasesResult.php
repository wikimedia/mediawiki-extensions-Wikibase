<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class ChangeOpAliasesResult
 */
class ChangeOpAliasesResult extends GenericChangeOpResult implements LanguageBoundChangeOpResult {

	private $languageCode;
	private $oldAliases;
	private $newAliases;

	/**
	 * @param EntityId|null $entityId
	 * @param string $languageCode
	 * @param array $oldAliases
	 * @param array $newAliases
	 * @param bool $isEntityChanged
	 */
	public function __construct( $entityId, $languageCode, $oldAliases, $newAliases, $isEntityChanged = false ) {
		parent::__construct( $entityId, $isEntityChanged );
		$this->languageCode = $languageCode;
		$this->oldAliases = $oldAliases;
		$this->newAliases = $newAliases;
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
