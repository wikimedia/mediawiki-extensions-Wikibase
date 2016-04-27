<?php

namespace Wikibase\DataModel\Services\Diff\Internal;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Diff\Patcher\PatcherException;
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
 * @author Thiemo Mättig
 */
class StatementListPatcher {

	/**
	 * @since 3.6
	 *
	 * @param StatementList $statements
	 * @param Diff $patch
	 *
	 * @throws PatcherException
	 */
	public function patchStatementList( StatementList $statements, Diff $patch ) {
		/** @var DiffOp $diffOp */
		foreach ( $patch as $diffOp ) {
			switch ( true ) {
				case $diffOp instanceof DiffOpAdd:
					/** @var DiffOpAdd $diffOp */
					$statements->addStatement( $diffOp->getNewValue() );
					break;

				case $diffOp instanceof DiffOpChange:
					/** @var DiffOpChange $diffOp */
					/** @var Statement $statement */
					$statement = $diffOp->getOldValue();
					$statements->removeStatementsWithGuid( $statement->getGuid() );
					$statements->addStatement( $diffOp->getNewValue() );
					break;

				case $diffOp instanceof DiffOpRemove:
					/** @var DiffOpRemove $diffOp */
					/** @var Statement $statement */
					$statement = $diffOp->getOldValue();
					$statements->removeStatementsWithGuid( $statement->getGuid() );
					break;

				default:
					throw new PatcherException( 'Invalid statement list diff' );
			}
		}
	}

	/**
	 * @deprecated since 3.6, use patchStatementList instead
	 *
	 * @param StatementList $statements
	 * @param Diff $patch
	 *
	 * @throws InvalidArgumentException
	 * @return StatementList
	 */
	public function getPatchedStatementList( StatementList $statements, Diff $patch ) {
		$patched = clone $statements;
		$this->patchStatementList( $patched, $patch );
		return $patched;
	}

}
