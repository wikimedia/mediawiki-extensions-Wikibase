<?php

namespace Wikibase\Lib\Formatters\Test;

use DataValues\StringValue;
use DataValues\UnDeserializableValue;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Formatters\WikitextIdValueSnakFormatter;
use Wikibase\Lib\SnakUrlExpander;

/**
 * @covers Wikibase\Lib\Formatters\WikitextIdValueSnakFormatter
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
class WikitextIdValueSnakFormatterTest extends \MediaWikiTestCase {


	public function provideFormatSnak() {
		$formatteUrlLookup = $this->getMock( 'Wikibase\Lib\SnakUrlExpander' );

		$formatteUrlLookup->expects( $this->any() )
			->method( 'expandUrl' )
			->will( $this->returnCallback( function( PropertyValueSnak $snak ) {
				$id = $snak->getDataValue()->getValue();

				if ( $snak->getPropertyId()->getSerialization() == 'P1' ) {
					return 'http://acme.test/stuff/' . urlencode( $id );
				} else {
					return null;
				}
			} ) );

		return array(
			'formatter URL' => array(
				$formatteUrlLookup,
				new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'abc\'\'123' ) ),
				'[http://acme.test/stuff/abc%27%27123 abc&#39;&#39;123]'
			),
			'unknown property' => array(
				$formatteUrlLookup,
				new PropertyValueSnak( new PropertyId( 'P2' ), new StringValue( 'abc\'\'123' ) ),
				'abc&#39;&#39;123'
			),
		);
	}

	/**
	 * @dataProvider provideFormatSnak
	 */
	public function testFormatSnak(
		SnakUrlExpander $urlExpander,
		PropertyValueSnak $snak,
		$expected
	) {
		$formatter = new WikitextIdValueSnakFormatter( $urlExpander );
		$text = $formatter->formatSnak( $snak );
		$this->assertEquals( $expected, $text );
	}

	public function provideFormatSnak_ParameterTypeException() {
		return array(
			'bad snak type' => array(
				new PropertyNoValueSnak( new PropertyId( 'P7' ) )
			),
		);
	}

	/**
	 * @dataProvider provideFormatSnak_ParameterTypeException
	 */
	public function testFormatSnak_ParameterTypeException( $snak ) {
		$urlExpander = $this->getMock( 'Wikibase\Lib\SnakUrlExpander' );
		$formatter = new WikitextIdValueSnakFormatter( $urlExpander );

		$this->setExpectedException( 'Wikimedia\Assert\ParameterTypeException' );
		$formatter->formatSnak( $snak );
	}

}
