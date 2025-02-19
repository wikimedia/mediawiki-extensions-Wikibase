<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\AliasesInLanguage;

/**
 * @license GPL-2.0-or-later
 */
interface ItemAliasesInLanguageRetriever {

	public function getAliasesInLanguage( ItemId $itemId, string $languageCode ): ?AliasesInLanguage;

}
