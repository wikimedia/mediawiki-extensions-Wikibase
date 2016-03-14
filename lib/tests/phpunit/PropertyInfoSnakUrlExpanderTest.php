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
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers Wikibase\Lib\PropertyInfoSnakUrlExpander
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PropertyInfoSnakUrlExpanderTest extends \PHPUnit_Framework_TestCase {

	public function provideExpandUrl() {
		$p66 = new PropertyId( 'P66' );
		$p2 = new PropertyId( 'P2' );
		$p3 = new PropertyId( 'P3' );
		$p4 = new PropertyId( 'P4' );
		$p5 = new PropertyId( 'P5' );
		$p523 = new PropertyId( 'P523' );

		$infoStore = new MockPropertyInfoStore();

		$infoStore->setPropertyInfo( $p2, array(
			// MockPropertyInfoStore requires the KEY_DATA_TYPE field.
			PropertyInfoStore::KEY_DATA_TYPE => 'string'
		) );

		$infoStore->setPropertyInfo( $p3, array(
			PropertyInfoStore::KEY_DATA_TYPE => 'string',
			PropertyInfoStore::KEY_FORMATTER_URL => 'http://acme.info/foo/$1',
		) );

		$infoStore->setPropertyInfo( $p4, array(
			PropertyInfoStore::KEY_DATA_TYPE => 'string',
			PropertyInfoStore::KEY_FORMATTER_URL => 'http://acme.info/foo?m=test&q=$1',
		) );

		$infoStore->setPropertyInfo( $p5, array(
			PropertyInfoStore::KEY_DATA_TYPE => 'string',
			PropertyInfoStore::KEY_FORMATTER_URL => 'http://acme.info/foo#$1',
		) );

		$infoStore->setPropertyInfo( $p523, array(
			PropertyInfoStore::KEY_DATA_TYPE => 'string',
			PropertyInfoStore::KEY_FORMATTER_URL => '$1',
		) );

		$infoProvider = new FieldPropertyInfoProvider( $infoStore, PropertyInfoStore::KEY_FORMATTER_URL );

		$value = new StringValue( 'X&Y' );
		$url = new StringValue( 'http://acme.info/&?&foo/' );

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
			'value with slash' => array(
				$infoProvider,
				new PropertyValueSnak( $p3, new StringValue( 'X/Y' ) ),
				'http://acme.info/foo/X/Y'
			),
			'pattern with url parameter' => array(
				$infoProvider,
				new PropertyValueSnak( $p4, $value ),
				'http://acme.info/foo?m=test&q=X%26Y'
			),
			'pattern with fragment' => array(
				$infoProvider,
				new PropertyValueSnak( $p5, $value ),
				'http://acme.info/foo#X%26Y'
			),
			'minimal url pattern' => array(
				$infoProvider,
				new PropertyValueSnak( $p523, $url ),
				'http://acme.info/%26%3F%26foo/'
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

		$this->setExpectedException( ParameterTypeException::class );
		$urlExpander->expandUrl( $snak );
	}

}
