<?php

namespace Wikibase\Lib\Tests\Store;

use MediaWiki\Http\HttpRequestFactory;
use Psr\Log\NullLogger;
use Wikibase\Lib\Store\HttpUrlPropertyOrderProvider;

/**
 * @covers \Wikibase\Lib\Store\HttpUrlPropertyOrderProvider
 * @covers \Wikibase\Lib\Store\WikiTextPropertyOrderProvider
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class HttpUrlPropertyOrderProviderTest extends \PHPUnit\Framework\TestCase {

	public function provideGetPropertyOrder() {
		yield from WikiTextPropertyOrderProviderTestHelper::provideGetPropertyOrder();
		yield [ false, null ];
	}

	/**
	 * @dataProvider provideGetPropertyOrder
	 */
	public function testGetPropertyOrder( $text, $expected ) {
		$mockHttp = $this->createMock( HttpRequestFactory::class );

		$mockHttp->expects( $this->once() )
			->method( 'get' )
			->with( 'page-url' )
			->willReturn( $text );

		$instance = new HttpUrlPropertyOrderProvider(
			'page-url',
			$mockHttp,
			new NullLogger()
		);
		$propertyOrder = $instance->getPropertyOrder();
		$this->assertSame( $expected, $propertyOrder );
	}
}
