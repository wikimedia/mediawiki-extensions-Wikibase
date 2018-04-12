<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\NumberValue;
use InvalidArgumentException;
use PHPUnit4And6Compat;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\GlobeCoordinateDetailsFormatter;

/**
 * @covers Wikibase\Lib\GlobeCoordinateDetailsFormatter
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class GlobeCoordinateDetailsFormatterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	private function newFormatter( FormatterOptions $options = null ) {
		$vocabularyUriFormatter = $this->getMock( ValueFormatter::class );
		$vocabularyUriFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnCallback( function( $value ) {
				return preg_match( '@^http://www\.wikidata\.org/entity/(.*)@', $value, $matches )
					? "formatted-globe-{$matches[1]}"
					: $value;
			} ) );

		return new GlobeCoordinateDetailsFormatter( $vocabularyUriFormatter, $options );
	}

	/**
	 * @dataProvider quantityFormatProvider
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = $this->newFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function quantityFormatProvider() {
		$options = new FormatterOptions( [
			ValueFormatter::OPT_LANG => 'en'
		] );

		return [
			[
				new GlobeCoordinateValue( new LatLongValue( 50, 11 ), 1, GlobeCoordinateValue::GLOBE_EARTH ),
				$options,
				'@' . implode( '.*',
					[
						'<h4[^<>]*>[^<>]*50[^<>]+11[^<>]*</h4>',
						'<td[^<>]*>[^<>]*50[^<>]*</td>',
						'<td[^<>]*>[^<>]*11[^<>]*</td>',
						'<td[^<>]*>[^<>]*1[^<>]*</td>',
						'<td[^<>]*>[^<>]*<a[^<>]*>[^<>]*.*formatted-globe-Q2[^<>]*</a>[^<>]*</td>',
					]
				) . '@s'
			],
		];
	}

	public function testFormatError() {
		$formatter = $this->newFormatter();
		$value = new NumberValue( 23 );

		$this->setExpectedException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

	public function testEscaping() {
		$value = $this->getMock(
			GlobeCoordinateValue::class,
			[ 'getLatitude', 'getLongitude', 'getPrecision' ],
			[ new LatLongValue( 0, 0 ), null, '<GLOBE>' ]
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

		$formatter = $this->newFormatter();
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
