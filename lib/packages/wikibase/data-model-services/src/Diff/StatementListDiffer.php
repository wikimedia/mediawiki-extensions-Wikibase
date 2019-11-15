<?php

namespace Wikibase\DataModel\Services\Diff;

use Diff\Comparer\ComparableComparer;
use Diff\Differ\MapDiffer;
use Diff\DiffOp\Diff\Diff;
use UnexpectedValueException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @since 3.6
 *
 * @license GPL-2.0-or-later
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
		return new MapDiffer( false, null, new ComparableComparer() );
	}

	private function toDiffArray( StatementList $statementList ) {
		$statementArray = [];

		/**
		 * @var Statement $statement
		 */
		foreach ( $statementList as $statement ) {
			$statementArray[$statement->getGuid()] = $statement;
		}

		return $statementArray;
	}

}
