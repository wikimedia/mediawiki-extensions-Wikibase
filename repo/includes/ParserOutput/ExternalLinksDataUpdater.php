<?php

namespace Wikibase\Repo\ParserOutput;

use DataValues\StringValue;
use ParserOutput;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;

/**
 * Add url data values as external links in ParserOutput.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
 */
class ExternalLinksDataUpdater implements StatementDataUpdater {

	/**
	 * @var PropertyDataTypeMatcher
	 */
	private $propertyDataTypeMatcher;

	public function __construct( PropertyDataTypeMatcher $propertyDataTypeMatcher ) {
		$this->propertyDataTypeMatcher = $propertyDataTypeMatcher;
	}

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
			if ( !$this->propertyDataTypeMatcher->isMatchingDataType( $id, 'url' ) ) {
				continue;
			}
			$url = $value->getValue();

			if ( $url === '' ) {
				continue;
			}

			$parserOutput->addExternalLink( $url );
		}
	}

}
