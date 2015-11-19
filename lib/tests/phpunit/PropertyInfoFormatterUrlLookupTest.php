<?php

namespace Wikibase\Lib\Test;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\PropertyInfoFormatterUrlLookup;
use Wikibase\PropertyInfoStore;
use Wikibase\Test\MockPropertyInfoStore;

/**
 * @covers Wikibase\DataModel\Services\Lookup\PropertyFormatterUrlLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoFormatterUrlLookupTest extends \PHPUnit_Framework_TestCase {

	public function provideGetUrlPatternForProperty() {
		$p66 = new PropertyId( 'P66' );
		$p2 = new PropertyId( 'P2' );
		$p3 = new PropertyId( 'P3' );

		$url = 'http://acme.info/foo/$1';

		$infoStore = new MockPropertyInfoStore();

		$infoStore->setPropertyInfo( $p2, array(
			PropertyInfoStore::KEY_DATA_TYPE => 'string'
		) );

		$infoStore->setPropertyInfo( $p3, array(
			PropertyInfoStore::KEY_DATA_TYPE => 'string',
			PropertyInfoStore::KEY_FORMATTER_URL => $url,
		) );

		return array(
			'unknown property' => array( $infoStore, $p66, null ),
			'no url pattern' => array( $infoStore, $p2, null ),
			'url pattern defined' => array( $infoStore, $p3, $url ),
		);
	}

	/**
	 * @dataProvider provideGetUrlPatternForProperty
	 */
	public function testGetUrlPatternForProperty(
		PropertyInfoStore $infoStore,
		PropertyId $propertyId,
		$expectedPattern
	) {
		$lookup = new PropertyInfoFormatterUrlLookup( $infoStore );

		$actualPattern = $lookup->getUrlPatternForProperty( $propertyId );

		$this->assertEquals( $expectedPattern, $actualPattern );
	}

}
