<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use ExtensionRegistry;
use Language;
use Parser;
use ParserOutput;
use Wikibase\Lib\CachingKartographerEmbeddingHandler;
use Wikimedia\TestingAccessWrapper;
use Xml;

/**
 * @covers \Wikibase\Lib\CachingKartographerEmbeddingHandler
 *
 * @group Wikibase
 * @group NotLegitUnitTest
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class CachingKartographerEmbeddingHandlerTest extends \MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Kartographer' ) ) {
			$this->markTestSkipped( 'Kartographer not installed' );
		}
	}

	public function testGetHtml() {
		$handler = new CachingKartographerEmbeddingHandler( new Parser );

		$language = Language::factory( 'qqx' );
		$result = $handler->getHtml( $this->newSampleCoordinate(), $language );

		$this->assertContains( 'mw-kartographer-map', $result );
		$this->assertContains( 'data-lat="50', $result );
		$this->assertContains( 'data-lon="11', $result );
		$this->assertStringStartsWith( '<div', $result );
		$this->assertStringEndsWith( '</div>', $result );
	}

	public function testGetHtml_cached() {
		$parser = $this->getMockBuilder( Parser::class )
			->enableProxyingToOriginalMethods()
			->getMock();

		$parser->expects( $this->once() )
			->method( 'parse' );

		$handler = new CachingKartographerEmbeddingHandler( $parser );
		$language = Language::factory( 'qqx' );

		$handler->getParserOutput(
			[
				$this->newSampleCoordinate()
			],
			$language
		);

		$result = $handler->getHtml( $this->newSampleCoordinate(), $language );
		$this->assertContains( 'mw-kartographer-map', $result );
	}

	public function testGetHtml_marsCoordinate() {
		$handler = new CachingKartographerEmbeddingHandler( new Parser );
		$language = Language::factory( 'qqx' );

		$this->assertFalse(
			$handler->getHtml( $this->newSampleMarsCoordinate(), $language )
		);
	}

	public function testGetPreviewHtml() {
		$handler = new CachingKartographerEmbeddingHandler( new Parser );

		$language = Language::factory( 'qqx' );
		$value = $this->newSampleCoordinate();

		$plainHtml = $handler->getHtml( $value, $language );
		$result = $handler->getPreviewHtml( $value, $language );

		// Preview HTML should contain the regular html
		$this->assertContains( $plainHtml, $result );
		$this->assertStringStartsWith( '<div id="wb-globeCoordinateValue-preview-', $result );
		$this->assertContains( 'wgKartographerLiveData', $result );
		$this->assertContains( 'initMapframeFromElement', $result );
	}

	public function testGetPreviewHtml_marsCoordinate() {
		$handler = new CachingKartographerEmbeddingHandler( new Parser );
		$language = Language::factory( 'qqx' );

		$this->assertFalse(
			$handler->getPreviewHtml( $this->newSampleMarsCoordinate(), $language )
		);
	}

	public function testGetParserOutput() {
		$handler = new CachingKartographerEmbeddingHandler( new Parser );
		$language = Language::factory( 'qqx' );
		$coordinate = new GlobeCoordinateValue(
			new LatLongValue( 12, 34 ),
			1,
			GlobeCoordinateValue::GLOBE_EARTH
		);

		$out = $handler->getParserOutput(
			[
				$this->newSampleCoordinate(),
				$this->newSampleMarsCoordinate(),
				$coordinate
			],
			$language
		);

		$this->assertInstanceOf( ParserOutput::class, $out );
		$this->assertNotFalse( $out->getProperty( 'kartographer' ) );
		$this->assertNotFalse( $out->getProperty( 'kartographer_frames' ) );

		$this->assertCount( 2, (array)$out->getJsConfigVars()['wgKartographerLiveData'] );
		$this->assertNotEmpty( $out->getModules() );
	}

	public function testGetParserOutput_empty() {
		$handler = new CachingKartographerEmbeddingHandler( new Parser );
		$language = Language::factory( 'qqx' );

		$out = $handler->getParserOutput(
			[
				$this->newSampleMarsCoordinate()
			],
			$language
		);

		$this->assertInstanceOf( ParserOutput::class, $out );
		$this->assertFalse( $out->getProperty( 'kartographer' ) );
		$this->assertFalse( $out->getProperty( 'kartographer_frames' ) );
	}

	public function testGetMapframeInitJS() {
		$handler = new CachingKartographerEmbeddingHandler( new Parser );
		$handler = TestingAccessWrapper::newFromObject( $handler );

		$html = $handler->getMapframeInitJS(
			'foo',
			[ 'rl-module-1', 'another-rl-module' ],
			[ 'maps' => 'awesome' ]
		);

		$this->assertStringStartsWith( '<script type="text/javascript">', $html );
		$this->assertStringEndsWith( '</script>', $html );
		$this->assertContains(
			'mw.config.get( \'wgKartographerLiveData\' )["maps"] = "awesome"',
			$html
		);
		$this->assertContains( '[ "rl-module-1", "another-rl-module", "ext.kartographer.frame" ]', $html );
		$this->assertContains( '( "#foo" )', $html );
	}

	public function testGetMapframeInitJS_escaping() {
		$handler = new CachingKartographerEmbeddingHandler( new Parser );
		$handler = TestingAccessWrapper::newFromObject( $handler );

		$html = $handler->getMapframeInitJS(
			'f"o"o',
			[ 'rl-"mo"dule' ],
			[ 'm"a"ps' => 'awe"s"ome' ]
		);

		$this->assertStringStartsWith( '<script type="text/javascript">', $html );
		$this->assertStringEndsWith( '</script>', $html );

		$stringsToEscape = [ '#f"o"o', 'rl-"mo"dule', 'm"a"ps', 'awe"s"ome' ];

		foreach ( $stringsToEscape as $str ) {
			$this->assertNotContains( $str, $html );
			$this->assertContains( Xml::encodeJsVar( $str ), $html );
		}
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
