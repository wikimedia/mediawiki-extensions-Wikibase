<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit4And6Compat;
use Wikibase\Lib\Store\FallbackPropertyOrderProvider;
use Wikibase\Lib\Store\PropertyOrderProvider;

/**
 * @covers Wikibase\Lib\Store\FallbackPropertyOrderProvider
 *
 * @group WikibaseStore
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class FallbackPropertyOrderProviderTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function getPropertyOrderProvider() {
		return [
			[
				null,
				null,
				null
			],
			[
				'primary-return-value',
				'primary-return-value',
				'secondary-return-value'
			],
			[
				'secondary-return-value',
				null,
				'secondary-return-value'
			]
		];
	}

	/**
	 * @dataProvider getPropertyOrderProvider
	 */
	public function testGetPropertyOrder( $expected, $primaryReturnValue, $secondaryReturnValue ) {
		$primaryProvider = $this->getMock( PropertyOrderProvider::class );
		$primaryProvider->expects( $this->once() )
			->method( 'getPropertyOrder' )
			->with()
			->will( $this->returnValue( $primaryReturnValue ) );

		$secondaryProvider = $this->getMock( PropertyOrderProvider::class );
		$secondaryProvider->expects( $this->exactly( $primaryReturnValue === null ? 1 : 0 ) )
			->method( 'getPropertyOrder' )
			->with()
			->will( $this->returnValue( $secondaryReturnValue ) );

		$provider = new FallbackPropertyOrderProvider( $primaryProvider, $secondaryProvider );

		$this->assertSame(
			$expected,
			$provider->getPropertyOrder()
		);
	}

}
