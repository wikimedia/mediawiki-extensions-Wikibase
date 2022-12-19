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
 * @covers \Wikibase\Lib\EntityTypeDefinitions
 */
class EntityTypeDefinitionsIntegrationTest extends MediaWikiIntegrationTestCase {
	public function testEntityTypeDefinitionsUsesNativeSearch_FedPropsDisabled() {
		$this->setService( 'WikibaseRepo.Settings', new SettingsArray( [
			'federatedPropertiesEnabled' => false,
		] ) );
		$this->clearHook( 'WikibaseRepoEntityTypes' );
		$services = $this->getServiceContainer();
		$entityTypeDefinitions = $services->get( 'WikibaseRepo.EntityTypeDefinitions' );
		/** @var EntityTypeDefinitions $entityTypeDefinitions */

		$unalteredEntityTypes = require __DIR__ . '/../../../WikibaseRepo.entitytypes.php';
		$this->checkExpectedDefinitionsInPlace( $unalteredEntityTypes, $entityTypeDefinitions );
	}

	private function checkExpectedDefinitionsInPlace( $expectedDefinitions, $actualDefinitions ) {
		foreach ( array_keys( $expectedDefinitions ) as $entityType ) {
			foreach ( array_keys( $expectedDefinitions[$entityType] ) as $definitionsName ) {
				$callbackDefinition = $actualDefinitions->get( $definitionsName );
				$this->assertEquals(
					$expectedDefinitions[$entityType][$definitionsName],
					$callbackDefinition[$entityType]
				);
			}
		}
	}
}
