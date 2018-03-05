<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\Diff\ClaimDiffer;

/**
 * EditSummary-Builder for claim operations
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ClaimSummaryBuilder {

	/**
	 * @var string
	 */
	private $apiModuleName;

	/**
	 * @var ClaimDiffer
	 */
	private $claimDiffer;

	/**
	 * @param string $apiModuleName
	 * @param ClaimDiffer $claimDiffer
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $apiModuleName, ClaimDiffer $claimDiffer ) {
		if ( !is_string( $apiModuleName ) ) {
			throw new InvalidArgumentException( '$apiModuleName needs to be a string' );
		}

		$this->apiModuleName = $apiModuleName;
		$this->claimDiffer = $claimDiffer;
	}

	/**
	 * Checks what has actually changed inside a statement by looking at a ClaimDifference,
	 * constructs an edit-summary based upon that information and returns
	 * a Summary object holding this edit-summary
	 *
	 * @param Statement|null $oldStatement
	 * @param Statement $newStatement
	 *
	 * @return Summary
	 */
	public function buildClaimSummary( Statement $oldStatement = null, Statement $newStatement ) {
		$guid = $newStatement->getGuid();

		$summary = new Summary( $this->apiModuleName );
		// Only one statement touched, so we're always having singular here.
		$summary->addAutoCommentArgs( 1 );
		$summaryArgs = $this->buildSummaryArgs(
			$newStatement,
			$guid
		);
		$summary->addAutoSummaryArgs( $summaryArgs );

		if ( $oldStatement !== null ) {
			//claim is changed
			$claimDifference = $this->claimDiffer->diffClaims( $oldStatement, $newStatement );

			if ( $claimDifference->isAtomic() ) {
				if ( $claimDifference->getMainSnakChange() !== null ) {
					$summary->setAction( 'update' );
				} elseif ( $claimDifference->getQualifierChanges()->isEmpty() === false ) {
					$summary->addAutoCommentArgs( $claimDifference->getQualifierChanges()->count() );
					$summary->setAction( 'update-qualifiers' );
				} elseif ( $claimDifference->getReferenceChanges()->isEmpty() === false ) {
					$summary->addAutoCommentArgs( $claimDifference->getReferenceChanges()->count() );
					$summary->setAction( 'update-references' );
				} elseif ( $claimDifference->getRankChange() !== null ) {
					$summary->setAction( 'update-rank' );
				} else {
					// something "else" has changed inside the claim, so falling back to plain update message
					$summary->setAction( 'update' );
				}
			} else {
				// TODO: decide what to do if changes affect multiple part of the claim
				// e.g. concat several autocomments into one?
				$summary->setAction( 'update' );
			}
		} else {
			//new claim is added
			$summary->setAction( 'create' );
		}

		return $summary;
	}

	/**
	 * Builds an associative array that can be used as summary arguments. It uses property IDs as
	 * array keys and builds arrays of the main Snaks of all statements given by the GUIDs.
	 *
	 * @param Statement $newStatement
	 * @param string $guid
	 *
	 * @return array[] Associative array that contains property ID => array of main Snaks
	 */
	private function buildSummaryArgs( Statement $newStatement, $guid ) {
		$pairs = [];

		if ( $newStatement->getGuid() === $guid ) {
			$snak = $newStatement->getMainSnak();
			$key = $snak->getPropertyId()->getSerialization();

			if ( !array_key_exists( $key, $pairs ) ) {
				$pairs[$key] = [];
			}

			$pairs[$key][] = $snak;
		}

		return [ $pairs ];
	}

}
