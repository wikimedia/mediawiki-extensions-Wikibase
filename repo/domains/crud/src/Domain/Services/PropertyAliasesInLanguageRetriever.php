<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\AliasesInLanguage;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyAliasesInLanguageRetriever {

	public function getAliasesInLanguage( PropertyId $propertyId, string $languageCode ): ?AliasesInLanguage;

}
