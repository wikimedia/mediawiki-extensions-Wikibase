<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\DataAccess;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PrefetchingTermLookupAliasesRetriever implements ItemAliasesRetriever {

	private PrefetchingTermLookup $prefetchingTermLookup;
	private ContentLanguages $termLanguages;

	public function __construct( PrefetchingTermLookup $prefetchingTermLookup, ContentLanguages $termLanguages ) {
		$this->prefetchingTermLookup = $prefetchingTermLookup;
		$this->termLanguages = $termLanguages;
	}

	public function getAliases( ItemId $itemId ): ?Aliases {
		$this->prefetchingTermLookup->prefetchTerms(
			[ $itemId ],
			[ TermTypes::TYPE_ALIAS ],
			$this->termLanguages->getLanguages()
		);

		$aliases = new Aliases();

		foreach ( $this->termLanguages->getLanguages() as $lang ) {
			$prefetchedAliases = $this->prefetchingTermLookup->getPrefetchedAliases( $itemId, $lang );

			if ( $prefetchedAliases ) {
				$aliases[$lang] = new AliasesInLanguage( $lang, $prefetchedAliases );
			}
		}

		return $aliases;
	}
}
