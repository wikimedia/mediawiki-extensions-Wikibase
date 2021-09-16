<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use BagOStuff;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Store\AbstractTermPropertyLabelResolver;
use Wikibase\Lib\Store\Sql\Terms\Util\StatsdMonitoring;

/**
 * Resolves and caches property labels (which are unique per language) into entity IDs
 * through DatabaseTermIdsResolver api.
 * @license GPL-2.0-or-later
 */
class CachedDatabasePropertyLabelResolver extends AbstractTermPropertyLabelResolver {

	use StatsdMonitoring;

	/**
	 * @var DatabaseTermInLangIdsResolver
	 */
	private $dbTermInLangIdsResolver;

	/**
	 * @param string $languageCode The language of the labels to look up (typically, the wiki's content language)
	 * @param TermInLangIdsResolver $dbTermInLangIdsResolver Must be instance of {@link DatabaseTermInLangIdsResolver}
	 * @param BagOStuff $cache      The cache to use for labels (typically from ObjectCache::getLocalClusterInstance())
	 * @param int $cacheDuration    Number of seconds to keep the cached version for.
	 *                              Defaults to 3600 seconds = 1 hour.
	 * @param string $cacheKey      The cache key to use, auto-generated based on $lang per default.
	 *                              Should be set to something including the wiki name
	 *                              of the wiki that maintains the properties.
	 */
	public function __construct(
		$languageCode,
		TermInLangIdsResolver $dbTermInLangIdsResolver,
		BagOStuff $cache,
		$cacheDuration,
		$cacheKey
	) {
		// TODO: extract resolveTermsViaJoin into an interface to avoid such check
		if ( !( $dbTermInLangIdsResolver instanceof DatabaseTermInLangIdsResolver ) ) {
			throw new InvalidArgumentException( 'This class requires a ' . DatabaseTermInLangIdsResolver::class );
		}
		parent::__construct( $languageCode, $cache, $cacheDuration, $cacheKey );
		$this->dbTermInLangIdsResolver = $dbTermInLangIdsResolver;
	}

	protected function loadProperties(): array {
		$this->incrementForQuery( 'CachedDatabasePropertyLabelResolver_loadProperties' );
		$termsByPropertyId = $this->dbTermInLangIdsResolver->resolveTermsViaJoin(
			'wbt_property_terms',
			'wbpt_term_in_lang_id',
			'wbpt_property_id',
			[],
			[ 'label' ],
			[ $this->languageCode ]
		);

		$propertiesByLabel = [];

		foreach ( $termsByPropertyId as $propertyId => $terms ) {
			$label = $terms['label'][$this->languageCode][0] ?? null;
			if ( $label !== null ) {
				$propertiesByLabel[$label] = NumericPropertyId::newFromNumber( $propertyId );
			}
		}

		return $propertiesByLabel;
	}

}
