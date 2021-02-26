<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Psr\Log\InvalidArgumentException;
use Psr\Log\NullLogger;
use TitleFactory;
use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyOrderProviderTest extends ServiceWiringTestCase {
	public function propertyOrderUrlProvider(): array {
		return [
			[ 'page-url' ],
			[ null ]
		];
	}

	/**
	 * @dataProvider propertyOrderUrlProvider
	 */
	public function testConstruction( $url ): void {
		$this->serviceContainer
			->method( 'get' )
			->willReturnCallback( function ( $id ) use ( $url ) {
				switch ( $id ) {
					case 'WikibaseClient.Settings':
						return new SettingsArray(
							[ 'propertyOrderUrl' => $url ]
						);
					case 'WikibaseClient.Logger':
						return new NullLogger();
					default:
						throw new InvalidArgumentException( "Unexpected service name: $id" );
				}
			} );

		$this->serviceContainer->expects( $this->once() )
			->method( 'getTitleFactory' )
			->willReturn( $this->createMock( TitleFactory::class ) );

		$this->assertInstanceOf(
			CachingPropertyOrderProvider::class,
			$this->getService( 'WikibaseClient.PropertyOrderProvider' )
		);
	}
}
