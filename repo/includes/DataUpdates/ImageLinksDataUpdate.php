<?php

namespace Wikibase\Repo\DataUpdates;

use DataValues\DataValue;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Store\PropertyDataTypeMatcher;

/**
 * Register commonsMedia values as used images in ParserOutput.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ImageLinksDataUpdate implements SnakDataUpdate {

	/**
	 * @var PropertyDataTypeMatcher
	 */
	private $propertyDataTypeMatcher;

	/**
	 * @var DataValue[]
	 */
	private $usedImages = array();

	/**
	 * @param PropertyDataTypeMatcher $propertyDataTypeMatcher
	 */
	public function __construct( PropertyDataTypeMatcher $propertyDataTypeMatcher ) {
		$this->propertyDataTypeMatcher = $propertyDataTypeMatcher;
	}

	/**
	 * Add DataValue to list of used images if Snak property data type is commonsMedia.
	 *
	 * @param Snak[] $snaks
	 */
	public function processSnak( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak &&
			$this->propertyDataTypeMatcher->isMatchingDataType(
				$snak->getPropertyId(),
				'commonsMedia'
			)
		) {
			$dataValue = $snak->getDataValue();
			$this->usedImages[$dataValue->getHash()] = $dataValue;
		}
	}

	/**
	 * Treat CommonsMedia values as file transclusions
	 *
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		foreach ( $this->usedImages as $image ) {
			$value = $image->getValue();
			if ( is_string( $value ) ) {
				$parserOutput->addImage( str_replace( ' ', '_', $value ) );
			}
		}
	}

}
