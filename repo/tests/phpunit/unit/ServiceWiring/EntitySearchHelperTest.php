<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\FederatedProperties\ApiServiceFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySearchHelperTest extends ServiceWiringTestCase {

	public function testConstruction(): void {

		$this->mockService(
			'WikibaseRepo.Settings',
			new SettingsArray( [ 'federatedPropertiesEnabled' => false ] )
		);

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			$this->createMock( EntityTypeDefinitions::class )
		);

		$this->assertInstanceOf(
			EntitySearchHelper::class,
			$this->getService( 'WikibaseRepo.EntitySearchHelper' )
		);
	}

	public function testConstructionWithFederatedPropertiesEnabled(): void {

		$this->mockService(
			'WikibaseRepo.Settings',
			new SettingsArray( [ 'federatedPropertiesEnabled' => true ] )
		);

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			$this->createMock( EntityTypeDefinitions::class )
		);

		$this->mockService(
			'WikibaseRepo.FederatedPropertiesServiceFactory',
			$this->createMock( ApiServiceFactory::class )
		);

		$this->assertInstanceOf(
			EntitySearchHelper::class,
			$this->getService( 'WikibaseRepo.EntitySearchHelper' )
		);
	}

}
