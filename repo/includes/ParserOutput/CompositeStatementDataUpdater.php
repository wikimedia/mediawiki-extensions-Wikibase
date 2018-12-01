<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\DataModel\Statement\Statement;

class CompositeStatementDataUpdater implements StatementDataUpdater {

	private $updaters;

	public function __construct( StatementDataUpdater ...$updaters ) {
		$this->updaters = $updaters;
	}

	public function addUpdater( StatementDataUpdater $updater ) {
		$this->updaters[] = $updater;
	}

	public function processStatement( Statement $statement ) {
		foreach ( $this->updaters as $updater ) {
			$updater->processStatement( $statement );
		}
	}

	public function updateParserOutput( ParserOutput $parserOutput ) {
		foreach ( $this->updaters as $updater ) {
			$updater->updateParserOutput( $parserOutput );
		}
	}

}
