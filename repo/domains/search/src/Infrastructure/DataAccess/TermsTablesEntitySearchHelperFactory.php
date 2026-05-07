<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure\DataAccess;

use LogicException;
use MediaWiki\Language\Language;
use MediaWiki\Request\WebRequest;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Interactors\MatchingTermsLookupSearchInteractor;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\MatchingTermsLookupFactory;
use Wikibase\Repo\Api\CombinedEntitySearchHelper;
use Wikibase\Repo\Api\EntityIdSearchHelper;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\Api\EntityTermSearchHelper;

/**
 * @license GPL-2.0-or-later
 */
class TermsTablesEntitySearchHelperFactory implements EntitySearchHelperFactory {

	public function __construct(
		private readonly EntityLookup $entityLookup,
		private readonly EntityIdParser $entityIdParser,
		private readonly EntitySourceDefinitions $entitySourceDefinitions,
		private readonly FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		private readonly array $enabledEntityTypes,
		private readonly MatchingTermsLookupFactory $matchingTermsLookupFactory,
		private readonly LanguageFallbackChainFactory $languageFallbackChainFactory,
		private readonly PrefetchingTermLookup $prefetchingTermLookup,
	) {
	}

	public function newEntitySearchHelper( string $entityType, Language $language, WebRequest $request ): EntitySearchHelper {
		$source = $this->entitySourceDefinitions->getDatabaseSourceForEntityType( $entityType );

		if ( $source === null ) {
			throw new LogicException( 'No source configured for entity type: ' . $entityType . '!' );
		}

		return new CombinedEntitySearchHelper( [
			new EntityIdSearchHelper(
				$this->entityLookup,
				$this->entityIdParser,
				$this->labelDescriptionLookupFactory->newLabelDescriptionLookup( $language ),
				$this->enabledEntityTypes
			),
			new EntityTermSearchHelper(
				new MatchingTermsLookupSearchInteractor(
					$this->matchingTermsLookupFactory->getLookupForSource( $source ),
					$this->languageFallbackChainFactory,
					$this->prefetchingTermLookup,
					$language->getCode()
				)
			),
		] );
	}

}
