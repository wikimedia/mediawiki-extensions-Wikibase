<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\Geo\Formatters\GlobeCoordinateFormatter;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\Formatters\WikitextGlobeCoordinateFormatter;

/**
 * @covers Wikibase\Lib\Formatters\WikitextGlobeCoordinateFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class WikitextGlobeCoordinateFormatterTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider globeCoordinateFormatProvider
	 */
	public function testFormat( GlobeCoordinateValue $value, $output, $withKartographer ) {
		$options = new FormatterOptions( [
			WikitextGlobeCoordinateFormatter::OPT_ENABLE_KARTOGRAPHER => $withKartographer
		] );
		$formatter = new WikitextGlobeCoordinateFormatter(
			new GlobeCoordinateFormatter(),
			$options
		);
		$this->assertEquals( $output, $formatter->format( $value ) );
	}

	public function globeCoordinateFormatProvider() {
		return [
			'earth with kartographer' => [
				new GlobeCoordinateValue(
					new LatLongValue( 1, 2 ),
					0.1,
					GlobeCoordinateValue::GLOBE_EARTH
				),
				'<maplink latitude="1" longitude="2">{"type":"Feature","geometry":{"type":"Point","coordinates":[2,1]}}</maplink>',
				true
			],
			'earth without kartographer' => [
				new GlobeCoordinateValue(
					new LatLongValue( 1, 2 ),
					0.1,
					GlobeCoordinateValue::GLOBE_EARTH
				),
				'1, 2',
				false
			],
			'moon with kartographer' => [
				new GlobeCoordinateValue(
					new LatLongValue( 1, 2 ),
					0.1,
					'http://www.wikidata.org/entity/Q405'
				),
				'1, 2',
				true
			],
			'moon without kartographer' => [
				new GlobeCoordinateValue(
					new LatLongValue( 1, 2 ),
					0.1,
					'http://www.wikidata.org/entity/Q405'
				),
				'1, 2',
				false
			],
		];
	}

}
