<?php

namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\PropertyInfoSnakUrlExpander;
use Wikibase\PropertyInfoStore;
use Wikibase\Test\MockPropertyInfoStore;

/**
 * @covers Wikibase\DataModel\Services\Lookup\PropertySnakUrlExpander
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoSnakUrlExpanderTest extends \PHPUnit_Framework_TestCase {

	public function provideExpandUrl() {
		$p66 = new PropertyId( 'P66' );
		$p2 = new PropertyId( 'P2' );
		$p3 = new PropertyId( 'P3' );

		$infoStore = new MockPropertyInfoStore();

		$infoStore->setPropertyInfo( $p2, array(
			// MockPropertyInfoStore requires the KEY_DATA_TYPE field.
			PropertyInfoStore::KEY_DATA_TYPE => 'string'
		) );

		$infoStore->setPropertyInfo( $p3, array(
			PropertyInfoStore::KEY_DATA_TYPE => 'string',
			PropertyInfoStore::KEY_FORMATTER_URL => 'http://acme.info/foo/$1',
		) );

		$value = new StringValue( 'X&Y' );

		return array(
			'unknown property' => array(
				$infoStore,
				new PropertyValueSnak( $p66, $value ),
				null
			),
			'no url pattern' => array(
				$infoStore,
				new PropertyValueSnak( $p2, $value ),
				null
			),
			'url pattern defined' => array(
				$infoStore,
				new PropertyValueSnak( $p3, $value ),
				'http://acme.info/foo/X%26Y'
			),
		);
	}

	/**
	 * @dataProvider provideExpandUrl
	 */
	public function testExpandUrl(
		PropertyInfoStore $infoStore,
		PropertyValueSnak $snak,
		$expected
	) {
		$lookup = new PropertyInfoSnakUrlExpander( $infoStore, PropertyInfoStore::KEY_FORMATTER_URL );

		$url = $lookup->expandUrl( $snak );
		$this->assertEquals( $expected, $url );
	}

	public function provideExpandUrl_ParameterTypeException() {
		return array(
			'bad value type' => array(
				new PropertyValueSnak(
					new PropertyId( 'P7' ),
					new EntityIdValue( new PropertyId( 'P18' ) )
				)
			),
		);
	}

	/**
	 * @dataProvider provideExpandUrl_ParameterTypeException
	 */
	public function testExpandUrl_ParameterTypeException( $snak ) {
		$infoStore = new MockPropertyInfoStore();
		$urlExpander = new PropertyInfoSnakUrlExpander( $infoStore, PropertyInfoStore::KEY_FORMATTER_URL );

		$this->setExpectedException( 'Wikimedia\Assert\ParameterTypeException' );
		$urlExpander->expandUrl( $snak );
	}

}
