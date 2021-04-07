<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests;

use MediaWikiIntegrationTestCase;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityTypeDefinitionsIntegrationTest extends MediaWikiIntegrationTestCase {
	public function testEntityTypeDefinitionsUsesNativeSearch_FedPropsDisabled() {
		$this->setService( 'WikibaseRepo.Settings', new SettingsArray( [
			'federatedPropertiesEnabled' => false
		] ) );
		$this->clearHook( 'WikibaseRepoEntityTypes' );
		$services = $this->getServiceContainer();
		$entityTypeDefinitions = $services->get( 'WikibaseRepo.EntityTypeDefinitions' );
		/** @var EntityTypeDefinitions $entityTypeDefinitions */

		$unalteredEntityTypes = require __DIR__ . '/../../../WikibaseRepo.entitytypes.php';
		$this->checkExpectedDefinitionsInPlace( $unalteredEntityTypes, $entityTypeDefinitions );
	}

	public function testEntityTypeDefinitionsUsesFedPropsSearch_FedPropsEnabled() {
		$this->setService( 'WikibaseRepo.Settings', new SettingsArray( [
			'federatedPropertiesEnabled' => true
		] ) );
		$this->clearHook( 'WikibaseRepoEntityTypes' );
		$services = $this->getServiceContainer();
		$entityTypeDefinitions = $services->get( 'WikibaseRepo.EntityTypeDefinitions' );

		$fedPropertiesEntityTypes = require __DIR__ . '/../../../WikibaseRepo.FederatedProperties.entitytypes.php';
		$this->checkExpectedDefinitionsInPlace( $fedPropertiesEntityTypes, $entityTypeDefinitions );
	}

	public function testEntityTypeDefinitionsUsesFedPropsSearch_FedPropsEnabled_AndHookFires() {
		$this->setService( 'WikibaseRepo.Settings', new SettingsArray( [
			'federatedPropertiesEnabled' => true
		] ) );
		$this->clearHook( 'WikibaseRepoEntityTypes' );
		$this->setTemporaryHook( 'WikibaseRepoEntityTypes', function ( &$array ) { $array = [];
		} );
		$services = $this->getServiceContainer();
		$entityTypeDefinitions = $services->get( 'WikibaseRepo.EntityTypeDefinitions' );

		$fedPropertiesEntityTypes = require __DIR__ . '/../../../WikibaseRepo.FederatedProperties.entitytypes.php';
		$this->checkExpectedDefinitionsInPlace( $fedPropertiesEntityTypes, $entityTypeDefinitions );
	}

	private function checkExpectedDefinitionsInPlace( $expectedDefinitions, $actualDefintions ) {
		foreach ( array_keys( $expectedDefinitions ) as $entityType ) {
			foreach ( array_keys( $expectedDefinitions[$entityType] ) as $definitionsName ) {
				$callbackDefinition = $actualDefintions->get( $definitionsName );
				$this->assertEquals(
					$expectedDefinitions[$entityType][$definitionsName],
					$callbackDefinition[$entityType]
				);
			}
		}
	}
}
