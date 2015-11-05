<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * @todo have ItemParserOutputDataUpdater, etc. instead.
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
	private $dataUpdaters;

	/**
	 * @var StatementDataUpdater[]
	 */
	private $statementDataUpdaters = array();

	/**
	 * @var SiteLinkDataUpdater[]
	 */
	private $siteLinkDataUpdaters = array();

	/**
	 * @param ParserOutput $parserOutput
	 * @param ParserOutputDataUpdater[] $dataUpdaters
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( ParserOutput $parserOutput, array $dataUpdaters ) {
		foreach ( $dataUpdaters as $updater ) {
			if ( $updater instanceof StatementDataUpdater ) {
				$this->statementDataUpdaters[] = $updater;
			} elseif ( $updater instanceof SiteLinkDataUpdater ) {
				$this->siteLinkDataUpdaters[] = $updater;
			} else {
				throw new InvalidArgumentException( 'Each $dataUpdaters element must be a '
					. 'StatementDataUpdater, SiteLinkDataUpdater or both' );
			}
		}

		$this->parserOutput = $parserOutput;
		$this->dataUpdaters = $dataUpdaters;
	}

	public function processEntity( EntityDocument $entity ) {
		if ( $entity instanceof StatementListProvider ) {
			$this->processStatementList( $entity->getStatements() );
		}

		if ( $entity instanceof Item ) {
			$this->processSiteLinks( $entity->getSiteLinkList() );
		}
	}

	private function processStatementList( StatementList $statements ) {
		if ( empty( $this->statementDataUpdaters ) ) {
			return;
		}

		foreach ( $statements as $statement ) {
			foreach ( $this->statementDataUpdaters as $updater ) {
				$updater->processStatement( $statement );
			}
		}
	}

	private function processSiteLinks( SiteLinkList $siteLinks ) {
		if ( empty( $this->siteLinkDataUpdaters ) ) {
			return;
		}

		foreach ( $siteLinks as $siteLink ) {
			foreach ( $this->siteLinkDataUpdaters as $updater ) {
				$updater->processSiteLink( $siteLink );
			}
		}
	}

	public function finish() {
		foreach ( $this->dataUpdaters as $updater ) {
			$updater->updateParserOutput( $this->parserOutput );
		}
	}

}
