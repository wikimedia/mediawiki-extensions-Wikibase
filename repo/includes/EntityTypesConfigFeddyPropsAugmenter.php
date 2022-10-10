<?php

declare( strict_types = 1 );
namespace Wikibase\Repo;

use function wfArrayPlus2d;

/**
 * Service that modifies entity type definitions for federated properties.
 * This service is intended to be no longer needed once we improve the way we handle
 * EntityTypeDefinitions see: T280153
 *
 * @see @ref docs_topics_entitytypes for the fields of a definition array
 * @see @ref docs_components_repo-federated-properties for the meaning of federated properties
 *
 * @license GPL-2.0-or-later
 */
class EntityTypesConfigFeddyPropsAugmenter {
	private $fedPropsEntityTypeDefinitions;

	public function __construct( array $fedPropsEntityTypeDefinitions ) {
		$this->fedPropsEntityTypeDefinitions = $fedPropsEntityTypeDefinitions;
	}

	/**
	 * @param array[] $existingEntityTypes Map from entity types to entity definitions
	 * @return array Map from entity types to entity definitions
	 */
	public function override( array $existingEntityTypes ) {
		return wfArrayPlus2d(
			$this->fedPropsEntityTypeDefinitions,
			$existingEntityTypes
		);
	}

	public static function factory(): self {
		return new EntityTypesConfigFeddyPropsAugmenter(
			require __DIR__ . '/../WikibaseRepo.FederatedProperties.OverrideEntityServices.php'
		);
	}
}
