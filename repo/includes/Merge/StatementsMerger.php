<?php

namespace Wikibase\Repo\Merge;

use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;

/**
 * Merges statements of two StatementListProvider objects.
 *
 * Note that this will not check whether the source and target objects have links.
 * It will also not modify the source statements.
 */
class StatementsMerger {

	/**
	 * @var StatementChangeOpFactory
	 */
	private $changeOpFactory;

	public function __construct( StatementChangeOpFactory $changeOpFactory ) {
		$this->changeOpFactory = $changeOpFactory;
	}

	public function merge( StatementListProvider $source, StatementListProvider $target ) {
		$removeOps = $this->generateRemoveStatementOps( $source );
		$mergeOps = $this->generateMergeChangeOps( $source, $target );

		$removeOps->apply( $source );
		$mergeOps->apply( $target );
	}

	private function generateMergeChangeOps( StatementListProvider $source, StatementListProvider $target ) {
		$changeOps = new ChangeOps();

		foreach ( $source->getStatements()->toArray() as $sourceStatement ) {
			$toStatement = clone $sourceStatement;
			$toStatement->setGuid( null );
			$toMergeToStatement = $this->findEquivalentStatement( $toStatement, $target );

			if ( $toMergeToStatement ) {
				$this->generateReferencesChangeOps( $toStatement, $toMergeToStatement );
			} else {
				$changeOps->add( $this->changeOpFactory->newSetStatementOp( $toStatement ) );
			}
		}

		return $changeOps;
	}

	/**
	 * Finds a statement in the target entity with the same main snak and qualifiers as $fromStatement
	 *
	 * @param Statement $fromStatement
	 *
	 * @return Statement|false Statement to merge reference into or false
	 */
	private function findEquivalentStatement( Statement $fromStatement, StatementListProvider $target ) {
		$fromHash = $this->getStatementHash( $fromStatement );

		/** @var Statement $statement */
		foreach ( $target->getStatements() as $statement ) {
			$toHash = $this->getStatementHash( $statement );
			if ( $toHash === $fromHash ) {
				return $statement;
			}
		}

		return false;
	}

	/**
	 * @param Statement $statement
	 *
	 * @return string combined hash of the Mainsnak and Qualifiers
	 */
	private function getStatementHash( Statement $statement ) {
		return $statement->getMainSnak()->getHash() . $statement->getQualifiers()->getHash();
	}

	/**
	 * @param Statement $fromStatement statement to take references from
	 * @param Statement $toStatement statement to add references to
	 *
	 * @return ChangeOps
	 */
	private function generateReferencesChangeOps( Statement $fromStatement, Statement $toStatement ) {
		$changeOps = new ChangeOps();

		/** @var Reference $reference */
		foreach ( $fromStatement->getReferences() as $reference ) {
			if ( !$toStatement->getReferences()->hasReferenceHash( $reference->getHash() ) ) {
				$changeOps->add( $this->changeOpFactory->newSetReferenceOp(
					$toStatement->getGuid(),
					$reference,
					''
				) );
			}
		}

		return $changeOps;
	}

	private function generateRemoveStatementOps( StatementListProvider $source ) {
		$changeOps = new ChangeOps();

		foreach ( $source->getStatements() as $statement ) {
			$changeOps->add( $this->changeOpFactory->newRemoveStatementOp( $statement->getGuid() ) );
		}

		return $changeOps;
	}

}
