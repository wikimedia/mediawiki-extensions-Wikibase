<?php

namespace Wikibase\Repo\ParserOutput;

use DataValues\StringValue;
use ParserOutput;
use RepoGroup;
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

	/**
	 * @var RepoGroup
	 */
	private $repoGroup;

	/**
	 * @var null[] Hash set of the file name strings found while processing statements. Only the
	 * array keys are used for performance reasons, the values are meaningless.
	 */
	private $fileNames = [];

	public function __construct(
		PropertyDataTypeMatcher $propertyDataTypeMatcher,
		RepoGroup $repoGroup
	) {
		$this->propertyDataTypeMatcher = $propertyDataTypeMatcher;
		$this->repoGroup = $repoGroup;
	}

	/**
	 * Add DataValue to list of used images if Snak property data type is commonsMedia.
	 *
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
		foreach ( $statement->getAllSnaks() as $snak ) {
			$this->processSnak( $snak );
		}
	}

	private function processSnak( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak ) {
			$id = $snak->getPropertyId();
			$value = $snak->getDataValue();

			if ( $value instanceof StringValue
				&& $this->propertyDataTypeMatcher->isMatchingDataType( $id, 'commonsMedia' )
			) {
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
			$file = $this->repoGroup->findFile( $fileName );

			$parserOutput->addImage(
				$fileName,
				$file ? $file->getTimestamp() : false,
				$file ? $file->getSha1() : false
			);
		}
	}

}
