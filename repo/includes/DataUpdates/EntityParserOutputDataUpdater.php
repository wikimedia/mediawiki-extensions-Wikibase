<?php

namespace Wikibase\Repo\DataUpdates;

use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikimedia\Assert\Assert;

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
	 * @var StatementDataUpdate[]
	 */
	private $statementUpdates;

	/**
	 * @var SiteLinkDataUpdate[]
	 */
	private $siteLinkUpdates;

	/**
	 * @param StatementDataUpdate[] $statementUpdates
	 * @param SiteLinkDataUpdate[] $siteLinkUpdates
	 */
	public function __construct( array $statementUpdates, array $siteLinkUpdates ) {
		Assert::parameterElementType( 'Wikibase\Repo\DataUpdates\StatementDataUpdate', $statementUpdates, '$statementUpdates' );
		Assert::parameterElementType( 'Wikibase\Repo\DataUpdates\SiteLinkDataUpdate', $siteLinkUpdates, '$siteLinkUpdates' );

		$this->statementUpdates = $statementUpdates;
		$this->siteLinkUpdates = $siteLinkUpdates;
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
		if ( empty( $this->statementUpdates ) ) {
			return;
		}

		foreach ( $entity->getStatements() as $statement ) {
			foreach ( $this->statementUpdates as $dataUpdate ) {
				$dataUpdate->processStatement( $statement );
			}
		}
	}

	/**
	 * @param Item $item
	 */
	private function processSiteLinks( Item $item ) {
		if ( empty( $this->siteLinkUpdates ) ) {
			return;
		}

		foreach ( $item->getSiteLinkList() as $siteLink ) {
			foreach ( $this->siteLinkUpdates as $dataUpdate ) {
				$dataUpdate->processSiteLink( $siteLink );
			}
		}
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
		/* @var ParserOutputDataUpdate[] $allUpdates */
		$allUpdates = array_merge( $this->statementUpdates, $this->siteLinkUpdates );

		foreach ( $allUpdates as $dataUpdate ) {
			$dataUpdate->updateParserOutput( $parserOutput );
		}
	}

}
