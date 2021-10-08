<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\LoggerInterface;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Tests\Mocks\MockClientStore;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AffectedPagesFinderTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray(
				 [ 'siteGlobalID' => 'somestringID' ]
			)
		);
		$this->mockService(
			'WikibaseClient.Store',
			new MockClientStore()
		);
		$this->mockService(
			'WikibaseClient.Logger',
			$this->createMock( LoggerInterface::class )
		);

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getTitleFactory' );

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getLinkBatchFactory' );

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getPageStore' );

		$service = $this->getService( 'WikibaseClient.AffectedPagesFinder' );

		$this->assertInstanceOf( AffectedPagesFinder::class, $service );
	}
}
