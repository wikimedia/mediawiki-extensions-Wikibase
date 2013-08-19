<?php

namespace Wikibase;

use Wikibase\Lib\ClaimGuidGenerator;

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

	/**
	 * @param ItemContent $fromItemContent
	 * @param ItemContent $toItemContent
	 */
	public function __construct( ItemContent $fromItemContent, ItemContent $toItemContent ) {
		$this->fromItemContent = $fromItemContent;
		$this->toItemContent = $toItemContent;
		$this->fromChangeOps = new ChangeOps();
		$this->toChangeOps = new ChangeOps();

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
				//todo add the option to merge conflicting labels into the aliases
				throw new ChangeOpException( "Conflicting labels for language {$langCode}" );
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
				//todo add the option to ignore description conflicts, or prioritise one
				throw new ChangeOpException( "Conflicting descriptions for language {$langCode}" );
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
		foreach( $this->fromItemContent->getItem()->getSimpleSiteLinks() as $simpleSiteLink ){
			$siteId = $simpleSiteLink->getSiteId();
			if( !$this->toItemContent->getItem()->hasLinkToSite( $siteId ) ){
				$this->fromChangeOps->add( new ChangeOpSiteLink( $siteId, null ) );
				$this->toChangeOps->add( new ChangeOpSiteLink( $siteId, $simpleSiteLink->getPageName() ) );
			} else {
				throw new ChangeOpException( "Conflicting sitelinks for {$siteId}" );
			}
		}
	}

	private function generateClaimsChangeOps() {
		foreach( $this->fromItemContent->getItem()->getClaims() as $fromClaim ){
			$this->fromChangeOps->add( new ChangeOpClaim( $fromClaim, 'remove', new ClaimGuidGenerator( $this->fromItemContent->getItem()->getId() ) ) );
			$fromClaim->setGuid( null );
			$this->toChangeOps->add( new ChangeOpClaim( $fromClaim , 'add', new ClaimGuidGenerator( $this->toItemContent->getItem()->getId() ) ) );
		}
	}

}