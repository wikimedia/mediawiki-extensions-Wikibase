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
					/** @var Statement $statement */
					$statement = $diffOp->getNewValue();
					$guid = $statement->getGuid();
					if ( $statements->getFirstStatementWithGuid( $guid ) === null ) {
						$statements->addStatement( $statement );
					}
					break;

				case $diffOp instanceof DiffOpChange:
					/** @var DiffOpChange $diffOp */
					/** @var Statement $oldStatement */
					/** @var Statement $newStatement */
					$oldStatement = $diffOp->getOldValue();
					$newStatement = $diffOp->getNewValue();
					$this->changeStatement( $statements, $oldStatement->getGuid(), $newStatement );
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
	 * @param StatementList $statements
	 * @param string|null $oldGuid
	 * @param Statement $newStatement
	 */
	private function changeStatement( StatementList $statements, $oldGuid, Statement $newStatement ) {
		$replacements = array();

		foreach ( $statements->toArray() as $statement ) {
			$guid = $statement->getGuid();

			// Collect all elements starting from the first with the same GUID
			if ( $replacements !== array() ) {
				$guid === null
					? $replacements[] = $statement
					: $replacements[$guid] = $statement;
			} elseif ( $guid === $oldGuid ) {
				$guid === null
					? $replacements[] = $newStatement
					: $replacements[$guid] = $newStatement;
			}
		}

		// Remove all starting from the one that should be replaced
		foreach ( $replacements as $guid => $statement ) {
			$statements->removeStatementsWithGuid( is_int( $guid ) ? null : $guid );
		}
		// Re-add all starting from the new one
		foreach ( $replacements as $statement ) {
			$statements->addStatement( $statement );
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
