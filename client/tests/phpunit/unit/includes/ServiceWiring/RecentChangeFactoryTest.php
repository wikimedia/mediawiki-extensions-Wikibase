<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use MediaWiki\User\CentralId\CentralIdLookupFactory;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikibase\Lib\Rdbms\ClientDomainDbFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class RecentChangeFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getContentLanguage' );
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'client',
			] ) );
		$clientDomainDb = $this->createMock( ClientDomainDb::class );
		$clientDomainDbFactory = $this->createMock( ClientDomainDbFactory::class );
		$clientDomainDbFactory->expects( $this->once() )
			->method( 'newLocalDb' )->willReturn( $clientDomainDb );
		$this->mockService( 'WikibaseClient.ClientDomainDbFactory', $clientDomainDbFactory );

		$this->mockService(
			'WikibaseClient.EntitySourceDefinitions',
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) )
		);
		$centralIdLookupFactory = $this->createMock( CentralIdLookupFactory::class );
		$centralIdLookupFactory->expects( $this->once() )
			->method( 'getNonLocalLookup' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getCentralIdLookupFactory' )
			->willReturn( $centralIdLookupFactory );
		$this->mockService( 'WikibaseClient.ExternalUserNames',
			null );

		$recentChangeFactory = $this->getService( 'WikibaseClient.RecentChangeFactory' );

		$this->assertInstanceOf( RecentChangeFactory::class, $recentChangeFactory );
	}

}
