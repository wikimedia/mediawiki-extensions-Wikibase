<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyAliasesInLanguageRetriever {

	public function getAliasesInLanguage( PropertyId $propertyId, string $languageCode ): ?AliasesInLanguage;

}
