<?php

namespace Wikibase\Lib\Test;

use DataValues\NumberValue;
use DataValues\QuantityValue;
use PHPUnit_Framework_TestCase;
use ValueFormatters\BasicNumberLocalizer;
use ValueFormatters\NumberLocalizer;
use Wikibase\Lib\QuantityDetailsFormatter;

/**
 * @covers Wikibase\Lib\QuantityDetailsFormatter
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class QuantityDetailsFormatterTest extends PHPUnit_Framework_TestCase {

	private function newFormatter( NumberLocalizer $numberLocalizer = null ) {
		$unitFormatter = $this->getMockBuilder( 'ValueFormatters\QuantityUnitFormatter' )
			->disableOriginalConstructor()
			->getMock();
		$unitFormatter->expects( $this->any() )
			->method( 'applyUnit' )
			->will( $this->returnCallback( function( $unit, $numberText ) {
				return $numberText . ' ' . $unit;
			} ) );

		return new QuantityDetailsFormatter(
			$numberLocalizer ?: new BasicNumberLocalizer(),
			$unitFormatter
		);
	}

	/**
	 * @dataProvider quantityFormatProvider
	 */
	public function testFormat( $value, $pattern ) {
		$formatter = $this->newFormatter();

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function quantityFormatProvider() {
		return array(
			array(
				QuantityValue::newFromNumber( '+5', '1', '+6', '+4' ),
				'@' . implode( '.*',
					array(
						'<h4[^<>]*>[^<>]*\b5\b[^<>]*1[^<>]*</h4>',
						'<td[^<>]*>[^<>]*\b5\b[^<>]*</td>',
						'<td[^<>]*>[^<>]*\b6\b[^<>]*</td>',
						'<td[^<>]*>[^<>]*\b4\b[^<>]*</td>',
						'<td[^<>]*>[^<>]*\b1\b[^<>]*</td>',
					)
				) . '@s'
			),
			'HTML injection' => array(
				QuantityValue::newFromNumber( '+5', '<a>m</a>', '+6', '+4' ),
				'@\b5 &lt;a&gt;m&lt;/a&gt;@'
			),
		);
	}

	public function testGivenHtmlCharacters_formatEscapesHtmlCharacters() {
		$unitFormatter = $this->getMockBuilder( 'ValueFormatters\NumberLocalizer' )
			->disableOriginalConstructor()
			->getMock();
		$unitFormatter->expects( $this->any() )
			->method( 'localizeNumber' )
			->will( $this->returnValue( '<a>+2</a>' ) );

		$formatter = $this->newFormatter( $unitFormatter );
		$value = QuantityValue::newFromNumber( '+2', '<a>m</a>', '+2', '+2' );

		$html = $formatter->format( $value );
		$this->assertNotContains( '<a>', $html );
		$this->assertContains( '&lt;a&gt;', $html );
		$this->assertNotContains( '&amp;', $html );
	}

	public function testFormatError() {
		$formatter = $formatter = $this->newFormatter();
		$value = new NumberValue( 23 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$formatter->format( $value );
	}

}
