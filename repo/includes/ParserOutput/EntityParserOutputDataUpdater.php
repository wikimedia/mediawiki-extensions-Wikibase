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
	 * @var ParserOutput
	 */
	private $parserOutput;

	/**
	 * @var ParserOutputDataUpdater[]
	 */
	private $dataUpdates;

	/**
	 * @var StatementDataUpdater[]
	 */
	private $statementDataUpdates = array();

	/**
	 * @var SiteLinkDataUpdater[]
	 */
	private $siteLinkDataUpdates = array();

	/**
	 * @param ParserOutput $parserOutput
	 * @param ParserOutputDataUpdater[] $dataUpdates
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( ParserOutput $parserOutput, array $dataUpdates ) {
		foreach ( $dataUpdates as $dataUpdate ) {
			if ( $dataUpdate instanceof StatementDataUpdater ) {
				$this->statementDataUpdates[] = $dataUpdate;
			} elseif ( $dataUpdate instanceof SiteLinkDataUpdater ) {
				$this->siteLinkDataUpdates[] = $dataUpdate;
			} else {
				throw new InvalidArgumentException( 'Each $dataUpdates element must be a '
					. 'StatementDataUpdater, SiteLinkDataUpdater or both' );
			}
		}

		$this->parserOutput = $parserOutput;
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

	public function finish() {
		foreach ( $this->dataUpdates as $dataUpdate ) {
			$dataUpdate->updateParserOutput( $this->parserOutput );
		}
	}

}
