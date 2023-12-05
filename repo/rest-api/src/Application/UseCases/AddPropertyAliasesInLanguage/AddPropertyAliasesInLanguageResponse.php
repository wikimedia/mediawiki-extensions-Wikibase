<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage;

use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;

/**
 * @license GPL-2.0-or-later
 */
class AddPropertyAliasesInLanguageResponse {

	private AliasesInLanguage $aliases;
	private string $lastModified;
	private int $revisionId;

	public function __construct(
		AliasesInLanguage $aliases,
		string $lastModified,
		int $revisionId
	) {
		$this->aliases = $aliases;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
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

}
