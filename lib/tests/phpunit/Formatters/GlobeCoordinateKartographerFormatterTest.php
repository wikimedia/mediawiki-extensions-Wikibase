<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Language;
use MediaWiki\MediaWikiServices;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\Formatters\GlobeCoordinateKartographerFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\GlobeCoordinateKartographerFormatter
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class GlobeCoordinateKartographerFormatterTest extends \PHPUnit\Framework\TestCase {

	public function emitPreviewHtmlProvider() {
		yield [ true ];
		yield [ false ];
	}

	/**
	 * @dataProvider emitPreviewHtmlProvider
	 */
	public function testFormat( $emitPreviewHtml ) {
		$formatter = new GlobeCoordinateKartographerFormatter(
			new FormatterOptions(),
			$this->newBaseValueFormatter( 1 ),
			$this->newCachingKartographerEmbeddingHandler( 1, $emitPreviewHtml ),
			MediaWikiServices::getInstance()->getLanguageFactory(),
			$emitPreviewHtml
		);

		$html = $formatter->format( $this->newSampleCoordinate() );
		$this->assertSame(
			'<div><kartographer-html/><div class="wikibase-kartographer-caption"><base-formatter-html/></div></div>',
			$html
		);
	}

	public function testFormat_marsCoordinate() {
		$formatter = new GlobeCoordinateKartographerFormatter(
			new FormatterOptions(),
			$this->newBaseValueFormatter( 1 ),
			$this->newCachingKartographerEmbeddingHandler( 1, false, false ),
			MediaWikiServices::getInstance()->getLanguageFactory(),
			false
		);

		$html = $formatter->format( $this->newSampleMarsCoordinate() );
		$this->assertSame(
			'<div><div class="wikibase-kartographer-caption"><base-formatter-html/></div></div>',
			$html
		);
	}

	public function testFormat_invalidValue() {
		$formatter = new GlobeCoordinateKartographerFormatter(
			new FormatterOptions(),
			$this->newBaseValueFormatter( 0 ),
			$this->newCachingKartographerEmbeddingHandler( 0 ),
			MediaWikiServices::getInstance()->getLanguageFactory(),
			false
		);

		$this->expectException( InvalidArgumentException::class );
		$formatter->format( new StringValue( 'A string is not a coordinate?!' ) );
	}

	private function newCachingKartographerEmbeddingHandler(
		$totalCalls,
		$isPreview = false,
		$returnValue = '<kartographer-html/>'
	) {
		$handler = $this->createMock( CachingKartographerEmbeddingHandler::class );

		$handler->expects( $this->exactly( $totalCalls ) )
			->method( $isPreview ? 'getPreviewHtml' : 'getHtml' )
			->with(
				$this->isInstanceOf( GlobeCoordinateValue::class ),
				$this->isInstanceOf( Language::class )
			)
			->willReturn( $returnValue );

		$handler->expects( $this->never() )
			->method( $isPreview ? 'getHtml' : 'getPreviewHtml' );

		return $handler;
	}

	private function newBaseValueFormatter( $expectedFormatCalls ) {
		$formatter = $this->createMock( ValueFormatter::class );

		$formatter->expects( $this->exactly( $expectedFormatCalls ) )
			->method( 'format' )
			->with( $this->isInstanceOf( GlobeCoordinateValue::class ) )
			->willReturn( '<base-formatter-html/>' );

		return $formatter;
	}

	private function newSampleCoordinate() {
		return new GlobeCoordinateValue(
			new LatLongValue( 50, 11 ),
			1,
			GlobeCoordinateValue::GLOBE_EARTH
		);
	}

	private function newSampleMarsCoordinate() {
		return new GlobeCoordinateValue(
			new LatLongValue( 50, 11 ),
			1,
			'http://www.wikidata.org/entity/Q111'
		);
	}

}
