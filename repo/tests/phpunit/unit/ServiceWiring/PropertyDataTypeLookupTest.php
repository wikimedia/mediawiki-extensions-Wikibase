<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Psr\Log\NullLogger;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\PropertyInfoDataTypeLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Repo\FederatedProperties\ApiPropertyDataTypeLookup;
use Wikibase\Repo\FederatedProperties\ApiServiceFactory;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyDataTypeLookupTest extends ServiceWiringTestCase {

	public function testLocalProperties(): void {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'federatedPropertiesEnabled' => false,
			] ) );
		$propertyInfoLookup = $this->createMock( PropertyInfoLookup::class );
		$store = $this->createMock( Store::class );
		$store->expects( $this->once() )
			->method( 'getPropertyInfoLookup' )
			->willReturn( $propertyInfoLookup );
		$this->mockService( 'WikibaseRepo.Store',
			$store );
		$this->mockService( 'WikibaseRepo.EntityLookup',
			$this->createMock( EntityLookup::class ) );
		$this->mockService( 'WikibaseRepo.Logger',
			new NullLogger() );

		$this->assertInstanceOf(
			PropertyInfoDataTypeLookup::class,
			$this->getService( 'WikibaseRepo.PropertyDataTypeLookup' )
		);
	}

	public function testFederatedProperties() {
		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'federatedPropertiesEnabled' => true,
			] ) );
		$propertyDataTypeLookup = $this->createMock( ApiPropertyDataTypeLookup::class );
		$federatedPropertiesServiceFactory = $this->createMock( ApiServiceFactory::class );
		$federatedPropertiesServiceFactory->expects( $this->once() )
			->method( 'newApiPropertyDataTypeLookup' )
			->willReturn( $propertyDataTypeLookup );
		$this->mockService( 'WikibaseRepo.FederatedPropertiesServiceFactory',
			$federatedPropertiesServiceFactory );

		$this->assertSame(
			$propertyDataTypeLookup,
			$this->getService( 'WikibaseRepo.PropertyDataTypeLookup' )
		);
	}

}
