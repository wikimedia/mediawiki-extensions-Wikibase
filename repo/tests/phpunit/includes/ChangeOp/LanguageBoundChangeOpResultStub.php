<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\ChangeOp\LanguageBoundChangeOpResult;

/**
 * Stub class to providing language Bound ChangeOpResults for test
 * @license GPL-2.0-or-later
 */
class LanguageBoundChangeOpResultStub extends ChangeOpResultStub implements LanguageBoundChangeOpResult {

	/**
	 * @var string
	 */
	private $languageCode;

	public function __construct(
		EntityId $entityId = null,
		$isEntityChanged = false,
		$languageCode = '',
		array $validationErrors = null
	) {
		parent::__construct( $entityId, $isEntityChanged, $validationErrors );
		$this->languageCode = $languageCode;
	}

	/**
	 * The language code of edit
	 * @return string
	 */
	public function getLanguageCode() {
		return $this->languageCode;
	}

}
