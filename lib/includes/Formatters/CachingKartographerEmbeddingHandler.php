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
	 * @var bool
	 */
	private $createPreviewHtml;

	/**
	 * @param Parser $parser
	 * @param bool $createPreviewHtml If this is set, html that can be injected into
	 *		a MediaWiki page at any time will be produced.
	 */
	public function __construct( Parser $parser, $createPreviewHtml ) {
		$this->parser = $parser;
		$this->cache = new MapCacheLRU( 100 );
		$this->createPreviewHtml = $createPreviewHtml;
	}

	/**
	 * @param GlobeCoordinateValue $value
	 * @param Language $language
	 *
	 * @throws InvalidArgumentException
	 * @return ParserOutput|false Returns false for non-earth coordinates
	 */
	public function getHtml( GlobeCoordinateValue $value, Language $language ) {
		if ( $value->getGlobe() !== GlobeCoordinateValue::GLOBE_EARTH ) {
			return false;
		}

		$cacheKey = $value->getHash() . '#' . $language->getCode();
		if ( $this->cache->has( $cacheKey ) ) {
			return $this->cache->get( $cacheKey );
		}

		$parserOptions = new ParserOptions( null, $language );
		$out = $this->parser->parse(
			$this->getWikiText( $value ),
			Title::newFromText( 'Special:BlankPage' ),
			$parserOptions
		);

		if ( $this->createPreviewHtml && isset( $out->getJsConfigVars()['wgKartographerLiveData'] ) ) {
			$containerDivId = 'wb-globeCoordinateValue-preview-' . base_convert( mt_rand( 1, PHP_INT_MAX ), 10, 36 );

			$html = "<div id='$containerDivId'>" . $out->getText() . '</div>';
			$html .= $this->getPreviewInitJS(
				$containerDivId,
				$out->getModules(),
				(array)$out->getJsConfigVars()['wgKartographerLiveData']
			);
		} else {
			$html = $out->getText();
		}

		$this->cache->set( $cacheKey, $html );
		return $html;
	}

	/**
	 * Get a ParserOutput with metadata for all the given GlobeCoordinateValues.
	 *
	 * ATTENTION: This ParserOutput will only contain metadata, for getting
	 * the html for a certain GlobeCoordinateValue, please use self::getHtml()
	 *
	 * @param GlobeCoordinateValue[] $values
	 * @param Language $language
	 * @return ParserOutput
	 */
	public function getParserOutput( $values, Language $language  ) {
		// Clear the state initially (but only once)
		$clearState = true;

		$title = Title::newFromText( 'Special:BlankPage' );
		$parserOptions = new ParserOptions( null, $language );
		foreach ( $values as $value ) {
			if ( $value->getGlobe() !== GlobeCoordinateValue::GLOBE_EARTH ) {
				continue;
			}

			$cacheKey = $value->getHash() . '#' . $language->getCode();

			$out = $this->parser->parse(
				$this->getWikiText( $value ),
				$title,
				$parserOptions,
				/* $lineStart */ true,
				/* $clearState */ $clearState
			);
			$clearState = false;

			$this->cache->set( $cacheKey, $out->getText() );
		}

		return $out;
	}

	/**
	 * Get a <script> code block that initializes a mapframe.
	 *
	 * @param string $mapPreviewId Id of the container containing the map
	 * @param string[] $rlModules RL modules to load
	 * @param array $kartographerLiveData
	 * @return string HTML
	 */
	private function getPreviewInitJS( $mapPreviewId, array $rlModules, array $kartographerLiveData ) {
		// Create an empty wgKartographerLiveData, if needed
		$javaScript = "if ( !mw.config.exists( 'wgKartographerLiveData' ) ) { mw.config.set( 'wgKartographerLiveData', {} ); }";

		// Append $kartographerLiveData to wgKartographerLiveData, as we can't overwrite wgKartographerLiveData
		// here, as it is already referenced, also we probably don't want to loose other entries
		foreach ( $kartographerLiveData as $key => $value ) {
			$jsKey = Xml::encodeJsVar( $key );
			$jsValue = Xml::encodeJsVar( $value );

			$javaScript .= "mw.config.get( 'wgKartographerLiveData' )[$jsKey] = $jsValue;";
		}

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
				"function() { mw.hook( 'wikipage.content' ).fire( $( $jsMapPreviewId ) ); } );";

		return "<script type='text/javascript'>" . $javaScript . '</script>';
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
