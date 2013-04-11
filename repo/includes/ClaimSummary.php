<?php

namespace Wikibase;

/**
 * EditSummaries for Claims
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ClaimSummary extends Summary {
	/**
	 * Constructs a new ClaimSummary
	 *
	 * @since 0.4
	 *
	 */
	public function __construct( $moduleName = null ) {
		parent::__construct( $moduleName );
	}

	/**
	 * Checks what has actually changed inside a claim by looking at a ClaimDifference
	 * and constructs a EditSummary based upon that information
	 *
	 * @param ClaimDiffer $claimDiffer
	 * @param Claims $existingClaims
	 * @param Claim $newClaim
	 */
	public function buildClaimSummary( ClaimDiffer $claimDiffer, Claims $existingClaims, Claim $newClaim ) {
		$this->addAutoCommentArgs( 1 ); // only one claim touched, so we're always having singular here
		$summaryArgs = $this->buildSummaryArgs(
			new \Wikibase\Claims( array( $newClaim ) ),
			array($newClaim->getGuid())
		);
		$this->addAutoSummaryArgs( $summaryArgs );

		if ( $existingClaims->hasClaimWithGuid( $newClaim->getGuid() ) ) {
			//claim is changed
			$oldClaim = $existingClaims->getClaimWithGuid( $newClaim->getGuid() );
			$claimDifference = $claimDiffer->diffClaims( $oldClaim, $newClaim );

			if ( $claimDifference->isAtomic() !== true ) {
				// TODO: decide what to do if changes affect multiple part of the claim
				// e.g. concat several autocomments into one?
				$this->setAction( 'update' );
			} else {
				if ( $claimDifference->getMainSnakChange() !== null ) {
					$this->setAction( 'update' );
				} elseif ( $claimDifference->getQualifierChanges()->isEmpty() === false ) {
					$this->addAutoCommentArgs( $claimDifference->getQualifierChanges()->count() );
					$this->setAction( 'update-qualifiers' );
				} elseif ( $claimDifference->getReferenceChanges()->isEmpty() === false ) {
					$this->addAutoCommentArgs( $claimDifference->getReferenceChanges()->count() );
					$this->setAction( 'update-references' );
				} elseif ( $claimDifference->getRankChange() !== null ) {
					$this->setAction( 'update-rank' );
				}
			}
		} else {
			//new claim is added
			$this->setAction( 'create' );
		}
	}

	/**
	 * Build key (property) => value pairs for summary arguments
	 *
	 * @param Claims $claims
	 * @param string[] $guids
	 *
	 * @return mixed[] // propertyId (prefixed) => array of values
	 */
	public function buildSummaryArgs( Claims $claims, array $guids ) {
		$pairs = array();

		foreach( $guids as $guid ) {
			if ( $claims->hasClaimWithGuid( $guid ) ) {
				$snak = $claims->getClaimWithGuid( $guid )->getMainSnak();
				$key = $snak->getPropertyId()->getPrefixedId();

				if ( !array_key_exists( $key, $pairs ) ) {
					$pairs[$key] = array();
				}

				if ( $snak instanceof PropertyValueSnak ) {
					$value = $snak->getDataValue();
				} else {
					$value = '-'; // todo handle no values in general way (needed elsewhere)
				}

				$pairs[$key][] = $value;
			}
		}

		return ( array( $pairs ) );
	}
}
