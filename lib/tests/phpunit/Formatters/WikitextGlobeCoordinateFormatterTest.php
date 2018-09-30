<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\Geo\Formatters\GlobeCoordinateFormatter;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\Formatters\WikitextGlobeCoordinateFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\WikitextGlobeCoordinateFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class WikitextGlobeCoordinateFormatterTest extends \PHPUnit\Framework\TestCase {

	/* private */ const GLOBE_MOON = 'http://www.wikidata.org/entity/Q405';

	/**
	 * @dataProvider globeCoordinateFormatProvider
	 */
	public function testFormat( GlobeCoordinateValue $value, $output, $withKartographer ) {
		$this->assertEquals( $output, $this->newFormatter( $withKartographer )->format( $value ) );
	}

	private function newFormatter( $enableKartographer ) {
		return new WikitextGlobeCoordinateFormatter(
			new GlobeCoordinateFormatter(),
			new FormatterOptions(
				[
					WikitextGlobeCoordinateFormatter::OPT_ENABLE_KARTOGRAPHER => $enableKartographer
				]
			)
		);
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
					self::GLOBE_MOON
				),
				'1, 2',
				true
			],
			'moon without kartographer' => [
				new GlobeCoordinateValue(
					new LatLongValue( 1, 2 ),
					0.1,
					self::GLOBE_MOON
				),
				'1, 2',
				false
			],
		];
	}

}
