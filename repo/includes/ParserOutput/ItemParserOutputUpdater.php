<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;

/**
 * @license GPL-2.0-or-later
 */
class ItemParserOutputUpdater implements EntityParserOutputUpdater {

	/** @var StatementDataUpdater */
	private $statementDataUpdater;

	public function __construct( StatementDataUpdater $statementDataUpdater ) {
		$this->statementDataUpdater = $statementDataUpdater;
	}

	public function updateParserOutput( ParserOutput $parserOutput, EntityDocument $entity ) {
		if ( $entity instanceof Item ) {
			$this->updateParserOutputForItem( $parserOutput, $entity );
		}
	}

	public function updateParserOutputForItem( ParserOutput $parserOutput, Item $item ) {
		foreach ( $item->getStatements() as $statement ) {
			$this->statementDataUpdater->processStatement( $statement );
		}

		$this->statementDataUpdater->updateParserOutput( $parserOutput );
	}

}
