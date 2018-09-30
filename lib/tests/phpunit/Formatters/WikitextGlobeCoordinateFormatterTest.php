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

	public function testWhenFormattingEarthCoordinatesWithoutKartographer_plainCoordinatesAreReturned() {
		$this->assertSame(
			'1, 2',
			$this->newFormatterWithoutKartographer()->format(
				$this->newGlobeCoordinate( GlobeCoordinateValue::GLOBE_EARTH )
			)
		);
	}

	public function testWhenFormattingEarthCoordinatesWithKartographer_mapLinkIsReturned() {
		$this->assertSame(
			'<maplink latitude="1" longitude="2">{"type":"Feature","geometry":{"type":"Point","coordinates":[2,1]}}</maplink>',
			$this->newFormatterWithKartographer()->format(
				$this->newGlobeCoordinate( GlobeCoordinateValue::GLOBE_EARTH )
			)
		);
	}

	public function testWhenFormattingNonEarthCoordinatesWithKartographer_plainCoordinatesAreReturned() {
		$this->assertSame(
			'1, 2',
			$this->newFormatterWithKartographer()->format(
				$this->newGlobeCoordinate( self::GLOBE_MOON )
			)
		);
	}

	private function newFormatterWithKartographer(): WikitextGlobeCoordinateFormatter {
		return $this->newFormatter( true );
	}

	private function newFormatterWithoutKartographer(): WikitextGlobeCoordinateFormatter {
		return $this->newFormatter( false );
	}

	private function newFormatter( $enableKartographer ): WikitextGlobeCoordinateFormatter {
		return new WikitextGlobeCoordinateFormatter(
			new GlobeCoordinateFormatter(),
			new FormatterOptions(
				[
					WikitextGlobeCoordinateFormatter::OPT_ENABLE_KARTOGRAPHER => $enableKartographer
				]
			)
		);
	}

	private function newGlobeCoordinate( $globe ): GlobeCoordinateValue {
		return new GlobeCoordinateValue(
			new LatLongValue( 1, 2 ),
			0.1,
			$globe
		);
	}

}
