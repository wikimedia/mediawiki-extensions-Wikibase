<?php

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * the Math extension.
 * @codingStandardsIgnoreFile
 */

class MathDataUpdater implements \Wikibase\Repo\ParserOutput\StatementDataUpdater {
	public function __construct( Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher $propertyDataTypeMatcher ) {
	}
	/**
	*
	* @param \Wikibase\DataModel\Statement\Statement $statement
	*/
	public function processStatement( \Wikibase\DataModel\Statement\Statement $statement ) {
	}
	/**
	 * @param ParserOutput $parserOutput
	 */
	public function updateParserOutput( ParserOutput $parserOutput ) {
	}
}
