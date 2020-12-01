<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Property;

/**
 * @license GPL-2.0-or-later
 */
class PropertyParserOutputUpdater implements EntityParserOutputUpdater {

	/** @var StatementDataUpdater */
	private $statementDataUpdater;

	public function __construct( StatementDataUpdater $statementDataUpdater ) {
		$this->statementDataUpdater = $statementDataUpdater;
	}

	public function updateParserOutput( ParserOutput $parserOutput, EntityDocument $entity ) {
		if ( $entity instanceof Property ) {
			$this->updateParserOutputForProperty( $parserOutput, $entity );
		}
	}

	public function updateParserOutputForProperty( ParserOutput $parserOutput, Property $property ) {
		foreach ( $property->getStatements() as $statement ) {
			$this->statementDataUpdater->processStatement( $statement );
		}

		$this->statementDataUpdater->updateParserOutput( $parserOutput );
	}

}
