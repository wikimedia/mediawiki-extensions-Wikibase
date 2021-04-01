<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use HashSiteStore;
use Site;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\Lib\SettingsArray;
use Wikimedia\TestingAccessWrapper;

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
		$site = new Site();
		$site->setGlobalId( 'repo' );
		$site->addInterwikiId( 'repointerwiki' );
		$this->serviceContainer->expects( $this->once() )
			->method( 'getSiteLookup' )
			->willReturn( new HashSiteStore( [ $site ] ) );
		$this->mockService( 'WikibaseClient.ItemAndPropertySource',
			new EntitySource(
				'localrepo',
				'repo',
				[ 'item' => [ 'namespaceId' => 123, 'slot' => 'main' ] ],
				'',
				'',
				'',
				'repo'
			) );
		$this->mockService( 'WikibaseClient.Settings',
			new SettingsArray( [
				'siteGlobalID' => 'client',
			] ) );

		/** @var RecentChangeFactory $recentChangeFactory */
		$recentChangeFactory = $this->getService( 'WikibaseClient.RecentChangeFactory' );

		$this->assertInstanceOf( RecentChangeFactory::class, $recentChangeFactory );
		$this->assertStringStartsWith(
			'repointerwiki>',
			TestingAccessWrapper::newFromObject( $recentChangeFactory )
				->externalUsernames->addPrefix( 'TestUser' )
		);
	}

}
