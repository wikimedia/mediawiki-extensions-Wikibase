<?php

namespace Wikibase\Lib\Formatters;

use DataValues\Geo\Values\GlobeCoordinateValue;
use InvalidArgumentException;
use MediaWiki\Context\RequestContext;
use MediaWiki\Html\Html;
use MediaWiki\Json\FormatJson;
use MediaWiki\Language\Language;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use ValueFormatters\FormatterOptions;
use Wikimedia\ObjectCache\MapCacheLRU;

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

	public const OPT_KARTOGRAPHER_VARIABLE_WIDTH = 'kartographer-variable-width';

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var MapCacheLRU
	 */
	private $cache;

	public function __construct(
		Parser $parser
	) {
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
	public function getHtml( GlobeCoordinateValue $value, Language $language, FormatterOptions $formatterOptions ) {
		if ( $value->getGlobe() !== GlobeCoordinateValue::GLOBE_EARTH ) {
			return false;
		}

		$variableWidth = $formatterOptions->hasOption( self::OPT_KARTOGRAPHER_VARIABLE_WIDTH );
		$cacheKey = $this->getCacheKey( $value, $language, $variableWidth );
		if ( !$this->cache->has( $cacheKey ) ) {
			$parserOptions = $this->getParserOptions( $language );
			$parserOutput = $this->parser->parse(
				$this->getWikiText( $value, $variableWidth ),
				RequestContext::getMain()->getTitle() ?? Title::makeTitle( NS_SPECIAL, 'BlankPage' ),
				$parserOptions
			);
			$this->cache->set(
				$this->getCacheKey( $value, $language, $variableWidth ),
				$parserOutput->runOutputPipeline( $parserOptions, [] )->getContentHolderText()
			);
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
	public function getPreviewHtml( GlobeCoordinateValue $value, Language $language, FormatterOptions $formatterOptions ) {
		if ( $value->getGlobe() !== GlobeCoordinateValue::GLOBE_EARTH ) {
			return false;
		}

		$variableWidth = $formatterOptions->hasOption( self::OPT_KARTOGRAPHER_VARIABLE_WIDTH );
		$parserOutput = $this->getParserOutput( [ $value ], $language, $variableWidth );

		$containerDivId = 'wb-globeCoordinateValue-preview-' . base_convert( (string)mt_rand( 1, PHP_INT_MAX ), 10, 36 );

		$html = '<div id="' . $containerDivId . '">' . $parserOutput->getContentHolderText() . '</div>';
		$html .= $this->getMapframeInitJS(
			$containerDivId,
			$parserOutput->getModules(),
			(array)( $parserOutput->getJsConfigVars()['wgKartographerLiveData'] ?? [] )
		);

		return $html;
	}

	/**
	 * Get a postprocessed ParserOutput with metadata for all the given GlobeCoordinateValues.
	 *
	 * ATTENTION: This ParserOutput will generally only contain useable metadata, for
	 * getting the html for a certain GlobeCoordinateValue, please use self::getHtml().
	 *
	 * @param GlobeCoordinateValue[] $values
	 * @param Language $language
	 * @param bool $variableWidth
	 * @return ParserOutput
	 */
	public function getParserOutput( array $values, Language $language, bool $variableWidth ) {
		// Parse all mapframes at once, to get metadata for all of them
		$wikiText = '';
		foreach ( $values as $value ) {
			if ( $value->getGlobe() !== GlobeCoordinateValue::GLOBE_EARTH ) {
				continue;
			}
			$wikiText .= $this->getWikiText( $value, $variableWidth );
		}

		$parserOptions = $this->getParserOptions( $language );
		return $this->parser->parse(
			$wikiText,
			RequestContext::getMain()->getTitle() ?? Title::makeTitle( NS_SPECIAL, 'BlankPage' ),
			 $parserOptions
		)->runOutputPipeline( $parserOptions, [] );
	}

	private function getParserOptions( Language $language ): ParserOptions {
		// Cannot use $this->parser->getUser(), because that relies on the parser
		// having either a User or ParserOptions set, causing failures:
		// Error: Call to a member function getUser() on null
		return new ParserOptions(
			RequestContext::getMain()->getUser(),
			$language
		);
	}

	/**
	 * @param GlobeCoordinateValue $value
	 * @param Language $language
	 * @param bool $variableWidth
	 * @return string
	 */
	private function getCacheKey(
		GlobeCoordinateValue $value,
		Language $language,
		bool $variableWidth
	) {
		return $value->getHash() . '#' . $language->getCode() . ( $variableWidth ? '#true' : '' );
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
		$selector = FormatJson::encode( "#$mapPreviewId .mw-kartographer-map[data-mw-kartographer]" );

		// Require all needed RL modules, then call initMapframeFromElement with the injected mapframe HTML
		// Note: this inline JS code is used as a model for the `mounted` handler in snakValue.vue
		$javaScript .= "mw.loader.using( $rlModulesJson ).then( " .
				"function( require ) { require( 'ext.kartographer.frame' ).initMapframeFromElement( " .
				"\$( $selector ).get( 0 ) ); } );";

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
	 * @param bool $variableWidth
	 * @return string wikitext
	 */
	private function getWikiText(
		GlobeCoordinateValue $value,
		bool $variableWidth
	) {
		$long = $this->formatNumber( $value->getLongitude() );
		$lat = $this->formatNumber( $value->getLatitude() );
		$width = '310';
		if ( $variableWidth ) {
			$width = 'full';
		}
		return '<mapframe width="' . $width . '" height="180" zoom="13" latitude="' .
			$lat . '" longitude="' . $long . '" frameless align="left">
			{
			"type": "Feature",
			"geometry": { "type": "Point", "coordinates": [' . $long . ', ' . $lat . '] },
			"properties": {
			  "marker-symbol": "marker",
			  "marker-size": "large",
			  "marker-color": "0050d0"
			}
		  }
		  </mapframe>';
	}

	/**
	 * @param float $number
	 * @return string
	 */
	private function formatNumber( float $number ) {
		// 12 decimal places are equivalent to <0.01 mm, more than enough for everything
		return rtrim( rtrim( number_format( $number, 12, '.', '' ), '0' ), '.' );
	}

}
