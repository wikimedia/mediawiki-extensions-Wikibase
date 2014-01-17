<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Reference;
use Wikibase\ItemContent;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpsMerge {

	private $fromItemContent;
	private $toItemContent;
	private $fromChangeOps;
	private $toChangeOps;
	/** @var array */
	private $ignoreConflicts;

	/**
	 * @var ClaimGuidValidator
	 */
	private $claimGuidValidator;
	/**
	 * @var ClaimGuidParser
	 */
	private $claimGuidParser;

	/**
	 * @param ItemContent $fromItemContent
	 * @param ItemContent $toItemContent
	 * @param array $ignoreConflicts list of elements to ignore conflicts for
	 *   can only contain 'label' and or 'description' and or 'sitelink'
	 */
	public function __construct(
		ItemContent $fromItemContent,
		ItemContent $toItemContent,
		$ignoreConflicts = array()
	) {
		$this->fromItemContent = $fromItemContent;
		$this->toItemContent = $toItemContent;
		$this->fromChangeOps = new ChangeOps();
		$this->toChangeOps = new ChangeOps();
		$this->assertValidIgnoreConflictValues( $ignoreConflicts );
		$this->ignoreConflicts = $ignoreConflicts;

		//@todo inject me
		$this->claimGuidValidator = WikibaseRepo::getDefaultInstance()->getClaimGuidValidator();
		$this->claimGuidParser = WikibaseRepo::getDefaultInstance()->getClaimGuidParser();
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
			if( $ignoreConflict !== 'label' && $ignoreConflict !== 'description' && $ignoreConflict !== 'sitelink' ){
				throw new InvalidArgumentException( '$ignoreConflicts array can only contain "label", "description" and or "sitelink" values' );
			}
		}
	}

	public function apply() {
		$this->generateChangeOps();
		$this->fromChangeOps->apply( $this->fromItemContent->getItem() );
		$this->toChangeOps->apply( $this->toItemContent->getItem() );
	}

	private function generateChangeOps() {
		$this->generateLabelsChangeOps();
		$this->generateDescriptionsChangeOps();
		$this->generateAliasesChangeOps();
		$this->generateSitelinksChangeOps();
		$this->generateClaimsChangeOps();
	}

	private function generateLabelsChangeOps() {
		foreach( $this->fromItemContent->getItem()->getLabels() as $langCode => $label ){
			$toLabel = $this->toItemContent->getItem()->getLabel( $langCode );
			if( $toLabel === false || $toLabel === $label ){
				$this->fromChangeOps->add( new ChangeOpLabel( $langCode, null ) );
				$this->toChangeOps->add( new ChangeOpLabel( $langCode, $label ) );
			} else {
				if( !in_array( 'label', $this->ignoreConflicts ) ){
					throw new ChangeOpException( "Conflicting labels for language {$langCode}" );
				}
			}
		}
	}

	private function generateDescriptionsChangeOps() {
		foreach( $this->fromItemContent->getItem()->getDescriptions() as $langCode => $desc ){
			$toDescription = $this->toItemContent->getItem()->getDescription( $langCode );
			if( $toDescription === false || $toDescription === $desc ){
				$this->fromChangeOps->add( new ChangeOpDescription( $langCode, null ) );
				$this->toChangeOps->add( new ChangeOpDescription( $langCode, $desc ) );
			} else {
				if( !in_array( 'description', $this->ignoreConflicts ) ){
					throw new ChangeOpException( "Conflicting descriptions for language {$langCode}" );
				}
			}
		}
	}

	private function generateAliasesChangeOps() {
		foreach( $this->fromItemContent->getItem()->getAllAliases() as $langCode => $aliases ){
			$this->fromChangeOps->add( new ChangeOpAliases( $langCode, $aliases, 'remove' ) );
			$this->toChangeOps->add( new ChangeOpAliases( $langCode, $aliases, 'add' ) );
		}
	}

	private function generateSitelinksChangeOps() {
		foreach( $this->fromItemContent->getItem()->getSiteLinks() as $simpleSiteLink ){
			$siteId = $simpleSiteLink->getSiteId();
			if( !$this->toItemContent->getItem()->hasLinkToSite( $siteId ) ){
				$this->fromChangeOps->add( new ChangeOpSiteLink( $siteId, null ) );
				$this->toChangeOps->add( new ChangeOpSiteLink( $siteId, $simpleSiteLink->getPageName() ) );
			} else {
				if( !in_array( 'sitelink', $this->ignoreConflicts ) ){
					throw new ChangeOpException( "Conflicting sitelinks for {$siteId}" );
				}
			}
		}
	}

	private function generateClaimsChangeOps() {
		foreach( $this->fromItemContent->getItem()->getClaims() as $fromClaim ) {
			$this->fromChangeOps->add( new ChangeOpClaimRemove( $fromClaim->getGuid() ) );

			$toClaim = clone $fromClaim;
			$toClaim->setGuid( null );
			$toMergeToClaim = false;

			if( $toClaim instanceof Statement ) {
				$toMergeToClaim = $this->findEquivalentClaim( $toClaim );
			}

			if( $toMergeToClaim ) {
				$this->generateReferencesChangeOps( $toClaim, $toMergeToClaim->getGuid() );
			} else {
			$this->toChangeOps->add( new ChangeOpClaim(
				$toClaim,
				new ClaimGuidGenerator( $this->toItemContent->getItem()->getId() ),
				$this->claimGuidValidator,
				$this->claimGuidParser
				) );
			}
		}
	}

	/**
	 * Finds a claim in the target entity with the same main snak and qualifiers as the given $fromStatement
	 *
	 * @param Statement $fromStatement
	 *
	 * @return Claim|bool Claim to merge reference into or false
	 */
	private function findEquivalentClaim( $fromStatement ) {
		/** @var $claim Claim */
		foreach( $this->toItemContent->getItem()->getClaims() as $claim ) {
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
	 * @param Statement $statement statement to take references from
	 * @param string $claimGuid claim guid to add the references to
	 */
	private function generateReferencesChangeOps( Statement $statement, $claimGuid ) {
		/** @var $reference Reference */
		foreach ( $statement->getReferences() as $reference ) {
			$this->toChangeOps->add( new ChangeOpReference(
				$claimGuid,
				$reference,
				''
			) );
		}
	}

}