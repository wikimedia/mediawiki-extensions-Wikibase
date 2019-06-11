<?php

namespace Wikibase\Lib\Store;

use BagOStuff;
use Wikibase\DataModel\Entity\Property;
use Wikibase\TermIndex;

/**
 * Resolves property labels (which are unique per language) into entity IDs
 * using a TermIndex.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class TermIndexPropertyLabelResolver extends AbstractTermPropertyLabelResolver {

	/**
	 * @var TermIndex
	 */
	private $termIndex;

	/**
	 * @param string $languageCode The language of the labels to look up (typically, the wiki's content language)
	 * @param TermIndex $termIndex  The TermIndex service to look up labels with
	 * @param BagOStuff $cache      The cache to use for labels (typically from wfGetMainCache())
	 * @param int $cacheDuration    Number of seconds to keep the cached version for.
	 *                              Defaults to 3600 seconds = 1 hour.
	 * @param string $cacheKey      The cache key to use, auto-generated based on $lang per default.
	 *                              Should be set to something including the wiki name
	 *                              of the wiki that maintains the properties.
	 */
	public function __construct(
		$languageCode,
		TermIndex $termIndex,
		BagOStuff $cache,
		$cacheDuration,
		$cacheKey
	) {
		parent::__construct( $languageCode, $cache, $cacheDuration, $cacheKey );
		$this->termIndex = $termIndex;
	}

	protected function loadProperties(): array {
		$termTemplate = new TermIndexSearchCriteria( [
			'termType' => 'label',
			'termLanguage' => $this->languageCode,
		] );

		$terms = $this->termIndex->getMatchingTerms(
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
