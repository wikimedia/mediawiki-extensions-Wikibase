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
	private $dataUpdates;

	/**
	 * @var StatementDataUpdate[]
	 */
	private $statementDataUpdates = array();

	/**
	 * @var SiteLinkDataUpdate[]
	 */
	private $siteLinkDataUpdates = array();

	/**
	 * @param ParserOutputDataUpdate[] $dataUpdates
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $dataUpdates ) {
		foreach ( $dataUpdates as $dataUpdate ) {
			if ( $dataUpdate instanceof StatementDataUpdate ) {
				$this->statementDataUpdates[] = $dataUpdate;
			} elseif ( $dataUpdate instanceof SiteLinkDataUpdate ) {
				$this->siteLinkDataUpdates[] = $dataUpdate;
			} else {
				throw new InvalidArgumentException( 'Each $dataUpdates element must be a '
					. 'StatementDataUpdate, SiteLinkDataUpdate or both' );
			}
		}

		$this->dataUpdates = $dataUpdates;
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
		if ( empty( $this->statementDataUpdates ) ) {
			return;
		}

		foreach ( $entity->getStatements() as $statement ) {
			foreach ( $this->statementDataUpdates as $dataUpdate ) {
				$dataUpdate->processStatement( $statement );
			}
		}
	}

	/**
	 * @param Item $item
	 */
	private function processItem( Item $item ) {
		if ( empty( $this->siteLinkDataUpdates ) ) {
			return;
		}

		foreach ( $item->getSiteLinkList() as $siteLink ) {
			foreach ( $this->siteLinkDataUpdates as $dataUpdate ) {
				$dataUpdate->processSiteLink( $siteLink );
			}
		}
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		foreach ( $this->dataUpdates as $dataUpdate ) {
			$dataUpdate->updateParserOutput( $parserOutput );
		}
	}

}
