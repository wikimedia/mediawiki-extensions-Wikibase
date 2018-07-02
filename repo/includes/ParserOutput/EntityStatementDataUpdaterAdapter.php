<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * @license GPL-2.0-or-later
 */
class EntityStatementDataUpdaterAdapter implements EntityParserOutputDataUpdater {

	/**
	 * @var StatementDataUpdater
	 */
	private $dataUpdater;

	public function __construct( StatementDataUpdater $dataUpdater ) {
		$this->dataUpdater = $dataUpdater;
	}

	public function processEntity( EntityDocument $entity ) {
		if ( $entity instanceof StatementListProvider ) {
			foreach ( $entity->getStatements() as $statement ) {
				$this->dataUpdater->processStatement( $statement );
			}
		}
	}

	public function updateParserOutput( ParserOutput $parserOutput ) {
		$this->dataUpdater->updateParserOutput( $parserOutput );
	}

}
