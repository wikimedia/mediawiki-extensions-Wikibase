<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Formatters\HtmlExternalIdentifierFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\SnakUrlExpander;
use Wikimedia\Assert\ParameterTypeException;

/**
 * @covers \Wikibase\Lib\Formatters\HtmlExternalIdentifierFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class HtmlExternalIdentifierFormatterTest extends \PHPUnit\Framework\TestCase {

	public function provideFormatSnak() {
		$formatterUrlExpander = $this->createMock( SnakUrlExpander::class );

		$formatterUrlExpander->method( 'expandUrl' )
			->willReturnCallback( function( PropertyValueSnak $snak ) {
				if ( $snak->getPropertyId()->getSerialization() === 'P1' ) {
					$value = $snak->getDataValue()->getValue();
					return 'http://acme.test/stuff/' . wfUrlencode( $value );
				}

				return null;
			} );

		return [
			'formatter URL' => [
				$formatterUrlExpander,
				new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'abc&123' ) ),
				'<a class="wb-external-id external" href="http://acme.test/stuff/abc%26123" rel="nofollow">abc&amp;123</a>',
			],
			'unknown property' => [
				$formatterUrlExpander,
				new PropertyValueSnak( new NumericPropertyId( 'P2' ), new StringValue( 'abc&123' ) ),
				'<span class="wb-external-id">abc&amp;123</span>',
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
		$formatter = new HtmlExternalIdentifierFormatter( $urlExpander );
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
		$formatter = new HtmlExternalIdentifierFormatter( $urlExpander );

		$this->expectException( ParameterTypeException::class );
		$formatter->formatSnak( $snak );
	}

	public function testGetFormat() {
		$urlExpander = $this->createMock( SnakUrlExpander::class );
		$formatter = new HtmlExternalIdentifierFormatter( $urlExpander );

		$this->assertSame( SnakFormatter::FORMAT_HTML, $formatter->getFormat() );
	}

}
