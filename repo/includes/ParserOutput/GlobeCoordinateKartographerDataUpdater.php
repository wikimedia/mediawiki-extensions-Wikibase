<?php

namespace Wikibase\Repo\ParserOutput;

use DataValues\Geo\Values\GlobeCoordinateValue;
use Language;
use ParserOutput;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;

/**
 * Add required data for Kartographer to the ParserOutput.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class GlobeCoordinateKartographerDataUpdater implements StatementDataUpdater {

	/**
	 * @var GlobeCoordinateValue[]
	 */
	private $globeCoordinateValues = [];

	/**
	 * @var CachingKartographerEmbeddingHandler
	 */
	private $kartographerHandler;

	public function __construct( CachingKartographerEmbeddingHandler $kartographerHandler ) {
		$this->kartographerHandler = $kartographerHandler;
	}

	/**
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
		foreach ( $statement->getAllSnaks() as $snak ) {
			$this->processSnak( $snak );
		}
	}

	private function processSnak( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak ) {
			$value = $snak->getDataValue();

			if ( $value instanceof GlobeCoordinateValue ) {
				$this->globeCoordinateValues[] = $value;
			}
		}
	}

	/**
	 * Add data for the discovered GlobeCoordinateValues to the ParserOutput.
	 *
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		if ( $this->globeCoordinateValues === [] ) {
			return;
		}

		$jsVars = $parserOutput->getJsConfigVars();

		// Hack: Get the language this ParserOutput was parsed in
		if ( isset( $jsVars['wgUserLanguage'] ) ) {
			$language = Language::factory( $jsVars['wgUserLanguage'] );
		} else {
			// If this is not the user language, we will (maybe) need to parse this twice.
			global $wgContLang;

			$language = $wgContLang;
		}

		$kartographerParserOutput = $this->kartographerHandler->getParserOutput(
			$this->globeCoordinateValues,
			$language
		);

		$parserOutput->addJsConfigVars( $kartographerParserOutput->getJsConfigVars() );
		$parserOutput->addModules( $kartographerParserOutput->getModules() );
		$parserOutput->addModuleStyles( $kartographerParserOutput->getModuleStyles() );

		$parserOutput->setProperty(
			'kartographer',
			$kartographerParserOutput->getProperty( 'kartographer' )
		);
		$parserOutput->setProperty(
			'kartographer_links',
			$kartographerParserOutput->getProperty( 'kartographer_links' )
		);
		$parserOutput->setProperty(
			'kartographer_frames',
			$kartographerParserOutput->getProperty( 'kartographer_frames' )
		);
	}

}
