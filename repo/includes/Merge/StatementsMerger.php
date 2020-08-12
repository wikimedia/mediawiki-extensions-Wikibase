<?php

namespace Wikibase\Repo\Merge;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\ChangeOp\StatementChangeOpFactory;
use Wikimedia\Assert\Assert;

/**
 * Merges statements of two StatementListProvider objects.
 *
 * Note that this will not check whether the source and target objects have links.
 *
 * @license GPL-2.0-or-later
 */
class StatementsMerger {

	/**
	 * @var StatementChangeOpFactory
	 */
	private $changeOpFactory;

	public function __construct( StatementChangeOpFactory $changeOpFactory ) {
		$this->changeOpFactory = $changeOpFactory;
	}

	/**
	 * @param StatementListProvider|EntityDocument $source
	 * @param StatementListProvider|EntityDocument $target
	 * @suppress PhanTypeMismatchArgument,PhanTypeMismatchDeclaredParam False positives with intersection types
	 */
	public function merge( StatementListProvider $source, StatementListProvider $target ) {
		Assert::parameterType( EntityDocument::class, $source, '$source' );
		Assert::parameterType( EntityDocument::class, $target, '$target' );

		$removeOps = $this->generateRemoveStatementOps( $source );
		$mergeOps = $this->generateMergeChangeOps( $source, $target );

		/** @var EntityDocument $source */
		$removeOps->apply( $source );
		/** @var EntityDocument $target */
		$mergeOps->apply( $target );
	}

	private function generateMergeChangeOps( StatementListProvider $source, StatementListProvider $target ) {
		$changeOps = new ChangeOps();

		foreach ( $source->getStatements()->toArray() as $sourceStatement ) {
			$toStatement = clone $sourceStatement;
			$toStatement->setGuid( null );
			$toMergeToStatement = $this->findEquivalentStatement( $toStatement, $target );

			if ( $toMergeToStatement ) {
				$changeOps->add( $this->generateReferencesChangeOps( $toStatement, $toMergeToStatement ) );
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
	 * @param StatementListProvider $target
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
