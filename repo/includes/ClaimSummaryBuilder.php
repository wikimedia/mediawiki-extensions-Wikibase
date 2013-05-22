<?php

namespace Wikibase;

use DataValues\TimeValue;
use InvalidArgumentException;
use Wikibase\Lib\EntityIdFormatter;

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
	 * @var ClaimDiffer
	 */
	private $claimDiffer;

	/**
	 * @var EntityIdFormatter
	 */
	private $idFormatter;

	/**
	 * Constructs a new ClaimSummaryBuilder
	 *
	 * @since 0.4
	 *
	 * @param string $apiModuleName
	 * @param ClaimDiffer $claimDiffer
	 * @param EntityIdFormatter $idFormatter
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $apiModuleName, ClaimDiffer $claimDiffer, EntityIdFormatter $idFormatter ) {
		if ( !is_string( $apiModuleName ) ) {
			throw new InvalidArgumentException( '$apiModuleName needs to be a string' );
		}

		$this->apiModuleName = $apiModuleName;
		$this->claimDiffer = $claimDiffer;
		$this->idFormatter = $idFormatter;
	}

	/**
	 * Checks what has actually changed inside a claim by looking at a ClaimDifference,
	 * constructs an edit-summary based upon that information and returns
	 * a Summary object holding this edit-summary
	 *
	 * @param Claims $existingClaims
	 * @param Claim $newClaim
	 *
	 * @return Summary $summary
	 */
	public function buildClaimSummary( Claims $existingClaims, Claim $newClaim ) {
		$summary = new Summary( $this->apiModuleName );

		$summary->addAutoCommentArgs( 1 ); // only one claim touched, so we're always having singular here
		$summaryArgs = $this->buildSummaryArgs(
			new Claims( array( $newClaim ) ),
			array($newClaim->getGuid())
		);
		$summary->addAutoSummaryArgs( $summaryArgs );

		if ( $existingClaims->hasClaimWithGuid( $newClaim->getGuid() ) ) {
			//claim is changed
			$oldClaim = $existingClaims->getClaimWithGuid( $newClaim->getGuid() );
			$claimDifference = $this->claimDiffer->diffClaims( $oldClaim, $newClaim );

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
				$key = $this->idFormatter->format( $snak->getPropertyId() );

				if ( !array_key_exists( $key, $pairs ) ) {
					$pairs[$key] = array();
				}

				if ( $snak instanceof PropertyValueSnak ) {
					$value = $snak->getDataValue();
					// TODO: we should use value formatters here!
					if ( $value instanceof TimeValue ) {
						$value = $value->getTime();
					}
				} else {
					$value = '-'; // todo handle no values in general way (needed elsewhere)
				}

				$pairs[$key][] = $value;
			}
		}

		return ( array( $pairs ) );
	}
}
