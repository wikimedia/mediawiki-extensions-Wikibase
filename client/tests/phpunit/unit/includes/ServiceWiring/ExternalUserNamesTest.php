<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use ExternalUserNames;
use HashSiteStore;
use Psr\Log\Test\TestLogger;
use Site;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ExternalUserNamesTest extends ServiceWiringTestCase {

	/** @param string|false $databaseName */
	private function mockItemAndPropertySource( $databaseName = 'repowiki' ) {
		$this->mockService( 'WikibaseClient.ItemAndPropertySource',
			new DatabaseEntitySource(
				'repo',
				$databaseName,
				[],
				'',
				'',
				'',
				''
			) );
	}

	public function testConstructionWithoutSite(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( new HashSiteStore( /* empty */ ) );
		$this->mockItemAndPropertySource();
		$logger = new TestLogger();
		$this->mockService( 'WikibaseClient.Logger',
			$logger );

		$this->assertNull( $this->getService( 'WikibaseClient.ExternalUserNames' ) );
		$this->assertTrue( $logger->hasWarningRecords(), 'logged warning' );
	}

	public function testConstructionWithSiteWithoutInterwikiIds(): void {
		$site = new Site();
		$site->setGlobalId( 'repowiki' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( new HashSiteStore( [ $site ] ) );
		$this->mockItemAndPropertySource();
		$logger = new TestLogger();
		$this->mockService( 'WikibaseClient.Logger',
			$logger );

		$this->assertNull( $this->getService( 'WikibaseClient.ExternalUserNames' ) );
		$this->assertTrue( $logger->hasWarningRecords(), 'logged warning' );
	}

	public function testConstructionWithSiteWithInterwikiIds(): void {
		$site = new Site();
		$site->setGlobalId( 'repowiki' );
		$site->addInterwikiId( 'r' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( new HashSiteStore( [ $site ] ) );
		$this->mockItemAndPropertySource();

		/** @var ExternalUserNames $externalUserNames */
		$externalUserNames = $this->getService( 'WikibaseClient.ExternalUserNames' );

		$this->assertInstanceOf( ExternalUserNames::class, $externalUserNames );
		$this->assertSame( 'r>MyUser', $externalUserNames->addPrefix( 'MyUser' ) );
	}

	public function testConstructionWithLocalSiteWithInterwikiIds(): void {
		$site = new Site();
		$site->setGlobalId( 'repowiki' );
		$site->addInterwikiId( 'r' );
		$this->mockService( 'WikibaseClient.Site', $site );
		$this->serviceContainer->expects( $this->never() )
			->method( 'getSiteLookup' );
		$this->mockItemAndPropertySource( false );

		/** @var ExternalUserNames $externalUserNames */
		$externalUserNames = $this->getService( 'WikibaseClient.ExternalUserNames' );

		$this->assertInstanceOf( ExternalUserNames::class, $externalUserNames );
		$this->assertSame( 'r>MyUser', $externalUserNames->addPrefix( 'MyUser' ) );
	}

}
