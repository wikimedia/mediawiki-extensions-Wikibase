<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;

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
		$this->mockService( 'WikibaseClient.ExternalUserNames',
			null );

		$recentChangeFactory = $this->getService( 'WikibaseClient.RecentChangeFactory' );

		$this->assertInstanceOf( RecentChangeFactory::class, $recentChangeFactory );
	}

}
