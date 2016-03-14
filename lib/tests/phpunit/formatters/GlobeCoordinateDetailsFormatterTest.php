<?php

namespace Wikibase\Lib\Test;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\NumberValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\GlobeCoordinateDetailsFormatter;

/**
 * @covers Wikibase\Lib\GlobeCoordinateDetailsFormatter
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class GlobeCoordinateDetailsFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider quantityFormatProvider
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = new GlobeCoordinateDetailsFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function quantityFormatProvider() {
		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => 'en'
		) );

		return array(
			array(
				new GlobeCoordinateValue( new LatLongValue( 50, 11 ), 1, GlobeCoordinateValue::GLOBE_EARTH ),
				$options,
				'@' . implode( '.*',
					array(
						'<h4[^<>]*>[^<>]*50[^<>]+11[^<>]*</h4>',
						'<td[^<>]*>[^<>]*50[^<>]*</td>',
						'<td[^<>]*>[^<>]*11[^<>]*</td>',
						'<td[^<>]*>[^<>]*1[^<>]*</td>',
						'<td[^<>]*>[^<>]*.*Q2[^<>]*</td>',
					)
				) . '@s'
			),
		);
	}

	public function testFormatError() {
		$formatter = new GlobeCoordinateDetailsFormatter();
		$value = new NumberValue( 23 );

		$this->setExpectedException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

	public function testEscaping() {
		$value = $this->getMock(
			GlobeCoordinateValue::class,
			array( 'getLatitude', 'getLongitude', 'getPrecision' ),
			array( new LatLongValue( 0, 0 ), null, '<GLOBE>' )
		);
		$value->expects( $this->any() )
			->method( 'getLatitude' )
			->will( $this->returnValue( '<LAT>' ) );
		$value->expects( $this->any() )
			->method( 'getLongitude' )
			->will( $this->returnValue( '<LONG>' ) );
		$value->expects( $this->any() )
			->method( 'getPrecision' )
			->will( $this->returnValue( '<PRECISION>' ) );

		$formatter = new GlobeCoordinateDetailsFormatter();
		$formatted = $formatter->format( $value );

		$this->assertContains( '&lt;LAT&gt;', $formatted );
		$this->assertContains( '&lt;LONG&gt;', $formatted );
		$this->assertContains( '&lt;PRECISION&gt;', $formatted );
		$this->assertContains( '&lt;GLOBE&gt;', $formatted );

		$this->assertNotContains( '<LAT>', $formatted, 'never unescaped' );
		$this->assertNotContains( '<LONG>', $formatted, 'never unescaped' );
		$this->assertNotContains( '<PRECISION>', $formatted, 'never unescaped' );
		$this->assertNotContains( '<GLOBE>', $formatted, 'never unescaped' );
		$this->assertNotContains( '&amp;', $formatted, 'no double escaping' );
	}

}
