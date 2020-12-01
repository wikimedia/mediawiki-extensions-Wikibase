<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class ChangeOpDescriptionResult
 * @license GPL-2.0-or-later
 */
class ChangeOpDescriptionResult extends GenericChangeOpResult implements LanguageBoundChangeOpResult {

	/** @var string */
	private $languageCode;
	/** @var string|null */
	private $oldDescription;
	/** @var string|null */
	private $newDescription;

	public function __construct(
		?EntityId $entityId,
		string $languageCode,
		?string $oldDescription,
		?string $newDescription,
		bool $isEntityChanged = false
	) {
		parent::__construct( $entityId, $isEntityChanged );
		$this->languageCode = $languageCode;
		$this->oldDescription = $oldDescription;
		$this->newDescription = $newDescription;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	public function getNewDescription(): ?string {
		return $this->newDescription;
	}

	public function getOldDescription(): ?string {
		return $this->oldDescription;
	}

}
