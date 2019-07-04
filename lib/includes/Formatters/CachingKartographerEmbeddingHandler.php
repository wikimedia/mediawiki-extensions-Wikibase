<?php

namespace Wikibase\Lib\Formatters;

use DataValues\Geo\Values\GlobeCoordinateValue;
use FormatJson;
use Html;
use InvalidArgumentException;
use Language;
use MapCacheLRU;
use Parser;
use ParserOptions;
use ParserOutput;
use Title;

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
		$out = new ParserOutput();
		// Clear the state initially (but only once)
		$clearState = true;

		$title = Title::newFromText( 'Special:BlankPage' );
		$parserOptions = new ParserOptions( null, $language );
		foreach ( $values as $value ) {
			if ( $value->getGlobe() !== GlobeCoordinateValue::GLOBE_EARTH ) {
				continue;
			}

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

		// ext.kartographer.frame contains initMapframeFromElement (which we use below)
		$rlModules[] = 'ext.kartographer.frame';
		$rlModulesArr = array_unique( $rlModules );

		$rlModulesJson = FormatJson::encode( $rlModulesArr );
		$jsMapPreviewId = FormatJson::encode( '#' . $mapPreviewId );

		// Require all needed RL modules, then call initMapframeFromElement with the injected mapframe HTML
		$javaScript .= "mw.loader.using( $rlModulesJson ).then( " .
				"function() { mw.loader.require( 'ext.kartographer.frame' ).initMapframeFromElement( " .
				"\$( $jsMapPreviewId ).find( '.mw-kartographer-map[data-mw=\"interface\"]' ).get( 0 ) ); } );";

		return Html::inlineScript( $javaScript );
	}

	/**
	 * Get JavaScript code to update/init "wgKartographerLiveData" with the given data.
	 *
	 * @param array $kartographerLiveData
	 * @return string JavaScript code
	 */
	private function getMWConfigJS( array $kartographerLiveData ) {
		// Create an empty wgKartographerLiveData, if needed
		$javaScript = "if ( !mw.config.exists( 'wgKartographerLiveData' ) ) { mw.config.set( 'wgKartographerLiveData', {} ); }";

		// Append $kartographerLiveData to wgKartographerLiveData, as we can't overwrite wgKartographerLiveData
		// here, as it is already referenced, also we probably don't want to loose other entries
		foreach ( $kartographerLiveData as $key => $value ) {
			$jsKey = FormatJson::encode( (string)$key );
			$jsValue = FormatJson::encode( $value );

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
