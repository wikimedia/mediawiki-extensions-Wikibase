<?php

declare( strict_types=1 );

namespace Wikibase\Repo\RemoteEntity;

use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * Adds a default Wikidata API source when:
 * - federatedValuesEnabled is true
 * - No API source for items already exists
 *
 * This allows users to enable federated values with minimal configuration:
 *   $wgWBRepoSettings['federatedValuesEnabled'] = true;
 *
 * @license GPL-2.0-or-later
 */
class DefaultWikidataEntitySourceAdder {

	private bool $federatedValuesEnabled;
	private SubEntityTypesMapper $subTypeMapper;

	public function __construct(
		bool $federatedValuesEnabled,
		SubEntityTypesMapper $subTypeMapper
	) {
		$this->federatedValuesEnabled = $federatedValuesEnabled;
		$this->subTypeMapper = $subTypeMapper;
	}

	/**
	 * Add a default Wikidata source for items if federated values is enabled
	 * and no API source for items already exists.
	 */
	public function addDefaultIfRequired( EntitySourceDefinitions $existingDefinitions ): EntitySourceDefinitions {
		if ( !$this->federatedValuesEnabled ) {
			return $existingDefinitions;
		}

		// Check if there's already an API source that provides items
		foreach ( $existingDefinitions->getSources() as $source ) {
			if ( $source->getType() === ApiEntitySource::TYPE ) {
				$entityTypes = $source->getEntityTypes();
				if ( in_array( Item::ENTITY_TYPE, $entityTypes, true ) ) {
					// Already have an API source for items, don't add another
					return $existingDefinitions;
				}
			}
		}

		// Add Wikidata as default source for federated values
		$sources = $existingDefinitions->getSources();
		$sources[] = new ApiEntitySource(
			'wikidata',
			[ Item::ENTITY_TYPE ],
			'http://www.wikidata.org/entity/',
			// RDF node namespace prefix: produces wd (entities), wdt (truthy), wdno (no-value), etc.
			'wd',
			// RDF predicate namespace prefix: empty to match Wikidata's standard p, ps, pq, pr prefixes
			'',
			'd',
			'https://www.wikidata.org/w/api.php'
		);

		return new EntitySourceDefinitions( $sources, $this->subTypeMapper );
	}
}

diff --git a/repo/includes/RemoteEntity/Hooks/RemoteEntitySearchHelperCallbacksHookHandler.php b/repo/includes/RemoteEntity/Hooks/RemoteEntitySearchHelperCallbacksHookHandler.php
index abef2bc47b..dc533c8371 100644
--- a/repo/includes/RemoteEntity/Hooks/RemoteEntitySearchHelperCallbacksHookHandler.php
