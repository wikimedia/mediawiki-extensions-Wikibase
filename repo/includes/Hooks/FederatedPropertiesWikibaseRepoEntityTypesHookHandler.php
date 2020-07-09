<?php

namespace Wikibase\Repo\Hooks;

use Wikibase\Lib\WikibaseSettings;

/**
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesWikibaseRepoEntityTypesHookHandler {

	private $federatedPropertiesEnabled;

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
		self::newFromGlobalState()->doWikibaseRepoEntityTypes( $entityTypeDefinitions );
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

	private static function newFromGlobalState(): self {
		return new self(
			WikibaseSettings::getRepoSettings()->getSetting( 'federatedPropertiesEnabled' ),
			require __DIR__ . '/../../WikibaseRepo.FederatedProperties.entitytypes.php'
		);
	}

}
