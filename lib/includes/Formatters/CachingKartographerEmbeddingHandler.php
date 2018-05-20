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

		$out = $this->parser->parse(
			$this->getWikiText( $value ),
			Title::newFromText( 'Special:BlankPage' ),
			new ParserOptions( null, $language )
		);
		$this->cache->set( $cacheKey, $out->getText() );

		return $out->getText();
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
