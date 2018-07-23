<?php

namespace Wikibase\Repo\Merge;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;

/**
 * Merges statements of two StatementListProvider objects.
 *
 * Note that this will not check whether the source and target objects have links.
 */
class StatementsMerger {

	public function merge( StatementListProvider $source, StatementListProvider $target ) {
		$this->addOrMergeStatements( $source, $target );
		$source->getStatements()->clear();
	}

	private function addOrMergeStatements( StatementListProvider $source, StatementListProvider $target ) {
		foreach ( $source->getStatements()->toArray() as $sourceStatement ) {
			$toStatement = clone $sourceStatement;
			$toStatement->setGuid( null );
			$toMergeToStatement = $this->findEquivalentStatement( $toStatement, $target );

			if ( $toMergeToStatement ) {
				$this->mergeStatements( $toStatement, $toMergeToStatement );
			} else {
				/** @var EntityDocument $target */
				$target->getStatements()->addNewStatement(
					$sourceStatement->getMainSnak(),
					$sourceStatement->getQualifiers(),
					$sourceStatement->getReferences(),
					( new GuidGenerator() )->newGuid( $target->getId() )
				);
			}
		}
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

	private function mergeStatements( Statement $source, Statement $target ) {
		/** @var Reference $reference */
		foreach ( $source->getReferences() as $reference ) {
			if ( !$target->getReferences()->hasReferenceHash( $reference->getHash() ) ) {
				$target->addNewReference( $reference->getSnaks() );
			}
		}
	}

}
