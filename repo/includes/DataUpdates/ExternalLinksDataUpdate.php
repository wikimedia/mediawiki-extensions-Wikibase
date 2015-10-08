<?php

namespace Wikibase\Repo\DataUpdates;

use DataValues\StringValue;
use ParserOutput;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * Add url data values as external links in ParserOutput.
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
	 * @var string[]
	 */
	private $urls = array();

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
					$this->urls[$value->getValue()] = $value->getValue();
				}
			}
		}
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		foreach ( $this->urls as $url ) {
			$parserOutput->addExternalLink( $url );
		}
	}

}
