<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use MediaWiki\Html\Html;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWikiIntegrationTestCase;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class CachingKartographerEmbeddingHandlerTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'Kartographer' );
		$this->overrideConfigValue( 'KartographerMapServer', 'http://192.0.2.0' );
	}

	public function testGetHtml() {
		$handler = new CachingKartographerEmbeddingHandler( $this->getServiceContainer()->getParserFactory()->create() );

		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );
		$result = $handler->getHtml( $this->newSampleCoordinate(), $language, new FormatterOptions( [] ) );

		$this->assertStringContainsString( 'mw-kartographer-map', $result );
		$this->assertStringContainsString( 'data-lat="50"', $result );
		// FIXME: This looks somewhat bogus, do we need to fix this as well?
		$this->assertStringContainsString( 'data-lon="1.1E-5"', $result );
		$this->assertStringStartsWith( '<div', $result );
		$this->assertStringEndsWith( '</div>', $result );
	}

	public function testGetHtml_cached() {
		$parser = $this->createMock( Parser::class );

		$parser->expects( $this->once() )
			->method( 'parse' )
			->willReturn( $this->createMock( ParserOutput::class ) );

		$handler = new CachingKartographerEmbeddingHandler( $parser );
		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );

		$handler->getHtml( $this->newSampleCoordinate(), $language, new FormatterOptions( [] ) );

		// This should be cached and not trigger Parser::parse() a second time
		$handler->getHtml( $this->newSampleCoordinate(), $language, new FormatterOptions( [] ) );
	}

	public function testGetHtml_variableWidth() {
		$handler = new CachingKartographerEmbeddingHandler( $this->getServiceContainer()->getParserFactory()->create() );

		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );
		$result = $handler->getHtml( $this->newSampleCoordinate(), $language, new FormatterOptions( [
			CachingKartographerEmbeddingHandler::OPT_KARTOGRAPHER_VARIABLE_WIDTH => true,
		] ) );

		$this->assertStringContainsString( 'width: 100%;', $result );
		$this->assertStringContainsString( 'mw-kartographer-map', $result );
		$this->assertStringContainsString( 'data-lat="50"', $result );
		// FIXME: This looks somewhat bogus, do we need to fix this as well?
		$this->assertStringContainsString( 'data-lon="1.1E-5"', $result );
		$this->assertStringStartsWith( '<div', $result );
		$this->assertStringEndsWith( '</div>', $result );
	}

	public function testGetHtml_marsCoordinate() {
		$handler = new CachingKartographerEmbeddingHandler( $this->getServiceContainer()->getParserFactory()->create() );
		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );

		$this->assertFalse(
			$handler->getHtml( $this->newSampleMarsCoordinate(), $language, new FormatterOptions( [] ) )
		);
	}

	public function testGetPreviewHtml() {
		$handler = new CachingKartographerEmbeddingHandler( $this->getServiceContainer()->getParserFactory()->create() );

		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );
		$value = $this->newSampleCoordinate();

		$plainHtml = $handler->getHtml( $value, $language, new FormatterOptions( [] ) );
		$result = $handler->getPreviewHtml( $value, $language, new FormatterOptions( [] ) );

		// Preview HTML should contain the regular html
		$this->assertStringContainsString( $plainHtml, $result );
		$this->assertStringStartsWith( '<div id="wb-globeCoordinateValue-preview-', $result );
		$this->assertStringContainsString( 'wgKartographerLiveData', $result );
		$this->assertStringContainsString( 'initMapframeFromElement', $result );
	}

	public function testGetPreviewHtml_variableWidth() {
		$handler = new CachingKartographerEmbeddingHandler( $this->getServiceContainer()->getParserFactory()->create() );

		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );
		$value = $this->newSampleCoordinate();

		$plainHtml = $handler->getHtml( $value, $language, new FormatterOptions( [
			CachingKartographerEmbeddingHandler::OPT_KARTOGRAPHER_VARIABLE_WIDTH => true,
		] ) );
		$result = $handler->getPreviewHtml( $value, $language, new FormatterOptions( [
			CachingKartographerEmbeddingHandler::OPT_KARTOGRAPHER_VARIABLE_WIDTH => true,
		] ) );

		// Preview HTML should contain the regular html
		$this->assertStringContainsString( $plainHtml, $result );
		$this->assertStringStartsWith( '<div id="wb-globeCoordinateValue-preview-', $result );
		$this->assertStringContainsString( 'width: 100%;', $result );
		$this->assertStringContainsString( 'wgKartographerLiveData', $result );
		$this->assertStringContainsString( 'initMapframeFromElement', $result );
	}

	public function testGetPreviewHtml_marsCoordinate() {
		$handler = new CachingKartographerEmbeddingHandler( $this->getServiceContainer()->getParserFactory()->create() );
		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );

		$this->assertFalse(
			$handler->getPreviewHtml( $this->newSampleMarsCoordinate(), $language, new FormatterOptions( [] ) )
		);
	}

	public function testGetParserOutput() {
		$handler = new CachingKartographerEmbeddingHandler( $this->getServiceContainer()->getParserFactory()->create() );
		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );
		$coordinate = new GlobeCoordinateValue(
			new LatLongValue( 12, 34 ),
			1,
			GlobeCoordinateValue::GLOBE_EARTH
		);

		$parserOutput = $handler->getParserOutput(
			[
				$this->newSampleCoordinate(),
				$this->newSampleMarsCoordinate(),
				$coordinate,
			],
			$language,
			false,
		);

		$this->assertInstanceOf( ParserOutput::class, $parserOutput );
		$this->assertNotNull( $parserOutput->getExtensionData( 'kartographer' ) );

		// This is sometimes an object, see \Kartographer\Tag\TagHandler::finalParseStep()
		$this->assertCount( 2, (array)$parserOutput->getJsConfigVars()['wgKartographerLiveData'] );
		$this->assertNotEmpty( $parserOutput->getModules() );
	}

	public function testGetParserOutput_variableWidth() {
		$mockParser = $this->createMock( Parser::class );
		$handler = new CachingKartographerEmbeddingHandler( $mockParser );
		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );
		$coordinate = new GlobeCoordinateValue(
			new LatLongValue( 12, 34 ),
			1,
			GlobeCoordinateValue::GLOBE_EARTH
		);
		$mockParser->expects( $this->once() )
			->method( 'parse' )
			->with( $this->stringContains( 'width="full"' ) )
			->willReturn( $this->createMock( ParserOutput::class ) );

		$handler->getParserOutput(
			[
				$this->newSampleCoordinate(),
				$this->newSampleMarsCoordinate(),
				$coordinate,
			],
			$language,
			true,
		);
	}

	public function testGetParserOutput_empty() {
		$handler = new CachingKartographerEmbeddingHandler( $this->getServiceContainer()->getParserFactory()->create() );
		$language = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'qqx' );

		$parserOutput = $handler->getParserOutput(
			[
				$this->newSampleMarsCoordinate(),
			],
			$language,
			false,
		);

		$this->assertInstanceOf( ParserOutput::class, $parserOutput );
		$this->assertNull( $parserOutput->getExtensionData( 'kartographer' ) );
	}

	public function testGetMapframeInitJS() {
		$handler = new CachingKartographerEmbeddingHandler( $this->getServiceContainer()->getParserFactory()->create() );
		/** @var CachingKartographerEmbeddingHandler $handler */
		$handler = TestingAccessWrapper::newFromObject( $handler );

		$html = $handler->getMapframeInitJS(
			'foo',
			[ 'rl-module-1', 'another-rl-module' ],
			[ 'maps' => 'awesome' ]
		);

		$this->assertStringStartsWith( '<script>', $html );
		$this->assertStringEndsWith( '</script>', $html );
		$this->assertStringContainsString(
			'mw.config.get( \'wgKartographerLiveData\' )["maps"] = "awesome"',
			$html
		);
		$this->assertStringContainsString( '["rl-module-1","another-rl-module","ext.kartographer.frame"]', $html );
		$this->assertStringContainsString( '( "#foo" )', $html );
	}

	public function testGetMapframeInitJS_escaping() {
		$handler = new CachingKartographerEmbeddingHandler( $this->getServiceContainer()->getParserFactory()->create() );
		/** @var CachingKartographerEmbeddingHandler $handler */
		$handler = TestingAccessWrapper::newFromObject( $handler );

		$html = $handler->getMapframeInitJS(
			'f"o"o',
			[ 'rl-"mo"dule' ],
			[ 'm"a"ps' => 'awe"s"ome' ]
		);

		$this->assertStringStartsWith( '<script>', $html );
		$this->assertStringEndsWith( '</script>', $html );

		$stringsToEscape = [ '#f"o"o', 'rl-"mo"dule', 'm"a"ps', 'awe"s"ome' ];

		foreach ( $stringsToEscape as $str ) {
			$this->assertStringNotContainsString( $str, $html );
			$this->assertStringContainsString( Html::encodeJsVar( $str ), $html );
		}
	}

	private function newSampleCoordinate() {
		return new GlobeCoordinateValue(
			new LatLongValue( 50, 1.1E-5 ),
			5,
			GlobeCoordinateValue::GLOBE_EARTH
		);
	}

	private function newSampleMarsCoordinate() {
		return new GlobeCoordinateValue(
			new LatLongValue( 50, 1.1E-5 ),
			5,
			'http://www.wikidata.org/entity/Q111'
		);
	}

}
