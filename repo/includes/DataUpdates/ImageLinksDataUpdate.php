<?php

namespace Wikibase\Repo\DataUpdates;

use DataValues\StringValue;
use ParserOutput;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Register commonsMedia values as used images in ParserOutput.
 *
 * @fixme This basic version does not do filtering based on the property data type!
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 */
class ImageLinksDataUpdate implements StatementDataUpdate {

	/**
	 * @var string[]
	 */
	private $fileNames = array();

	/**
	 * @param StatementList $statements
	 *
	 * @return null[]
	 */
	public function getImageLinks( StatementList $statements ) {
		$this->fileNames = array();

		foreach ( $statements as $statement ) {
			$this->processStatement( $statement );
		}

		return array_keys( $this->fileNames );
	}

	/**
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
		foreach ( $statement->getAllSnaks() as $snak ) {
			$this->processSnak( $snak );
		}
	}

	/**
	 * @param Snak $snak
	 */
	private function processSnak( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak ) {
			$value = $snak->getDataValue();

			if ( $value instanceof StringValue ) {
				$fileName = str_replace( ' ', '_', $value->getValue() );

				if ( $fileName !== '' ) {
					$this->fileNames[$fileName] = null;
				}
			}
		}
	}

	/**
	 * Treat CommonsMedia values as file transclusions
	 *
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		foreach ( $this->fileNames as $fileName => $null ) {
			$parserOutput->addImage( $fileName );
		}
	}

}
