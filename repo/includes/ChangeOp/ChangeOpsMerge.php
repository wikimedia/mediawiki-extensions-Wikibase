<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Reference;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\SiteLinkLookup;
use Wikibase\Term;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpsMerge {

	private $fromItem;
	private $toItem;
	private $fromChangeOps;
	private $toChangeOps;

	/**
	 * @var array
	 */
	private $ignoreConflicts;

	/**
	 * @var LabelDescriptionDuplicateDetector
	 */
	private $labelDescriptionDuplicateDetector;

	/** @var SiteLinkLookup */
	private $sitelinkLookup;

	/**
	 * @var ChangeOpFactory
	 */
	private $changeOpFactory;

	/**
	 * @param Item $fromItem
	 * @param Item $toItem
	 * @param array $ignoreConflicts list of elements to ignore conflicts for
	 *   can only contain 'label' and or 'description' and or 'sitelink'
	 * @param LabelDescriptionDuplicateDetector $labelDescriptionDuplicateDetector
	 * @param SiteLinkLookup $sitelinkLookup
	 * @param ChangeOpFactory $changeOpFactory
	 */
	public function __construct(
		Item $fromItem,
		Item $toItem,
		$ignoreConflicts,
		LabelDescriptionDuplicateDetector $labelDescriptionDuplicateDetector,
		SiteLinkLookup $sitelinkLookup,
		ChangeOpFactory $changeOpFactory
	) {
		$this->assertValidIgnoreConflictValues( $ignoreConflicts );

		$this->fromItem = $fromItem;
		$this->toItem = $toItem;
		$this->fromChangeOps = new ChangeOps();
		$this->toChangeOps = new ChangeOps();
		$this->ignoreConflicts = $ignoreConflicts;
		$this->labelDescriptionDuplicateDetector = $labelDescriptionDuplicateDetector;
		$this->sitelinkLookup = $sitelinkLookup;

		$this->changeOpFactory = $changeOpFactory;
	}

	/**
	 * @param array $ignoreConflicts can contain strings 'label', 'description', 'sitelink'
	 *
	 * @throws InvalidArgumentException
	 */
	private function assertValidIgnoreConflictValues( $ignoreConflicts ) {
		if( !is_array( $ignoreConflicts ) ){
			throw new InvalidArgumentException( '$ignoreConflicts must be an array' );
		}
		foreach( $ignoreConflicts as $ignoreConflict ){
			if(
				$ignoreConflict !== 'label' &&
				$ignoreConflict !== 'description' &&
				$ignoreConflict !== 'sitelink'
			) {
				throw new InvalidArgumentException(
					'$ignoreConflicts array can only contain "label", "description" and or "sitelink" values'
				);
			}
		}
	}

	public function apply() {
		$this->generateChangeOps();
		$this->fromChangeOps->apply( $this->fromItem );
		$this->toChangeOps->apply( $this->toItem );
		$this->applyConstraintChecks();
	}

	private function generateChangeOps() {
		$this->generateLabelsChangeOps();
		$this->generateDescriptionsChangeOps();
		$this->generateAliasesChangeOps();
		$this->generateSitelinksChangeOps();
		$this->generateClaimsChangeOps();
	}

	private function generateLabelsChangeOps() {
		foreach( $this->fromItem->getLabels() as $langCode => $label ){
			$toLabel = $this->toItem->getLabel( $langCode );
			if( $toLabel === false || $toLabel === $label ){
				$this->fromChangeOps->add( $this->changeOpFactory->newRemoveLabelOp( $langCode ) );
				$this->toChangeOps->add( $this->changeOpFactory->newSetLabelOp( $langCode, $label ) );
			} else {
				if( !in_array( 'label', $this->ignoreConflicts ) ){
					throw new ChangeOpException( "Conflicting labels for language {$langCode}" );
				}
			}
		}
	}

	private function generateDescriptionsChangeOps() {
		foreach( $this->fromItem->getDescriptions() as $langCode => $desc ){
			$toDescription = $this->toItem->getDescription( $langCode );
			if( $toDescription === false || $toDescription === $desc ){
				$this->fromChangeOps->add( $this->changeOpFactory->newRemoveDescriptionOp( $langCode ) );
				$this->toChangeOps->add( $this->changeOpFactory->newSetDescriptionOp( $langCode, $desc ) );
			} else {
				if( !in_array( 'description', $this->ignoreConflicts ) ){
					throw new ChangeOpException( "Conflicting descriptions for language {$langCode}" );
				}
			}
		}
	}

	private function generateAliasesChangeOps() {
		foreach( $this->fromItem->getAllAliases() as $langCode => $aliases ){
			$this->fromChangeOps->add( $this->changeOpFactory->newRemoveAliasesOp( $langCode, $aliases ) );
			$this->toChangeOps->add( $this->changeOpFactory->newAddAliasesOp( $langCode, $aliases, 'add' ) );
		}
	}

	private function generateSitelinksChangeOps() {
		foreach( $this->fromItem->getSiteLinks() as $simpleSiteLink ){
			$siteId = $simpleSiteLink->getSiteId();
			if( !$this->toItem->hasLinkToSite( $siteId ) ){
				$this->fromChangeOps->add( $this->changeOpFactory->newRemoveSiteLinkOp( $siteId ) );
				$this->toChangeOps->add(
					$this->changeOpFactory->newSetSiteLinkOp(
						$siteId,
						$simpleSiteLink->getPageName(),
						$simpleSiteLink->getBadges()
					)
				);
			} else {
				if( !in_array( 'sitelink', $this->ignoreConflicts ) ){
					throw new ChangeOpException( "Conflicting sitelinks for {$siteId}" );
				}
			}
		}
	}

	private function generateClaimsChangeOps() {
		foreach( $this->fromItem->getClaims() as $fromClaim ) {
			$this->fromChangeOps->add( $this->changeOpFactory->newRemoveClaimOp( $fromClaim->getGuid() ) );

			$toClaim = clone $fromClaim;
			$toClaim->setGuid( null );
			$toMergeToClaim = false;

			if( $toClaim instanceof Statement ) {
				$toMergeToClaim = $this->findEquivalentClaim( $toClaim );
			}

			if( $toMergeToClaim ) {
				$this->generateReferencesChangeOps( $toClaim, $toMergeToClaim );
			} else {
				$this->toChangeOps->add( $this->changeOpFactory->newSetClaimOp( $toClaim ) );
			}
		}
	}

	/**
	 * Finds a claim in the target entity with the same main snak and qualifiers as $fromStatement
	 *
	 * @param Statement $fromStatement
	 *
	 * @return Claim|bool Claim to merge reference into or false
	 */
	private function findEquivalentClaim( $fromStatement ) {
		/** @var $claim Claim */
		foreach( $this->toItem->getClaims() as $claim ) {
			$fromHash = $this->getClaimHash( $fromStatement );
			$toHash = $this->getClaimHash( $claim );
			if( $toHash === $fromHash ) {
				return $claim;
			}
		}
		return false;
	}

	/**
	 * @param Statement $statement
	 *
	 * @return string combined hash of the Mainsnak and Qualifiers
	 */
	private function getClaimHash( Statement $statement ) {
		return $statement->getMainSnak()->getHash() . $statement->getQualifiers()->getHash();
	}

	/**
	 * @param Statement $fromStatement statement to take references from
	 * @param Statement $toStatement statement to add references to
	 */
	private function generateReferencesChangeOps( Statement $fromStatement, Statement $toStatement ) {
		/** @var $reference Reference */
		foreach ( $fromStatement->getReferences() as $reference ) {
			if ( !$toStatement->getReferences()->hasReferenceHash( $reference->getHash() ) ) {
				$this->toChangeOps->add( $this->changeOpFactory->newSetReferenceOp(
					$toStatement->getGuid(),
					$reference,
					''
				) );
			}
		}
	}

	/**
	 * Throws an exception if it would not be possible to save the second item
	 * @throws ChangeOpException
	 */
	private function applyConstraintChecks() {
		// Whether the labelDescriptionDuplicateDetector being used is real or has been mocked
		$detectorReal = get_class( $this->labelDescriptionDuplicateDetector ) == 'Wikibase\LabelDescriptionDuplicateDetector';

		if ( defined( 'MW_PHPUNIT_TEST' ) && $detectorReal ) {
			// @FIXME: This is a bad hack and should die!
			// Skip the check for conflicting terms if this is being run in a unit test
			// and the LabelDescriptionDuplicateDetector hasn't been mocked, because:
			//  a) MySQL will choke on the self join on a temp table
			//  b) we generally don't care about such conflicts while testing
			$conflictingTerms = array();
		} else {
			$conflictingTerms = $this->labelDescriptionDuplicateDetector->getConflictingTerms(
				$this->toItem
			);
		}
		$conflictingSitelinks = $this->sitelinkLookup->getConflictsForItem( $this->toItem );

		$conflictString = '';
		if( $conflictingTerms !== array() ) {
			$conflictString .= $this->getConflictStringForTerms( $conflictingTerms );
		}
		if( $conflictingSitelinks !== array() ) {
			$conflictString .= $this->getConflictStringForSitelinks( $conflictingSitelinks );
		}

		if( $conflictString !== '' ) {
			throw new ChangeOpException( 'Item being merged to has conflicting terms: ' . $conflictString );
		}
	}

	/**
	 * @param Term[] $conflictingTerms
	 *
	 * @return string
	 */
	private function getConflictStringForTerms( $conflictingTerms ) {
		$conflictString = '';
		foreach( $conflictingTerms as $term ) {
			$conflictString .= $this->getConflictStringForTerm( $term );
		}
		return $conflictString;
	}

	/**
	 * @param Term $term
	 *
	 * @return string
	 */
	private function getConflictStringForTerm( Term $term ) {
		$itemId = $term->getEntityId();
		if( !$this->fromItem->getId()->equals( $itemId ) ) {
			if( $itemId instanceof ItemId ) {
				$termItemIdentity = $itemId->getSerialization();
			} else {
				$termItemIdentity = $itemId; // as this can sometimes be null
			}

			return '(' .
				$termItemIdentity . ' => ' .
				$term->getLanguage() . ' => ' .
				$term->getType() . ' => ' .
				$term->getText() . ') ';
		}
		return '';
	}

	/**
	 * @param array $conflictingSitelinks array of arrays each with the keys:
	 *     - itemId => integer
	 *     - siteId => string
	 *     - sitePage => string
	 * @return string
	 */
	private function getConflictStringForSitelinks( $conflictingSitelinks ) {
		$conflictString = '';
		foreach( $conflictingSitelinks as $sitelink ) {
			$conflictString .= $this->getConflictStringForSitelink( $sitelink );
		}
		return $conflictString;
	}

	/**
	 * @param array $sitelink array with the keys:
	 *     - itemId => integer
	 *     - siteId => string
	 *     - sitePage => string
	 *
	 * @return string
	 */
	private function getConflictStringForSitelink( $sitelink ) {
		$itemId = ItemId::newFromNumber( $sitelink['itemId'] );
		if( !$itemId->equals( $this->fromItem->getId() ) ) {
			return '(' .
				$itemId->getSerialization() . ' => ' .
				$sitelink['siteId'] . ' => ' .
				$sitelink['sitePage'] . ') ';
		}
		return '';
	}

}
