<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure\DataAccess;

use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesInLanguageRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesInLanguageRetriever;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PrefetchingTermLookupAliasesRetriever implements
	ItemAliasesRetriever,
	ItemAliasesInLanguageRetriever,
	PropertyAliasesRetriever,
	PropertyAliasesInLanguageRetriever
{

	private PrefetchingTermLookup $prefetchingTermLookup;
	private ContentLanguages $termLanguages;

	public function __construct( PrefetchingTermLookup $prefetchingTermLookup, ContentLanguages $termLanguages ) {
		$this->prefetchingTermLookup = $prefetchingTermLookup;
		$this->termLanguages = $termLanguages;
	}

	public function getAliases( EntityId $entityId ): ?Aliases {
		$this->prefetchingTermLookup->prefetchTerms(
			[ $entityId ],
			[ TermTypes::TYPE_ALIAS ],
			$this->termLanguages->getLanguages()
		);

		$aliases = new Aliases();

		foreach ( $this->termLanguages->getLanguages() as $lang ) {
			$prefetchedAliases = $this->prefetchingTermLookup->getPrefetchedAliases( $entityId, $lang );

			if ( $prefetchedAliases ) {
				$aliases[$lang] = new AliasesInLanguage( $lang, $prefetchedAliases );
			}
		}

		return $aliases;
	}

	public function getAliasesInLanguage( EntityId $entityId, string $languageCode ): ?AliasesInLanguage {
		$this->prefetchingTermLookup->prefetchTerms(
			[ $entityId ],
			[ TermTypes::TYPE_ALIAS ],
			[ $languageCode ]
		);

		$prefetchedAliases = $this->prefetchingTermLookup->getPrefetchedAliases( $entityId, $languageCode );

		if ( $prefetchedAliases ) {
			return new AliasesInLanguage( $languageCode, $prefetchedAliases );
		}

		return null;
	}

}
