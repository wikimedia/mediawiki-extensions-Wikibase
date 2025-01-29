<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage;

use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;

/**
 * @license GPL-2.0-or-later
 */
class AddItemAliasesInLanguageResponse {

	private AliasesInLanguage $aliases;
	private string $lastModified;
	private int $revisionId;
	private bool $addedToExistingAliasGroup;

	public function __construct(
		AliasesInLanguage $aliases,
		string $lastModified,
		int $revisionId,
		bool $addedToExistingAliasGroup
	) {
		$this->aliases = $aliases;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
		$this->addedToExistingAliasGroup = $addedToExistingAliasGroup;
	}

	public function getAliases(): AliasesInLanguage {
		return $this->aliases;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

	public function wasAddedToExistingAliasGroup(): bool {
		return $this->addedToExistingAliasGroup;
	}
}
