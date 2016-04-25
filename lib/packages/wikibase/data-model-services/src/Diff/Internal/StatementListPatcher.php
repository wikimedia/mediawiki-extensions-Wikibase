<?php

namespace Wikibase\DataModel\Services\Diff\Internal;

use Diff\Comparer\CallbackComparer;
use Diff\DiffOp\Diff\Diff;
use Diff\Patcher\MapPatcher;
use InvalidArgumentException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;

/**
 * TODO: Class must be public.
 * TODO: Should this support actual edit conflict detection?
 *
 * Package private.
 *
 * @since 1.0
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementListPatcher {

	/**
	 * @var MapPatcher
	 */
	private $patcher;

	public function __construct() {
		$this->patcher = new MapPatcher();

		$this->patcher->setValueComparer( new CallbackComparer(
			function( Statement $firstStatement, Statement $secondStatement ) {
				return $firstStatement->equals( $secondStatement );
			}
		) );
	}

	/**
	 * @param StatementList $statements
	 * @param Diff $patch
	 *
	 * @throws InvalidArgumentException
	 * @return StatementList
	 */
	public function getPatchedStatementList( StatementList $statements, Diff $patch ) {
		$statementsByGuid = array();

		/**
		 * @var Statement $statement
		 */
		foreach ( $statements as $statement ) {
			$statementsByGuid[$statement->getGuid()] = $statement;
		}

		$patchedList = new StatementList();

		foreach ( $this->patcher->patch( $statementsByGuid, $patch ) as $statement ) {
			$patchedList->addStatement( $statement );
		}

		return $patchedList;
	}

}
