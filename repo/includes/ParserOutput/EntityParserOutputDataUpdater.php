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
 * @fixme The split between EntityParserOutputDataUpdater and EntityParserOutputGenerator is
 * arbitrary. Which concerns belong where, and how is that reflected by the names?
 * @see https://gerrit.wikimedia.org/r/#/c/243613/15/repo/includes/EntityParserOutputGenerator.php
 *
 * @since 0.5
 *
 * @license GPL-2.0+
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
			$this->registerDataUpdater( $updater );
		}

		$this->parserOutput = $parserOutput;
		$this->dataUpdaters = $dataUpdaters;
	}

	private function registerDataUpdater( $updater ) {
		if ( !( $updater instanceof StatementDataUpdater ) &&
		     !( $updater instanceof SiteLinkDataUpdater )
		) {
			throw new InvalidArgumentException(
				'Each $dataUpdaters element must be a StatementDataUpdater, SiteLinkDataUpdater or both'
			);
		}

		if ( $updater instanceof StatementDataUpdater ) {
			$this->statementDataUpdaters[] = $updater;
		}

		if ( $updater instanceof SiteLinkDataUpdater ) {
			$this->siteLinkDataUpdaters[] = $updater;
		}
	}

	/**
	 * @param EntityDocument $entity
	 */
	public function processEntity( EntityDocument $entity ) {
		if ( $entity instanceof StatementListProvider && $this->statementDataUpdaters ) {
			$this->processStatements( $entity->getStatements() );
		}

		if ( $entity instanceof Item && $this->siteLinkDataUpdaters ) {
			$this->processSiteLinks( $entity->getSiteLinkList() );
		}
	}

	/**
	 * @param StatementList $statements
	 */
	private function processStatements( StatementList $statements ) {
		foreach ( $statements as $statement ) {
			foreach ( $this->statementDataUpdaters as $updater ) {
				$updater->processStatement( $statement );
			}
		}
	}

	/**
	 * @param SiteLinkList $siteLinks
	 */
	private function processSiteLinks( SiteLinkList $siteLinks ) {
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
