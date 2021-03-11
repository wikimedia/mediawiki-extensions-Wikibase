<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesWikibaseRepoEntityTypesHookHandler {

	/** @var bool */
	private $federatedPropertiesEnabled;

	/** @var array */
	private $entityTypeDefs;

	public function __construct( bool $federatedPropertiesEnabled, array $entityTypeDefs ) {
		$this->federatedPropertiesEnabled = $federatedPropertiesEnabled;
		$this->entityTypeDefs = $entityTypeDefs;
	}

	/**
	 * Adds overrides for entity services concerning federated properties if the feature is enabled.
	 *
	 * @note This is bootstrap code, it is executed for EVERY request.
	 * Avoid instantiating objects here!
	 *
	 * @param array[] $entityTypeDefinitions
	 */
	public static function onWikibaseRepoEntityTypes( array &$entityTypeDefinitions ) {
		self::factory()->doWikibaseRepoEntityTypes( $entityTypeDefinitions );
	}

	public function doWikibaseRepoEntityTypes( array &$entityTypeDefinitions ) {
		if ( !$this->federatedPropertiesEnabled ) {
			return;
		}

		$entityTypeDefinitions = wfArrayPlus2d(
			$this->entityTypeDefs,
			$entityTypeDefinitions
		);
	}

	private static function factory(): self {
		return new self(
			WikibaseRepo::getSettings()->getSetting( 'federatedPropertiesEnabled' ),
			require __DIR__ . '/../../WikibaseRepo.FederatedProperties.entitytypes.php'
		);
	}

}
