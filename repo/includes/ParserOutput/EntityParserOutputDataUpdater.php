<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * @todo have ItemParserOutputDataUpdate, etc. instead.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 */
class EntityParserOutputDataUpdater {

	/**
	 * @var ParserOutputDataUpdate[]
	 */
	private $dataUpdaters;

	/**
	 * @var StatementDataUpdate[]
	 */
	private $statementDataUpdaters = array();

	/**
	 * @var SiteLinkDataUpdate[]
	 */
	private $siteLinkDataUpdaters = array();

	/**
	 * @param ParserOutputDataUpdate[] $dataUpdaters
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $dataUpdaters ) {
		foreach ( $dataUpdaters as $dataUpdater ) {
			if ( $dataUpdater instanceof StatementDataUpdate ) {
				$this->statementDataUpdaters[] = $dataUpdater;
			} elseif ( $dataUpdater instanceof SiteLinkDataUpdate ) {
				$this->siteLinkDataUpdaters[] = $dataUpdater;
			} else {
				throw new InvalidArgumentException( 'Each $dataUpdaters element must be a '
					. 'StatementDataUpdate, SiteLinkDataUpdate or both' );
			}
		}

		$this->dataUpdaters = $dataUpdaters;
	}

	/**
	 * @param EntityDocument $entity
	 */
	public function processEntity( EntityDocument $entity ) {
		if ( $entity instanceof StatementListProvider ) {
			$this->processStatementListProvider( $entity );
		}

		if ( $entity instanceof Item ) {
			$this->processItem( $entity );
		}
	}

	/**
	 * @param StatementListProvider $entity
	 */
	private function processStatementListProvider( StatementListProvider $entity ) {
		if ( empty( $this->statementDataUpdaters ) ) {
			return;
		}

		foreach ( $entity->getStatements() as $statement ) {
			foreach ( $this->statementDataUpdaters as $dataUpdater ) {
				$dataUpdater->processStatement( $statement );
			}
		}
	}

	/**
	 * @param Item $item
	 */
	private function processItem( Item $item ) {
		if ( empty( $this->siteLinkDataUpdaters ) ) {
			return;
		}

		foreach ( $item->getSiteLinkList() as $siteLink ) {
			foreach ( $this->siteLinkDataUpdaters as $dataUpdater ) {
				$dataUpdater->processSiteLink( $siteLink );
			}
		}
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		foreach ( $this->dataUpdaters as $dataUpdater ) {
			$dataUpdater->updateParserOutput( $parserOutput );
		}
	}

}
