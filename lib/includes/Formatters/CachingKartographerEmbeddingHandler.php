<?php

namespace Wikibase\Lib;

use DataValues\Geo\Values\GlobeCoordinateValue;
use InvalidArgumentException;
use Language;
use MapCacheLRU;
use Parser;
use ParserOptions;
use ParserOutput;
use Title;
use Xml;

/**
 * Service for embedding Kartographer mapframes for GlobeCoordinateValues.
 *
 * Use getParserOutput with ALL GlobeCoordinateValues on a page to get metadata
 * needed to display the mapframes properly.
 * Use getHtml for getting the HTML for a specific GlobeCoordinateValue.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class CachingKartographerEmbeddingHandler {

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var MapCacheLRU
	 */
	private $cache;

	/**
	 * @param Parser $parser
	 */
	public function __construct( Parser $parser ) {
		$this->parser = $parser;
		$this->cache = new MapCacheLRU( 100 );
	}

	/**
	 * @param GlobeCoordinateValue $value
	 * @param Language $language
	 *
	 * @throws InvalidArgumentException
	 * @return string|bool Html, false if the given value could not be rendered
	 */
	public function getHtml( GlobeCoordinateValue $value, Language $language ) {
		if ( $value->getGlobe() !== GlobeCoordinateValue::GLOBE_EARTH ) {
			return false;
		}

		$cacheKey = $this->getCacheKey( $value, $language );
		if ( !$this->cache->has( $cacheKey ) ) {
			$this->getParserOutput( [ $value ], $language );
		}
		return $this->cache->get( $cacheKey );
	}

	/**
	 * Get HTML for a Kartographer map, that can be injected into a MediaWiki page on
	 * demand (for live previews).
	 *
	 * @param GlobeCoordinateValue $value
	 * @param Language $language
	 *
	 * @throws InvalidArgumentException
	 * @return string|bool Html, false if the given value could not be rendered
	 */
	public function getPreviewHtml( GlobeCoordinateValue $value, Language $language ) {
		if ( $value->getGlobe() !== GlobeCoordinateValue::GLOBE_EARTH ) {
			return false;
		}

		$out = $this->getParserOutput( [ $value ], $language );

		$containerDivId = 'wb-globeCoordinateValue-preview-' . base_convert( mt_rand( 1, PHP_INT_MAX ), 10, 36 );

		$html = '<div id="' . $containerDivId . '">' . $out->getText() . '</div>';
		$html .= $this->getMapframeInitJS(
			$containerDivId,
			$out->getModules(),
			(array)$out->getJsConfigVars()['wgKartographerLiveData']
		);

		return $html;
	}

	/**
	 * Get a ParserOutput with metadata for all the given GlobeCoordinateValues.
	 *
	 * ATTENTION: This ParserOutput will generally only contain metadata, for getting
	 * the html for a certain GlobeCoordinateValue, please use self::getHtml()
	 *
	 * @param GlobeCoordinateValue[] $values
	 * @param Language $language
	 * @return ParserOutput
	 */
	public function getParserOutput( array $values, Language $language ) {
		// Clear the state initially (but only once)
		$clearState = true;

		$title = Title::newFromText( 'Special:BlankPage' );
		$parserOptions = new ParserOptions( null, $language );
		foreach ( $values as $value ) {
			if ( $value->getGlobe() !== GlobeCoordinateValue::GLOBE_EARTH ) {
				continue;
			}

			file_put_contents( '/tmp/getParserOutput.log', "Parsed!\n", FILE_APPEND );
			$out = $this->parser->parse(
				$this->getWikiText( $value ),
				$title,
				$parserOptions,
				/* $lineStart */ true,
				/* $clearState */ $clearState
			);
			$clearState = false;

			$this->cache->set( $this->getCacheKey( $value, $language ), $out->getText() );
		}

		return $out;
	}

	/**
	 * @param GlobeCoordinateValue $value
	 * @param Language $language
	 * @return string
	 */
	private function getCacheKey( GlobeCoordinateValue $value, Language $language ) {
		return $value->getHash() . '#' . $language->getCode();
	}

	/**
	 * Get a <script> code block that initializes a mapframe.
	 *
	 * @param string $mapPreviewId Id of the container containing the map
	 * @param string[] $rlModules RL modules to load
	 * @param array $kartographerLiveData
	 * @return string HTML
	 */
	private function getMapframeInitJS( $mapPreviewId, array $rlModules, array $kartographerLiveData ) {
		$javaScript = $this->getMWConfigJS( $kartographerLiveData );

		// ext.kartographer.frame contains mw.kartographer.initMapframeFromElement (which we use below)
		$rlModules[] = 'ext.kartographer.frame';
		$rlModules = array_unique( $rlModules );

		$JSRlModules = join(
			', ',
			array_map(
				function( $rlModuleName ) {
					return Xml::encodeJsVar( $rlModuleName );
				},
				$rlModules
			)
		);
		$jsMapPreviewId = Xml::encodeJsVar( '#' . $mapPreviewId );

		// Require all needed RL modules, than fire the "wikipage.content" hook for the new JS container
		$javaScript .= "mw.loader.using( [ $JSRlModules ], " .
				"function() { mw.kartographer.initMapframeFromElement( " .
				"\$( $jsMapPreviewId ).find( '.mw-kartographer-map' ).get( 0 ) ); } );";

		return '<script type="text/javascript">' . $javaScript . '</script>';
	}

	/**
	 * Get JavaScript code to update/init "wgKartographerLiveData" with the given data.
	 *
	 * @param array $kartographerLiveData
	 * @return string JavaScript
	 */
	private function getMWConfigJS( array $kartographerLiveData ) {
		// Create an empty wgKartographerLiveData, if needed
		$javaScript = "if ( !mw.config.exists( 'wgKartographerLiveData' ) ) { mw.config.set( 'wgKartographerLiveData', {} ); }";

		// Append $kartographerLiveData to wgKartographerLiveData, as we can't overwrite wgKartographerLiveData
		// here, as it is already referenced, also we probably don't want to loose other entries
		foreach ( $kartographerLiveData as $key => $value ) {
			$jsKey = Xml::encodeJsVar( $key );
			$jsValue = Xml::encodeJsVar( $value );

			$javaScript .= "mw.config.get( 'wgKartographerLiveData' )[$jsKey] = $jsValue;";
		}

		return $javaScript;
	}

	/**
	 * Get the mapframe wikitext for a given GlobeCoordinateValue.
	 *
	 * @param GlobeCoordinateValue $value
	 * @return string wikitext
	 */
	private function getWikiText( GlobeCoordinateValue $value ) {
		return '<mapframe width="310" height="180" zoom="13" latitude="' .
			$value->getLatitude() . '" longitude="' . $value->getLongitude() . '" frameless align="left">
			{
			"type": "Feature",
			"geometry": { "type": "Point", "coordinates": [' . $value->getLongitude() . ', ' . $value->getLatitude() . '] },
			"properties": {
			  "marker-symbol": "marker",
			  "marker-size": "large",
			  "marker-color": "0050d0"
			}
		  }
		  </mapframe>';
	}

}
