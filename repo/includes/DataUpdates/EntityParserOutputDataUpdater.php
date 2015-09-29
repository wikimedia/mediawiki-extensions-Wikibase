<?php

namespace Wikibase\Repo\DataUpdates;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * @todo have ItemParserOutputDataUpdate, etc. instead.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityParserOutputDataUpdater {

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
			$this->processStatements( $entity->getStatements() );
		}

		if ( $entity instanceof Item ) {
			$this->processSiteLinks( $entity->getSiteLinkList() );
		}
	}

	/**
	 * @param StatementList $statements
	 */
	private function processStatements( StatementList $statements ) {
		$statementDataUpdates = $this->getStatementDataUpdates();

		if ( empty( $statementDataUpdates ) ) {
			return;
		}

		foreach ( $statements as $statement ) {
			foreach ( $statementDataUpdates as $statementDataUpdate ) {
				$statementDataUpdate->processStatement( $statement );
			}
		}
	}

	/**
	 * @param SiteLinkList $siteLinks
	 */
	private function processSiteLinks( SiteLinkList $siteLinks ) {
		$siteLinkDataUpdates = $this->getSiteLinkDataUpdates();

		if ( empty( $siteLinkDataUpdates ) ) {
			return;
		}

		// process things like badges
		foreach ( $siteLinks as $siteLink ) {
			foreach ( $siteLinkDataUpdates as $siteLinkDataUpdate ) {
				$siteLinkDataUpdate->processSiteLink( $siteLink );
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

}
