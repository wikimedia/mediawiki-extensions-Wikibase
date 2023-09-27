<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage;

use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesInLanguageResponse {

	private AliasesInLanguage $aliasesInLanguage;

	public function __construct( AliasesInLanguage $aliasesInLanguage ) {
		$this->aliasesInLanguage = $aliasesInLanguage;
	}

	public function getAliasesInLanguage(): AliasesInLanguage {
		return $this->aliasesInLanguage;
	}

}
