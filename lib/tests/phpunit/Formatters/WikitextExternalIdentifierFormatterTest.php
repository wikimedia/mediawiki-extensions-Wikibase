<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Formatters\WikitextExternalIdentifierFormatter;
use Wikibase\Lib\SnakUrlExpander;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lib\Formatters\WikitextExternalIdentifierFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class WikitextExternalIdentifierFormatterTest extends \PHPUnit\Framework\TestCase {

	public function provideFormatSnak() {
		$formatterUrlExpander = $this->createMock( SnakUrlExpander::class );

		$formatterUrlExpander->method( 'expandUrl' )
			->willReturnCallback( function( PropertyValueSnak $snak ) {
				$value = $snak->getDataValue()->getValue();

				switch ( $snak->getPropertyId()->getSerialization() ) {
					case 'P1':
						return 'http://acme.test/stuff/' . wfUrlencode( $value );
					case 'P2':
						return 'http://acme.test/[other stuff]/<' . wfUrlencode( $value ) . '>';
					default:
						return null;
				}
			} );

		return [
			'formatter URL' => [
				$formatterUrlExpander,
				new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'abc\'\'123' ) ),
				'[http://acme.test/stuff/abc%27%27123 abc&#39;&#39;123]',
			],
			'formatter URL with escaping' => [
				$formatterUrlExpander,
				new PropertyValueSnak( new NumericPropertyId( 'P2' ), new StringValue( 'abc\'\'123' ) ),
				'[http://acme.test/%5Bother%20stuff%5D/%3Cabc%27%27123%3E abc&#39;&#39;123]',
			],
			'unknown property' => [
				$formatterUrlExpander,
				new PropertyValueSnak( new NumericPropertyId( 'P345' ), new StringValue( 'abc\'\'123' ) ),
				'abc&#39;&#39;123',
			],
		];
	}

	/**
	 * @dataProvider provideFormatSnak
	 */
	public function testFormatSnak(
		SnakUrlExpander $urlExpander,
		PropertyValueSnak $snak,
		$expected
	) {
		$formatter = new WikitextExternalIdentifierFormatter( $urlExpander );
		$text = $formatter->formatSnak( $snak );
		$this->assertEquals( $expected, $text );
	}

	public function provideFormatSnak_ParameterTypeException() {
		return [
			'bad snak type' => [
				new PropertyNoValueSnak( new NumericPropertyId( 'P7' ) ),
			],
		];
	}

	/**
	 * @dataProvider provideFormatSnak_ParameterTypeException
	 */
	public function testFormatSnak_ParameterTypeException( $snak ) {
		$urlExpander = $this->createMock( SnakUrlExpander::class );
		$formatter = new WikitextExternalIdentifierFormatter( $urlExpander );

		$this->expectException( ParameterTypeException::class );
		$formatter->formatSnak( $snak );
	}

	public function testGetFormat() {
		$urlExpander = $this->createMock( SnakUrlExpander::class );
		$formatter = new WikitextExternalIdentifierFormatter( $urlExpander );

		$this->assertSame( SnakFormatter::FORMAT_WIKI, $formatter->getFormat() );
	}

}
