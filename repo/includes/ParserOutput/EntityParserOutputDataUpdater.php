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
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
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
	private $statementDataUpdaters = [];

	/**
	 * @var SiteLinkDataUpdater[]
	 */
	private $siteLinkDataUpdaters = [];

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
		if ( !( $updater instanceof StatementDataUpdater )
			&& !( $updater instanceof SiteLinkDataUpdater )
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

	public function processEntity( EntityDocument $entity ) {
		if ( $entity instanceof StatementListProvider && $this->statementDataUpdaters ) {
			$this->processStatements( $entity->getStatements() );
		}

		if ( $entity instanceof Item && $this->siteLinkDataUpdaters ) {
			$this->processSiteLinks( $entity->getSiteLinkList() );
		}
	}

	private function processStatements( StatementList $statements ) {
		foreach ( $statements as $statement ) {
			foreach ( $this->statementDataUpdaters as $updater ) {
				$updater->processStatement( $statement );
			}
		}
	}

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
