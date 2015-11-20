<?php

namespace Wikibase\Lib\Formatters\Test;

use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyFormatterUrlLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Formatters\HtmlIdValueSnakFormatter;

/**
 * @covers Wikibase\Lib\Formatters\HtmlIdValueSnakFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class HtmlIdValueSnakFormatterTest extends \MediaWikiTestCase {

	public function provideFormatSnak() {
		$formatteUrlLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\PropertyFormatterUrlLookup' );

		$formatteUrlLookup->expects( $this->any() )
			->method( 'getUrlPatternForProperty' )
			->will( $this->returnCallback( function( PropertyId $id ) {
				return $id->getSerialization() === 'P1' ? 'http://acme.test/stuff/$1' : null;
			} ) );

		return array(
			'formatter URL' => array(
				$formatteUrlLookup,
				new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'abc&123' ) ),
				'<a class="wb-external-id" href="http://acme.test/stuff/abc%26123">abc&amp;123</a>'
			),
			'unknown property' => array(
				$formatteUrlLookup,
				new PropertyValueSnak( new PropertyId( 'P2' ), new StringValue( 'abc&123' ) ),
				'<span class="wb-external-id">abc&amp;123</span>'
			),
		);
	}

	/**
	 * @dataProvider provideFormatSnak
	 */
	public function testFormatSnak(
		PropertyFormatterUrlLookup $formatteUrlLookup,
		PropertyValueSnak $snak,
		$expected
	) {
		$formatter = new HtmlIdValueSnakFormatter( $formatteUrlLookup );
		$text = $formatter->formatSnak( $snak );
		$this->assertEquals( $expected, $text );
	}

	public function provideFormatSnak_ParameterTypeException() {
		return array(
			'bad snak type' => array(
				new PropertyNoValueSnak( new PropertyId( 'P7' ) )
			),

			'bad value type' => array(
				new PropertyValueSnak(
					new PropertyId( 'P7' ),
					new EntityIdValue( new PropertyId( 'P18' ) )
				)
			),
		);
	}

	/**
	 * @dataProvider provideFormatSnak_ParameterTypeException
	 */
	public function testFormatSnak_ParameterTypeException( $snak ) {
		$formatteUrlLookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\PropertyFormatterUrlLookup' );
		$formatter = new HtmlIdValueSnakFormatter( $formatteUrlLookup );

		$this->setExpectedException( 'Wikimedia\Assert\ParameterTypeException' );
		$formatter->formatSnak( $snak );
	}

}
