<?php

namespace Wikibase\DataModel\Statement;

use Diff\Differ\MapDiffer;
use Diff\DiffOp\Diff\Diff;
use UnexpectedValueException;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementListDiffer {

	/**
	 * @since 1.0
	 *
	 * @param StatementList $fromStatements
	 * @param StatementList $toStatements
	 *
	 * @return Diff
	 * @throws UnexpectedValueException
	 */
	public function getDiff( StatementList $fromStatements, StatementList $toStatements ) {
		return new Diff(
			$this->newDiffer()->doDiff(
				$this->toDiffArray( $fromStatements ),
				$this->toDiffArray( $toStatements )
			),
			true
		);
	}

	private function newDiffer() {
		$differ = new MapDiffer();

		$differ->setComparisonCallback( function( Statement $fromStatement, Statement $toStatement ) {
			return $fromStatement->equals( $toStatement );
		} );

		return $differ;
	}

	private function toDiffArray( StatementList $statementList ) {
		$statementArray = array();

		/**
		 * @var Statement $statement
		 */
		foreach ( $statementList as $statement ) {
			$statementArray[$statement->getGuid()] = $statement;
		}

		return $statementArray;
	}

}
