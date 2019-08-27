<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\ChangeOp\LanguageBoundChangeOpResult;

/**
 * Stub class to providing language Bound ChangeOpResults for test
 */
class LanguageBoundChangeOpResultStub implements LanguageBoundChangeOpResult {
	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var bool
	 */
	private $isEntityChanged;

	/**
	 * @var string
	 */
	private $languageCode;

	public function __construct( EntityId $entityId = null, $isEntityChanged = false, $languageCode = '' ) {
		$this->entityId = $entityId;
		$this->isEntityChanged = $isEntityChanged;
		$this->languageCode = $languageCode;
	}

	/**
	 * The id of the entity document that the change op was applied to
	 * @return EntityId|null
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * Whether the entity document was actually changed in any way
	 * as a result of applying the change op to it
	 * @return bool
	 */
	public function isEntityChanged() {
		return $this->isEntityChanged;
	}

	/**
	 * The language code of edit
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

}
