<?php

namespace Wikibase\Repo\ParserOutput;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\SubEntityProvider;

/**
 * @license GPL-2.0-or-later
 */
class SubEntityDataUpdaterAdapter implements EntityParserOutputDataUpdater {

	/**
	 * @var EntityParserOutputDataUpdater
	 */
	private $dataUpdater;

	public function __construct( EntityParserOutputDataUpdater $dataUpdater ) {
		$this->dataUpdater = $dataUpdater;
	}

	public function processEntity( EntityDocument $entity ) {
		$this->dataUpdater->processEntity( $entity );
		if ( $entity instanceof SubEntityProvider ) {
			foreach ( $entity->getSubEntitities() as $subEntitity ) {
				$this->dataUpdater->processEntity( $subEntitity );
			}
		}
	}

	public function updateParserOutput( ParserOutput $parserOutput ) {
		$this->dataUpdater->updateParserOutput( $parserOutput );
	}

}
