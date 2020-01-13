<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\Property;

/**
 * Resolves property labels (which are unique per language) into entity IDs
 * using a TermIndex.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MatchingTermsLookupPropertyLabelResolver extends AbstractTermPropertyLabelResolver {

	/**
	 * @var MatchingTermsLookup
	 */
	private $matchingTermsLookup;

	/**
	 * @param string $languageCode The language of the labels to look up (typically, the wiki's content language)
	 * @param MatchingTermsLookup $matchingTermsLookup  The MatchingTermsLookup service to look up labels with
	 * @param BagOStuff $cache      The cache to use for labels (typically from wfGetMainCache())
	 * @param int $cacheDuration    Number of seconds to keep the cached version for.
	 *                              Defaults to 3600 seconds = 1 hour.
	 * @param string $cacheKey      The cache key to use, auto-generated based on $lang per default.
	 *                              Should be set to something including the wiki name
	 *                              of the wiki that maintains the properties.
	 */
	public function __construct(
		$languageCode,
		MatchingTermsLookup $matchingTermsLookup,
		BagOStuff $cache,
		$cacheDuration,
		$cacheKey
	) {
		parent::__construct( $languageCode, $cache, $cacheDuration, $cacheKey );
		$this->matchingTermsLookup = $matchingTermsLookup;
	}

	protected function loadProperties(): array {
		$termTemplate = new TermIndexSearchCriteria( [
			'termType' => 'label',
			'termLanguage' => $this->languageCode,
		] );

		$terms = $this->matchingTermsLookup->getMatchingTerms(
			[ $termTemplate ],
			'label',
			Property::ENTITY_TYPE,
			[
				'caseSensitive' => true,
				'prefixSearch' => false,
				'LIMIT' => false,
			]
		);

		$propertiesByLabel = [];

		foreach ( $terms as $term ) {
			$label = $term->getText();
			$propertiesByLabel[$label] = $term->getEntityId();
		}

		return $propertiesByLabel;
	}

}
