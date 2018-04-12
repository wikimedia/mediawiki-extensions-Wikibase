<?php

namespace Wikibase\Lib\Tests;

use DataValues\StringValue;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\FieldPropertyInfoProvider;
use Wikibase\Lib\Store\PropertyInfoProvider;
use Wikibase\Lib\PropertyInfoSnakUrlExpander;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers Wikibase\Lib\PropertyInfoSnakUrlExpander
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PropertyInfoSnakUrlExpanderTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	public function provideExpandUrl() {
		$p66 = new PropertyId( 'P66' );
		$p2 = new PropertyId( 'P2' );
		$p3 = new PropertyId( 'P3' );
		$p4 = new PropertyId( 'P4' );
		$p5 = new PropertyId( 'P5' );
		$p523 = new PropertyId( 'P523' );

		$infoLookup = new MockPropertyInfoLookup( [
			$p2->getSerialization() => [
				PropertyInfoLookup::KEY_DATA_TYPE => 'string'
			],
			$p3->getSerialization() => [
				PropertyInfoLookup::KEY_DATA_TYPE => 'string',
				PropertyInfoLookup::KEY_FORMATTER_URL => 'http://acme.info/foo/$1',
			],
			$p4->getSerialization() => [
				PropertyInfoLookup::KEY_DATA_TYPE => 'string',
				PropertyInfoLookup::KEY_FORMATTER_URL => 'http://acme.info/foo?m=test&q=$1',
			],
			$p5->getSerialization() => [
				PropertyInfoLookup::KEY_DATA_TYPE => 'string',
				PropertyInfoLookup::KEY_FORMATTER_URL => 'http://acme.info/foo#$1',
			],
			$p523->getSerialization() => [
				PropertyInfoLookup::KEY_DATA_TYPE => 'string',
				PropertyInfoLookup::KEY_FORMATTER_URL => '$1',
			],
		] );

		$infoProvider = new FieldPropertyInfoProvider( $infoLookup, PropertyInfoLookup::KEY_FORMATTER_URL );

		$value = new StringValue( 'X&Y' );
		$url = new StringValue( 'http://acme.info/&?&foo/' );

		return [
			'unknown property' => [
				$infoProvider,
				new PropertyValueSnak( $p66, $value ),
				null
			],
			'no url pattern' => [
				$infoProvider,
				new PropertyValueSnak( $p2, $value ),
				null
			],
			'url pattern defined' => [
				$infoProvider,
				new PropertyValueSnak( $p3, $value ),
				'http://acme.info/foo/X%26Y'
			],
			'value with slash' => [
				$infoProvider,
				new PropertyValueSnak( $p3, new StringValue( 'X/Y' ) ),
				'http://acme.info/foo/X/Y'
			],
			'pattern with url parameter' => [
				$infoProvider,
				new PropertyValueSnak( $p4, $value ),
				'http://acme.info/foo?m=test&q=X%26Y'
			],
			'pattern with fragment' => [
				$infoProvider,
				new PropertyValueSnak( $p5, $value ),
				'http://acme.info/foo#X%26Y'
			],
			'minimal url pattern' => [
				$infoProvider,
				new PropertyValueSnak( $p523, $url ),
				'http://acme.info/%26%3F%26foo/'
			],
		];
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
		return [
			'bad value type' => [
				new PropertyValueSnak(
					new PropertyId( 'P7' ),
					new EntityIdValue( new PropertyId( 'P18' ) )
				)
			],
		];
	}

	/**
	 * @dataProvider provideExpandUrl_ParameterTypeException
	 */
	public function testExpandUrl_ParameterTypeException( $snak ) {
		$infoProvider = new FieldPropertyInfoProvider(
			new MockPropertyInfoLookup(),
			PropertyInfoLookup::KEY_FORMATTER_URL
		);
		$urlExpander = new PropertyInfoSnakUrlExpander( $infoProvider );

		$this->setExpectedException( ParameterTypeException::class );
		$urlExpander->expandUrl( $snak );
	}

}
