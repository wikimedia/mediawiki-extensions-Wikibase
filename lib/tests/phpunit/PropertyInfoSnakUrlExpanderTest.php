<?php

namespace Wikibase\Lib\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\FieldPropertyInfoProvider;
use Wikibase\Lib\PropertyInfoProvider;
use Wikibase\Lib\PropertyInfoSnakUrlExpander;
use Wikibase\PropertyInfoStore;
use Wikibase\Test\MockPropertyInfoStore;

/**
 * @covers Wikibase\Lib\PropertyInfoSnakUrlExpander
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

		$infoProvider = new FieldPropertyInfoProvider( $infoStore, PropertyInfoStore::KEY_FORMATTER_URL );

		$value = new StringValue( 'X&Y' );

		return array(
			'unknown property' => array(
				$infoProvider,
				new PropertyValueSnak( $p66, $value ),
				null
			),
			'no url pattern' => array(
				$infoProvider,
				new PropertyValueSnak( $p2, $value ),
				null
			),
			'url pattern defined' => array(
				$infoProvider,
				new PropertyValueSnak( $p3, $value ),
				'http://acme.info/foo/X%26Y'
			),
		);
	}

	/**
	 * @dataProvider provideExpandUrl
	 */
	public function testExpandUrl(
		PropertyInfoProvider $infoProvider,
		PropertyValueSnak $snak,
		$expected
	) {
		$lookup = new PropertyInfoSnakUrlExpander( $infoProvider );

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
		$infoProvider = new FieldPropertyInfoProvider(
			new MockPropertyInfoStore(),
			PropertyInfoStore::KEY_FORMATTER_URL
		);
		$urlExpander = new PropertyInfoSnakUrlExpander( $infoProvider );

		$this->setExpectedException( 'Wikimedia\Assert\ParameterTypeException' );
		$urlExpander->expandUrl( $snak );
	}

}
