<?php

namespace Wikibase\Repo\DataUpdates;

use DataValues\DataValue;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
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
class ExternalLinksDataUpdate implements SnakDataUpdate {

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
	 * @param Snak[] $snaks
	 */
	public function processSnak( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak &&
			$this->propertyDataTypeMatcher->isMatchingDataType( $snak->getPropertyId(), 'url' )
		) {
			$dataValue = $snak->getDataValue();
			$this->usedUrls[$dataValue->getHash()] = $dataValue;
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
