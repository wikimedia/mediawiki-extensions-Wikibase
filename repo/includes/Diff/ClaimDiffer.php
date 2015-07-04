<?php

namespace Wikibase\Repo\Diff;

use Diff\Differ\Differ;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\SnakList;

/**
 * Class for generating a ClaimDifference given two statements.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Adam Shorland
 */
class ClaimDiffer {

	private $listDiffer;

	public function __construct( Differ $listDiffer ) {
		$this->listDiffer = $listDiffer;
	}

	/**
	 * Calculates diff of two Statements and stores the difference in a ClaimDifference
	 *
	 * @since 0.4
	 *
	 * @param Statement|null $oldStatement
	 * @param Statement|null $newStatement
	 *
	 * @return ClaimDifference
	 */
	public function diffClaims( $oldStatement, $newStatement ) {
		$mainSnakChange = $this->diffMainSnaks( $oldStatement, $newStatement );
		$qualifierChanges = $this->diffQualifiers( $oldStatement, $newStatement );

		if ( $oldStatement instanceof Statement || $newStatement instanceof Statement ) {
			$rankChange = $this->diffRank( $oldStatement, $newStatement );
			$referenceChanges = $this->diffReferences( $oldStatement, $newStatement );
		} else {
			$rankChange = null;
			$referenceChanges = null;
		}

		return new ClaimDifference( $mainSnakChange, $qualifierChanges, $referenceChanges, $rankChange );
	}

	/**
	 * @param Statement|null $oldStatement
	 * @param Statement|null $newStatement
	 *
	 * @return DiffOpChange|null
	 */
	private function diffMainSnaks( Statement $oldStatement = null, Statement $newStatement = null ) {
		$oldStatementMainSnak = $oldStatement === null ? null : $oldStatement->getMainSnak();
		$newStatementMainSnak = $newStatement === null ? null : $newStatement->getMainSnak();

		if ( $oldStatementMainSnak === null && $newStatementMainSnak === null ) {
			return null;
		}

		if( ( $oldStatementMainSnak === null && $newStatementMainSnak !== null )
			|| !$oldStatementMainSnak->equals( $newStatementMainSnak ) ) {
			return new DiffOpChange( $oldStatementMainSnak, $newStatementMainSnak );
		}

		return null;
	}

	/**
	 * @param Statement|null $oldStatement
	 * @param Statement|null $newStatement
	 *
	 * @return Diff
	 */
	private function diffQualifiers( Statement $oldStatement = null, Statement $newStatement = null ) {
		$oldQualifiers = $oldStatement === null ? new SnakList() : $oldStatement->getQualifiers();
		$newQualifiers = $newStatement === null ? new SnakList() : $newStatement->getQualifiers();

		if ( !$oldQualifiers->equals( $newQualifiers ) ) {
			$diffOps = $this->listDiffer->doDiff(
				iterator_to_array( $oldQualifiers ),
				iterator_to_array( $newQualifiers )
			);

			return new Diff( $diffOps, false );
		}

		return null;
	}

	/**
	 * @param Statement|null $oldStatement
	 * @param Statement|null $newStatement
	 *
	 * @return DiffOpChange|null
	 */
	private function diffRank( Statement $oldStatement = null, Statement $newStatement = null ) {
		$oldRank = $oldStatement === null ? null : $oldStatement->getRank();
		$newRank = $newStatement === null ? null : $newStatement->getRank();

		if ( $oldRank !== $newRank ) {
			return new DiffOpChange( $oldRank, $newRank );
		}

		return null;
	}

	/**
	 * @param Statement|null $oldStatement
	 * @param Statement|null $newStatement
	 *
	 * @return Diff
	 */
	private function diffReferences( Statement $oldStatement = null, Statement $newStatement = null ) {
		$oldReferences = $oldStatement === null ? new ReferenceList( array() ) : $oldStatement->getReferences();
		$newReferences = $newStatement === null ? new ReferenceList( array() ) : $newStatement->getReferences();

		if ( !$oldReferences->equals( $newReferences ) ) {
			$diffOps = $this->listDiffer->doDiff(
				iterator_to_array( $oldReferences ),
				iterator_to_array( $newReferences )
			);

			return new Diff( $diffOps, false );
		}

		return null;
	}

}
