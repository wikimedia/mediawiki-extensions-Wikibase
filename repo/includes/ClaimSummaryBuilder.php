<?php

namespace Wikibase;

use DataValues\TimeValue;
use InvalidArgumentException;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\TypedValueFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;

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
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * Constructs a new ClaimSummaryBuilder
	 *
	 * @since 0.4
	 *
	 * @param string $apiModuleName
	 * @param ClaimDiffer $claimDiffer
	 * @param EntityIdFormatter $idFormatter
	 * @param DataTypeLookup $dataTypeLookup
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $apiModuleName, ClaimDiffer $claimDiffer,
		EntityIdFormatter $idFormatter, PropertyDataTypeLookup $dataTypeLookup = null ) {
		if ( !is_string( $apiModuleName ) ) {
			throw new InvalidArgumentException( '$apiModuleName needs to be a string' );
		}

		$this->apiModuleName = $apiModuleName;
		$this->claimDiffer = $claimDiffer;
		$this->idFormatter = $idFormatter;

		// todo: inject
		$this->dataTypeLookup = ( $dataTypeLookup === null ) ?
			$this->dataTypeLookup = WikibaseRepo::getDefaultInstance()->getPropertyDataTypeLookup() :
			$dataTypeLookup;
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

				$pairs[$key][] = $this->getFormattedSnakValue( $snak );
			}
		}

		return ( array( $pairs ) );
	}

	protected function getFormattedSnakValue( Snak $snak ) {
		if ( $snak instanceof PropertyValueSnak ) {
			$formatter = $this->getValueFormatterForSnak( $snak );
			$value = $snak->getDataValue();
			$formattedValue = $formatter->format( $value );
		} else {
			$formattedValue = '-'; // todo handle no values in general way (needed elsewhere)
		}

		return $formattedValue;
	}

	/**
	 * Get value formatter for a snak
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 *
	 * @return ValueFormatter
	 */
	protected function getValueFormatterForSnak( Snak $snak ) {
		$propertyId = $snak->getPropertyId();

		$dataTypeId = $this->dataTypeLookup->getDataTypeIdForProperty( $propertyId );
		$dataType = WikibaseRepo::getDefaultInstance()->getDataTypeFactory()->getType( $dataTypeId );

		$valueFormatters = $dataType->getFormatters();

		return $valueFormatters['default'];
	}
}
