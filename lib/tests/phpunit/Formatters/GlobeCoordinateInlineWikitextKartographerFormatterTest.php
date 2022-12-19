<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\Geo\Formatters\GlobeCoordinateFormatter;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use Wikibase\Lib\Formatters\GlobeCoordinateInlineWikitextKartographerFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\GlobeCoordinateInlineWikitextKartographerFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 */
class GlobeCoordinateInlineWikitextKartographerFormatterTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider globeCoordinateFormatProvider
	 */
	public function testFormat( GlobeCoordinateValue $value, $output ) {
		$formatter = new GlobeCoordinateInlineWikitextKartographerFormatter(
			new GlobeCoordinateFormatter()
		);
		$this->assertEquals( $output, $formatter->format( $value ) );
	}

	public function globeCoordinateFormatProvider() {
		return [
			'earth' => [
				new GlobeCoordinateValue(
					new LatLongValue( 1, 2 ),
					0.1,
					GlobeCoordinateValue::GLOBE_EARTH
				),
				'<maplink latitude="1" longitude="2">{"type":"Feature","geometry":{"type":"Point","coordinates":[2,1]}}</maplink>',
			],
			'moon' => [
				new GlobeCoordinateValue(
					new LatLongValue( 1, 2 ),
					0.1,
					'http://www.wikidata.org/entity/Q405'
				),
				'1, 2',
			],
		];
	}

}
