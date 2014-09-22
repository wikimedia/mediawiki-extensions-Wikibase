<?php

namespace Wikibase\Repo\View;

use Linker;
use Wikibase\ClaimHtmlGenerator;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\ReferencedEntitiesFinder;

/**
 * Generates HTML to display claims.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ClaimsView {

	/**
	 * @var EntityInfoBuilderFactory
	 */
	private $entityInfoBuilderFactory;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var ClaimHtmlGenerator
	 */
	private $claimHtmlGenerator;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param EntityInfoBuilderFactory $entityInfoBuilderFactory
	 * @param EnttiyTitleLookup $entityTitleLookup
	 * @param SectionEditLinkGenerator $sectionEditLinkGenerator
	 * @param ClaimHtmlGenerator $claimHtmlGenerator
	 * @param string $languageCode
	 */
	public function __construct(
		EntityInfoBuilderFactory $entityInfoBuilderFactory,
		EntityTitleLookup $entityTitleLookup,
		SectionEditLinkGenerator $sectionEditLinkGenerator,
		ClaimHtmlGenerator $claimHtmlGenerator,
		$languageCode
	) {
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
		$this->claimHtmlGenerator = $claimHtmlGenerator;
		$this->languageCode = $languageCode;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's claims.
	 *
	 * @since 0.5
	 *
	 * @param Claim[] $claims the claims to render
	 * @param string $heading the message key of the heading
	 * @return string
	 */
	public function getHtml( array $claims, $heading = 'wikibase-claims' ) {
		// aggregate claims by properties
		$claimsByProperty = $this->groupClaimsByProperties( $claims );
		$entityInfo = $this->getEntityInfo( $claims, $this->languageCode );

		$claimsHtml = '';
		foreach ( $claimsByProperty as $claims ) {
			$claimsHtml .= $this->getHtmlForClaimGroup( $claims, $entityInfo );
		}

		$claimgrouplistviewHtml = wfTemplate( 'wb-claimgrouplistview', $claimsHtml, '' );

		// TODO: Add link to SpecialPage that allows adding a new claim.
		$sectionHeading = $this->getHtmlForSectionHeading( $heading );
		// FIXME: claimgrouplistview should be the topmost claims related template
		$html = wfTemplate( 'wb-claimlistview', $claimgrouplistviewHtml, '', '' );
		return $sectionHeading . $html;
	}

	/**
	 * Returns the HTML for the heading of the statements section
	 *
	 * @return string
	 */
	private function getHtmlForSectionHeading( $heading ) {
		$html = wfTemplate(
			'wb-section-heading',
			wfMessage( $heading )->escaped(),
			'claims' // ID - TODO: should not be added if output page is not the entity's page
		);

		return $html;
	}

	/**
	 * Groups claims by their properties.
	 *
	 * @param Claim[] $claims
	 * @return Claim[][]
	 */
	private function groupClaimsByProperties( array $claims ) {
		$claimsByProperty = array();
		/** @var Claim $claim */
		foreach ( $claims as $claim ) {
			$propertyId = $claim->getMainSnak()->getPropertyId();
			$claimsByProperty[$propertyId->getNumericId()][] = $claim;
		}
		return $claimsByProperty;
	}

	/**
	 * Fetches labels and descriptions for all entities used as properties in snaks in the given
	 * entity.
	 *
	 * @param Snak[] $claims
	 * @param string $languageCode the language code of the labels to fetch.
	 * @return array[] Entity info array that maps property IDs to labels and descriptions.
	 */
	private function getEntityInfo( array $claims, $languageCode ) {
		// TODO: Share cache with PropertyLabelResolver
		// TODO: ... or share info with getBasicEntityInfo.

		// TODO: Make a finder just for properties, so we don't have to filter.
		$refFinder = new ReferencedEntitiesFinder();
		$snaks = $this->getSnaksFromClaims( $claims );
		$entityIds = $refFinder->findSnakLinks( $snaks );
		$propertyIds = array_filter( $entityIds, function ( EntityId $id ) {
			return $id->getEntityType() === Property::ENTITY_TYPE;
		} );

		// NOTE: This is a bit hackish, it would be more appropriate to use a TermTable here.
		$entityInfoBuilder = $this->entityInfoBuilderFactory->newEntityInfoBuilder( $propertyIds );
		$entityInfoBuilder->removeMissing();
		$entityInfoBuilder->collectTerms(
			array( 'label', 'description' ),
			array( $languageCode )
		);

		return $entityInfoBuilder->getEntityInfo();
	}

	/**
	 * Returns all snaks which are stored in this list of claims.
	 *
	 * @param Claim[] $claims
	 * @return Snak[]
	 */
	private function getSnaksFromClaims( array $claims ) {
		$snaks = array();
		/** @var Claim $claim */
		foreach ( $claims as $claim ) {
			$snaks = array_merge( $snaks, $claim->getAllSnaks() );
		}
		return $snaks;
	}

	/**
	 * Returns the HTML for a group of claims.
	 *
	 * @param Claim[] $claims
	 * @param array $entityInfo
	 * @return string
	 */
	private function getHtmlForClaimGroup( array $claims, array $entityInfo ) {
		$propertyHtml = '';

		$propertyId = $claims[0]->getMainSnak()->getPropertyId();
		$key = $propertyId->getSerialization();
		$propertyLabel = $key;
		if ( isset( $entityInfo[$key] ) && !empty( $entityInfo[$key]['labels'] ) ) {
			$entityInfoLabel = reset( $entityInfo[$key]['labels'] );
			$propertyLabel = $entityInfoLabel['value'];
		}

		$propertyLink = Linker::link(
			$this->entityTitleLookup->getTitleForId( $propertyId ),
			htmlspecialchars( $propertyLabel )
		);

		// TODO: add link to SpecialPage
		$htmlForEditSection = $this->sectionEditLinkGenerator->getHtmlForEditSection(
			'',
			array(),
			'edit',
			wfMessage( 'wikibase-edit' )
		);

		foreach ( $claims as $claim ) {
			$propertyHtml .= $this->claimHtmlGenerator->getHtmlForClaim(
				$claim,
				$entityInfo,
				$htmlForEditSection
			);
		}

		$toolbarHtml = wfTemplate( 'wikibase-toolbar-wrapper',
			$this->sectionEditLinkGenerator->getHtmlForAddSection(
				'',
				array(),
				'add',
				wfMessage( 'wikibase-add' )
			)
		);

		return wfTemplate( 'wb-claimlistview',
			$propertyHtml,
			wfTemplate( 'wb-claimgrouplistview-groupname', $propertyLink ) . $toolbarHtml,
			$propertyId->getSerialization()
		);
	}

}
