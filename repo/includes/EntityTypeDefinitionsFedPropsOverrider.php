<?php

declare( strict_types = 1 );
namespace Wikibase\Repo;

use function wfArrayPlus2d;

/**
 * Service that modifies entity type definitions when federated properties is enabled.
 * This service is intended to be no longer needed once we improve the way we handle
 * EntityTypeDefinitions see: T280153
 *
 * @see @ref md_docs_topics_entitytypes for the fields of a definition array
 * @see @ref repo-federated-properties for the meaning of federated properties
 *
 * @license GPL-2.0-or-later
 */
class EntityTypeDefinitionsFedPropsOverrider {
	private $fedPropsEntityTypeDefinitions;
	private $fedPropsEnabled;

	/**
	 * EntityTypeDefinitionsFedPropsOverrider constructor.
	 * @param array $fedPropsEntityTypeDefinitions
	 * @param bool $fedPropsEnabled
	 */
	public function __construct( array $fedPropsEntityTypeDefinitions, bool $fedPropsEnabled ) {
		$this->fedPropsEntityTypeDefinitions = $fedPropsEntityTypeDefinitions;
		$this->fedPropsEnabled = $fedPropsEnabled;
	}

	/**
	 * @param array[] $existingEntityTypes Map from entity types to entity definitions
	 * @return array Map from entity types to entity definitions
	 */
	public function override( array $existingEntityTypes ) {
		if ( $this->fedPropsEnabled ) {
			return wfArrayPlus2d(
				$this->fedPropsEntityTypeDefinitions,
				$existingEntityTypes
			);
		}
		return $existingEntityTypes;
	}

	public static function factory( bool $federatedPropertiesEnabled ): self {
		return new EntityTypeDefinitionsFedPropsOverrider(
			require __DIR__ . '../../WikibaseRepo.FederatedProperties.entitytypes.php',
			$federatedPropertiesEnabled
		);
	}
}
