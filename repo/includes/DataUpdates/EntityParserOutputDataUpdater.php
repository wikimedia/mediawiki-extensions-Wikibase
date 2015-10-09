<?php

namespace Wikibase\Repo\DataUpdates;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
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
	 * @param ParserOutputDataUpdate[] $dataUpdates
	 */
	public function __construct( array $dataUpdates ) {
		$this->dataUpdates = $dataUpdates;
	}

	/**
	 * @param EntityDocument $entity
	 */
	public function processEntity( EntityDocument $entity ) {
		if ( $entity instanceof StatementListProvider ) {
			$this->processStatements( $entity );
		}

		if ( $entity instanceof Item ) {
			$this->processSiteLinks( $entity );
		}
	}

	/**
	 * @param StatementListProvider $entity
	 */
	private function processStatements( StatementListProvider $entity ) {
		$dataUpdates = $this->getStatementDataUpdates();

		if ( empty( $dataUpdates ) ) {
			return;
		}

		foreach ( $entity->getStatements() as $statement ) {
			foreach ( $dataUpdates as $dataUpdate ) {
				$dataUpdate->processStatement( $statement );
			}
		}
	}

	/**
	 * @param Item $item
	 */
	private function processSiteLinks( Item $item ) {
		$dataUpdates = $this->getSiteLinkDataUpdates();

		if ( empty( $dataUpdates ) ) {
			return;
		}

		foreach ( $item->getSiteLinkList() as $siteLink ) {
			foreach ( $dataUpdates as $dataUpdate ) {
				$dataUpdate->processSiteLink( $siteLink );
			}
		}
	}

	/**
	 * @return StatementDataUpdate[]
	 */
	private function getStatementDataUpdates() {
		$statementDataUpdates = array();

		foreach ( $this->dataUpdates as $dataUpdate ) {
			if ( $dataUpdate instanceof StatementDataUpdate ) {
				$statementDataUpdates[] = $dataUpdate;
			}
		}

		return $statementDataUpdates;
	}

	/**
	 * @return SiteLinkDataUpdate[]
	 */
	private function getSiteLinkDataUpdates() {
		$siteLinkDataUpdates = array();

		foreach ( $this->dataUpdates as $dataUpdate ) {
			if ( $dataUpdate instanceof SiteLinkDataUpdate ) {
				$siteLinkDataUpdates[] = $dataUpdate;
			}
		}

		return $siteLinkDataUpdates;
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
