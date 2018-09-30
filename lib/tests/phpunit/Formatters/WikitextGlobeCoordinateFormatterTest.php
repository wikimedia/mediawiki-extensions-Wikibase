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

	private function newGlobeCoordinateValue( $globe ) {
		return new GlobeCoordinateValue(
			new LatLongValue( 1, 2 ),
			0.1,
			$globe
		);
	}

	public function globeCoordinateFormatProvider() {
		return [
			'earth with kartographer' => [
				$this->newGlobeCoordinateValue( GlobeCoordinateValue::GLOBE_EARTH ),
				'<maplink latitude="1" longitude="2">{"type":"Feature","geometry":{"type":"Point","coordinates":[2,1]}}</maplink>',
				true
			],
			'earth without kartographer' => [
				$this->newGlobeCoordinateValue( GlobeCoordinateValue::GLOBE_EARTH ),
				'1, 2',
				false
			],
			'moon with kartographer' => [
				$this->newGlobeCoordinateValue( self::GLOBE_MOON ),
				'1, 2',
				true
			],
			'moon without kartographer' => [
				$this->newGlobeCoordinateValue( self::GLOBE_MOON ),
				'1, 2',
				false
			],
		];
	}

}
