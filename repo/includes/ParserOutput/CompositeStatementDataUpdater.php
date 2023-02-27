<?php

namespace Wikibase\Repo\ParserOutput;

use MediaWiki\Parser\ParserOutput;
use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class CompositeStatementDataUpdater implements StatementDataUpdater {

	/** @var array */
	private $updaters;

	/**
	 * @param StatementDataUpdater ...$updaters
	 */
	public function __construct( ...$updaters ) {
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
