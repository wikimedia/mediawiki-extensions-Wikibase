<?php

namespace Wikibase\Repo\DataUpdates;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Snak\Snak;
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
		$this->processSnaks( $this->getEntitySnaks( $entity ) );

		if ( $entity instanceof Item ) {
			$this->processSiteLinks( $entity->getSiteLinkList() );
		}
	}

	private function processSnaks( array $snaks ) {
		$snakDataUpdates = $this->getSnakDataUpdates();

		if ( empty( $snakDataUpdates ) ) {
			return;
		}

		foreach ( $snaks as $snak ) {
			foreach ( $snakDataUpdates as $snakDataUpdate ) {
				$snakDataUpdate->processSnak( $snak );
			}
		}
	}

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
		foreach ( $this->dataUpdates as $snakDataUpdate ) {
			$snakDataUpdate->updateParserOutput( $parserOutput );
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
	 * @return SnakDataUpdate[]
	 */
	private function getSnakDataUpdates() {
		$snakDataUpdates = array();

		foreach ( $this->dataUpdates as $dataUpdate ) {
			if ( $dataUpdate instanceof SnakDataUpdate ) {
				$snakDataUpdates[] = $dataUpdate;
			}
		}

		return $snakDataUpdates;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return Snak[]
	 */
	private function getEntitySnaks( EntityDocument $entity ) {
		if ( $entity instanceof StatementListProvider ) {
			return $entity->getStatements()->getAllSnaks();
		}

		return array();
	}

}
