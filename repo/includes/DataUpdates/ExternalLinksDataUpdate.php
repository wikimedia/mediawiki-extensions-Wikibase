<?php

namespace Wikibase\Repo\DataUpdates;

use DataValues\DataValue;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Store\PropertyDataTypeMatcher;

/**
 * Add url data values as external links in ParserOutput.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ExternalLinksDataUpdate implements StatementDataUpdate {

	/**
	 * @var PropertyDataTypeMatcher
	 */
	private $propertyDataTypeMatcher;

	/**
	 * @var DataValue[]
	 */
	private $usedUrls = array();

	/**
	 * @param PropertyDataTypeMatcher $propertyDataTypeMatcher
	 */
	public function __construct( PropertyDataTypeMatcher $propertyDataTypeMatcher ) {
		$this->propertyDataTypeMatcher = $propertyDataTypeMatcher;
	}

	/**
	 * Add DataValue to list of used urls, if Snak property has 'url' data type.
	 *
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
		$snaks = $statement->getAllSnaks();
		$this->extractUsedUrls( $snaks );
	}

	/**
	 * @param Snak[] $snaks
	 */
	public function extractUsedUrls( array $snaks ) {
		foreach( $snaks as $snak ) {
			if ( $snak instanceof PropertyValueSnak &&
				$this->propertyDataTypeMatcher->isMatchingDataType( $snak->getPropertyId(), 'url' )
			) {
				$dataValue = $snak->getDataValue();
				$this->usedUrls[$dataValue->getHash()] = $dataValue;
			}
		}
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		// treat URL values as external links ------
		foreach ( $this->usedUrls as $url ) {
			$value = $url->getValue();
			if ( is_string( $value ) ) {
				$parserOutput->addExternalLink( $value );
			}
		}
	}

}
