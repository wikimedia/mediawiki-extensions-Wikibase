<?php

namespace Wikibase\Repo\DataUpdates;

use DataValues\StringValue;
use ParserOutput;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * Add url data values as external links in ParserOutput.
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
class ExternalLinksDataUpdate implements StatementDataUpdate {

	/**
	 * @var null[]
	 */
	private $urls = array();

	/**
	 * @param StatementList $statements
	 *
	 * @return string[]
	 */
	public function getExternalLinks( StatementList $statements ) {
		$this->urls = array();

		foreach ( $statements as $statement ) {
			$this->processStatement( $statement );
		}

		return array_keys( $this->urls );
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
				$url = $value->getValue();

				if ( $url !== '' ) {
					$this->urls[$url] = null;
				}
			}
		}
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		foreach ( $this->urls as $url => $null ) {
			$parserOutput->addExternalLink( $url );
		}
	}

}
