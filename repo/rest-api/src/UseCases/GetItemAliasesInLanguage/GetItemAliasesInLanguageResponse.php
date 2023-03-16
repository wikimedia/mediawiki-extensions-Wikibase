<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage;

use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;

/**
 * @license GPL-2.0-or-later
 */
class GetItemAliasesInLanguageResponse {

	private AliasesInLanguage $aliasesInLanguage;
	private string $lastModified;
	private int $revisionId;

	public function __construct( AliasesInLanguage $aliasesInLanguage, string $lastModified, int $revisionId ) {
		$this->aliasesInLanguage = $aliasesInLanguage;
		$this->lastModified = $lastModified;
		$this->revisionId = $revisionId;
	}

	public function getAliasesInLanguage(): AliasesInLanguage {
		return $this->aliasesInLanguage;
	}

	public function getLastModified(): string {
		return $this->lastModified;
	}

	public function getRevisionId(): int {
		return $this->revisionId;
	}

}
