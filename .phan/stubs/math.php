<?php

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * the Math extension.
 */

namespace MediaWiki\Extension\Math;

use ParserOutput;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\ParserOutput\StatementDataUpdater;

class MathDataUpdater implements StatementDataUpdater {
	public function __construct( PropertyDataTypeMatcher $propertyDataTypeMatcher ) {
	}

	/**
	 * @param Statement $statement
	 */
	public function processStatement( Statement $statement ) {
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
	}
}
