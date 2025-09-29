<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

/**
 * @license GPL-2.0-or-later
 */
class Aliases {
	private array $aliasesInLanguage;

	public function __construct( AliasesInLanguage ...$aliasesInLanguage ) {
		$this->aliasesInLanguage = array_combine(
			array_map( fn( AliasesInLanguage $d ) => $d->languageCode, $aliasesInLanguage ),
			$aliasesInLanguage
		);
	}

	public function getAliasesInLanguageInLanguage( string $languageCode ): ?AliasesInLanguage {
		return $this->aliasesInLanguage[$languageCode] ?? null;
	}
}
