<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\NumberValue;
use InvalidArgumentException;
use MediaWikiTestCaseTrait;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\GlobeCoordinateDetailsFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\GlobeCoordinateDetailsFormatter
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class GlobeCoordinateDetailsFormatterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	private function newFormatter( FormatterOptions $options = null ) {
		$vocabularyUriFormatter = $this->createMock( ValueFormatter::class );
		$vocabularyUriFormatter->method( 'format' )
			->willReturnCallback( function( $value ) {
				return preg_match( '@^http://www\.wikidata\.org/entity/(.*)@', $value, $matches )
					? "formatted-globe-{$matches[1]}"
					: $value;
			} );

		return new GlobeCoordinateDetailsFormatter( $vocabularyUriFormatter, $options );
	}

	/**
	 * @dataProvider quantityFormatProvider
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = $this->newFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertMatchesRegularExpression( $pattern, $html );
	}

	public function quantityFormatProvider() {
		$options = new FormatterOptions( [
			ValueFormatter::OPT_LANG => 'en',
		] );

		return [
			[
				new GlobeCoordinateValue( new LatLongValue( 50, 11 ), 1, GlobeCoordinateValue::GLOBE_EARTH ),
				$options,
				'@' . implode( '.*',
					[
						'<b[^<>]*>[^<>]*50[^<>]+11[^<>]*</b>',
						'<td[^<>]*>[^<>]*50[^<>]*</td>',
						'<td[^<>]*>[^<>]*11[^<>]*</td>',
						'<td[^<>]*>[^<>]*1[^<>]*</td>',
						'<td[^<>]*>[^<>]*<a[^<>]*>[^<>]*.*formatted-globe-Q2[^<>]*</a>[^<>]*</td>',
					]
				) . '@s',
			],
		];
	}

	public function testFormatError() {
		$formatter = $this->newFormatter();
		$value = new NumberValue( 23 );

		$this->expectException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

	public function testEscaping() {
		$value = $this->getMockBuilder( GlobeCoordinateValue::class )
			->onlyMethods( [ 'getLatitude', 'getLongitude', 'getPrecision' ] )
			->setConstructorArgs( [ new LatLongValue( 0, 0 ), null, '<GLOBE>' ] )
			->getMock();
		$value->method( 'getLatitude' )
			->willReturn( 0.0 );
		$value->method( 'getLongitude' )
			->willReturn( 0.0 );
		$value->method( 'getPrecision' )
			->willReturn( 1.0 );

		$formatter = $this->newFormatter();
		$formatted = $formatter->format( $value );
		$this->assertStringContainsString( '&lt;GLOBE&gt;', $formatted );
		$this->assertStringNotContainsString( '<GLOBE>', $formatted, 'never unescaped' );
		$this->assertStringNotContainsString( '&amp;', $formatted, 'no double escaping' );
	}

}
