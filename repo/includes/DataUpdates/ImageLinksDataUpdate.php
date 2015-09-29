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
 * Register commonsMedia values as used images in ParserOutput.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ImageLinksDataUpdate implements StatementDataUpdate {

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
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
		$snaks = $statement->getAllSnaks( $statement );
		$this->extractUsedImagesFromSnaks( $snaks );
	}

	/**
	 * @param Snak[] $snaks
	 */
	private function extractUsedImagesFromSnaks( array $snaks ) {
		foreach ( $snaks as $snak ) {
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
