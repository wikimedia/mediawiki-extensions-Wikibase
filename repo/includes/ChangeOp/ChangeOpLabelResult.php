<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ChangeOp;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Class ChangeOpLabelResult
 * @license GPL-2.0-or-later
 */
class ChangeOpLabelResult extends GenericChangeOpResult implements LanguageBoundChangeOpResult {

	/** @var string */
	private $languageCode;
	/** @var string|null */
	private $oldLabel;
	/** @var string|null */
	private $newLabel;

	public function __construct(
		?EntityId $entityId,
		string $languageCode,
		?string $oldLabel,
		?string $newLabel,
		bool $isEntityChanged = false
	) {
		parent::__construct( $entityId, $isEntityChanged );
		$this->languageCode = $languageCode;
		$this->oldLabel = $oldLabel;
		$this->newLabel = $newLabel;
	}

	public function getLanguageCode(): string {
		return $this->languageCode;
	}

	public function getNewLabel(): ?string {
		return $this->newLabel;
	}

	public function getOldLabel(): ?string {
		return $this->oldLabel;
	}

}
