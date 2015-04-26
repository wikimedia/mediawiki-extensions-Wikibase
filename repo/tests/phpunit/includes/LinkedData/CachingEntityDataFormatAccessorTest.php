<?php

namespace Wikibase\Test;

use HashBagOStuff;
use PHPUnit_Framework_TestCase;
use Wikibase\Repo\LinkedData\CachingEntityDataFormatAccessor;

/**
 * @covers Wikibase\Repo\LinkedData\CachingEntityDataFormatAccessor
 *
 * @group Wikibase
 * @group WikibaseEntityData
 * @group WikibaseRepo
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class CachingEntityDataFormatAccessorTest extends PHPUnit_Framework_TestCase {

	public function testGetMimeTypes() {
		$this->cacheTestCase( 'getMimeTypes' );
	}

	public function testGetFileExtensions() {
		$this->cacheTestCase( 'getFileExtensions' );
	}

	private function cacheTestCase( $method ) {
		$expected1 = array( 'cat' => 'nyan' );
		$expected2 = array( 'foo' => 'bar' );
		$wl1 = array( 'nyan' );
		$wl2 = array( 'kitten' );

		$lookup = $this->getMock( 'Wikibase\Repo\LinkedData\EntityDataFormatAccessor' );
		$lookup->expects( $this->at( 0 ) )
			->method( $method )
			->with( $wl1 )
			->will( $this->returnValue( $expected1 ) );
		$lookup->expects( $this->at( 1 ) )
			->method( $method )
			->with( $wl2 )
			->will( $this->returnValue( $expected2 ) );

		$provider = new CachingEntityDataFormatAccessor( $lookup, new HashBagOStuff() );

		$types = $provider->$method( $wl1 );

		$this->assertEquals(
			$expected1,
			$types
		);

		// Call $method again to make sure we use the cache now
		$types = $provider->$method( $wl1 );

		$this->assertEquals(
			$expected1,
			$types
		);

		// Different $whitelist, different result
		$types = $provider->$method( $wl2 );

		$this->assertEquals(
			$expected2,
			$types
		);
	}

}
