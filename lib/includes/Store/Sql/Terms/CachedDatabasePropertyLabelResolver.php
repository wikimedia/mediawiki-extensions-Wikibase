<?php

namespace Wikibase\Lib\Store\Sql\Terms;

use BagOStuff;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\AbstractTermPropertyLabelResolver;

/**
 * Resolves and caches property labels (which are unique per language) into entity IDs
 * through DatabaseTermIdsResolver api.
 */
class CachedDatabasePropertyLabelResolver extends AbstractTermPropertyLabelResolver {

	/**
	 * @var DatabaseTermIdsResolver
	 */
	private $dbTermIdsResolver;

	/**
	 * @param string $languageCode The language of the labels to look up (typically, the wiki's content language)
	 * @param TermIdsResolver $dbTermIdsResolver Must be instance of {@link DatabaseTermIdsResolver}
	 * @param BagOStuff $cache      The cache to use for labels (typically from wfGetMainCache())
	 * @param int $cacheDuration    Number of seconds to keep the cached version for.
	 *                              Defaults to 3600 seconds = 1 hour.
	 * @param string $cacheKey      The cache key to use, auto-generated based on $lang per default.
	 *                              Should be set to something including the wiki name
	 *                              of the wiki that maintains the properties.
	 */
	public function __construct(
		$languageCode,
		TermIdsResolver $dbTermIdsResolver,
		BagOStuff $cache,
		$cacheDuration,
		$cacheKey
	) {
		// TODO: extract resolveTermsViaJoin into an interface to avoid such check
		if ( !( $dbTermIdsResolver instanceof DatabaseTermIdsResolver ) ) {
			throw new InvalidArgumentException( 'This class requires a ' . DatabaseTermIdsResolver::class );
		}
		parent::__construct( $languageCode, $cache, $cacheDuration, $cacheKey );
		$this->dbTermIdsResolver = $dbTermIdsResolver;
	}

	protected function loadProperties(): array {
		$termsByPropertyId = $this->dbTermIdsResolver->resolveTermsViaJoin(
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
				$propertiesByLabel[$label] = PropertyId::newFromNumber( $propertyId );
			}
		}

		return $propertiesByLabel;
	}

}
