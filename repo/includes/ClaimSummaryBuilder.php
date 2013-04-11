<?php

namespace Wikibase;

/**
 * EditSummary-Builder for claim operations
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ClaimSummaryBuilder {
	/**
	 * @var string
	 */
	private $apiModuleName;

	/**
	 * Constructs a new ClaimSummaryBuilder
	 *
	 * @since 0.4
	 *
	 * @param string $apiModuleName
	 *
	 */
	public function __construct( $apiModuleName ) {
		if ( !is_string( $apiModuleName ) ) {
			throw new \MWException( 'module name is invalid or unknown type.' );
		}

		$this->apiModuleName = $apiModuleName;
	}

	/**
	 * Checks what has actually changed inside a claim by looking at a ClaimDifference,
	 * constructs an edit-summary based upon that information and returns
	 * a Summary object holding this edit-summary
	 *
	 * @param ClaimDiffer $claimDiffer
	 * @param Claims $existingClaims
	 * @param Claim $newClaim
	 *
	 * @return Summary $summary
	 */
	public function buildClaimSummary( ClaimDiffer $claimDiffer, Claims $existingClaims, Claim $newClaim ) {
		$summary = new Summary( $this->apiModuleName );
		
		$summary->addAutoCommentArgs( 1 ); // only one claim touched, so we're always having singular here
		$summaryArgs = $this->buildSummaryArgs(
			new \Wikibase\Claims( array( $newClaim ) ),
			array($newClaim->getGuid())
		);
		$summary->addAutoSummaryArgs( $summaryArgs );

		if ( $existingClaims->hasClaimWithGuid( $newClaim->getGuid() ) ) {
			//claim is changed
			$oldClaim = $existingClaims->getClaimWithGuid( $newClaim->getGuid() );
			$claimDifference = $claimDiffer->diffClaims( $oldClaim, $newClaim );

			if ( $claimDifference->isAtomic() !== true ) {
				// TODO: decide what to do if changes affect multiple part of the claim
				// e.g. concat several autocomments into one?
				$summary->setAction( 'update' );
			} else {
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
				}
			}
		} else {
			//new claim is added
			$summary->setAction( 'create' );
		}

		return $summary;
	}

	/**
	 * Build key (property) => value pairs for summary arguments
	 *
	 * @param Claims $claims
	 * @param string[] $guids
	 *
	 * @return mixed[] // propertyId (prefixed) => array of values
	 */
	protected function buildSummaryArgs( Claims $claims, array $guids ) {
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
