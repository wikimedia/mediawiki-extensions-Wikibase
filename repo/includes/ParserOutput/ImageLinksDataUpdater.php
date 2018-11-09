<?php

namespace Wikibase\Repo\ParserOutput;

use DataValues\StringValue;
use ParserOutput;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * Register commonsMedia values as used images in ParserOutput.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
 */
class ImageLinksDataUpdater implements StatementDataUpdater {

	/**
	 * @var PropertyDataTypeMatcher
	 */
	private $propertyDataTypeMatcher;

	public function __construct( PropertyDataTypeMatcher $propertyDataTypeMatcher ) {
		$this->propertyDataTypeMatcher = $propertyDataTypeMatcher;
	}

	/**
	 * Add DataValue to list of used images if Snak property data type is commonsMedia.
	 * Treat CommonsMedia values as file transclusions
	 */
	public function updateParserOutput( ParserOutput $parserOutput, Statement $statement ) {
		foreach ( $statement->getAllSnaks() as $snak ) {
			if ( !( $snak instanceof PropertyValueSnak ) ) {
				continue;
			}

			$id = $snak->getPropertyId();
			$value = $snak->getDataValue();

			if ( !( $value instanceof StringValue ) ) {
				continue;
			}
			if ( !$this->propertyDataTypeMatcher->isMatchingDataType( $id, 'commonsMedia' ) ) {
				continue;
			}
			$fileName = str_replace( ' ', '_', $value->getValue() );

			if ( $fileName === '' ) {
				continue;
			}

			$file = wfFindFile( $fileName );

			$parserOutput->addImage(
				$fileName,
				$file ? $file->getSha1() : false,
				$file ? $file->getTimestamp() : false
			);
		}
	}

}
