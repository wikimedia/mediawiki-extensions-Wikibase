<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class ChangeOpAliasesResult
 * @license GPL-2.0-or-later
 */
class ChangeOpAliasesResult extends GenericChangeOpResult implements LanguageBoundChangeOpResult {

	/** @var string */
	private $languageCode;
	/** @var string[] */
	private $oldAliases;
	/** @var string[] */
	private $newAliases;

	/**
	 * @param EntityId|null $entityId
	 * @param string $languageCode
	 * @param string[] $oldAliases
	 * @param string[] $newAliases
	 * @param bool $isEntityChanged
	 */
	public function __construct(
		?EntityId $entityId,
		string $languageCode,
		array $oldAliases,
		array $newAliases,
		bool $isEntityChanged = false
	) {
		parent::__construct( $entityId, $isEntityChanged );
		$this->languageCode = $languageCode;
		$this->oldAliases = $oldAliases;
		$this->newAliases = $newAliases;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	public function getNewAliases(): array {
		return $this->newAliases;
	}

	public function getOldAliases(): array {
		return $this->oldAliases;
	}

}
