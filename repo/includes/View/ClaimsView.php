<?php

namespace Wikibase\Repo\View;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Template\TemplateFactory;

/**
 * Generates HTML to display claims.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Daniel Kinzler
 */
class ClaimsView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var EntityIdFormatter
	 */
	private $propertyIdFormatter;

	/**
	 * @var SectionEditLinkGenerator
	 */
	private $sectionEditLinkGenerator;

	/**
	 * @var ClaimHtmlGenerator
	 */
	private $claimHtmlGenerator;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EntityIdFormatter $propertyIdFormatter
	 * @param SectionEditLinkGenerator $sectionEditLinkGenerator
	 * @param ClaimHtmlGenerator $claimHtmlGenerator
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityIdFormatter $propertyIdFormatter,
		SectionEditLinkGenerator $sectionEditLinkGenerator,
		ClaimHtmlGenerator $claimHtmlGenerator
	) {
		$this->propertyIdFormatter = $propertyIdFormatter;
		$this->sectionEditLinkGenerator = $sectionEditLinkGenerator;
		$this->claimHtmlGenerator = $claimHtmlGenerator;
		$this->templateFactory = $templateFactory;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's claims.
	 *
	 * @since 0.5
	 *
	 * @param Claim[] $claims the claims to render
	 * @return string
	 */
	public function getHtml( array $claims ) {
		// aggregate claims by properties
		$claimsByProperty = $this->groupClaimsByProperties( $claims );

		$claimsHtml = '';
		foreach ( $claimsByProperty as $claims ) {
			$claimsHtml .= $this->getHtmlForClaimGroup( $claims );
		}

		$claimgrouplistviewHtml = $this->templateFactory->render( 'wb-claimgrouplistview', $claimsHtml, '' );

		// TODO: Add link to SpecialPage that allows adding a new claim.
		$sectionHeading = $this->getHtmlForSectionHeading( 'wikibase-statements' );
		// FIXME: claimgrouplistview should be the topmost claims related template
		$html = $this->templateFactory->render( 'wb-claimlistview', $claimgrouplistviewHtml, '', '' );
		return $sectionHeading . $html;
	}

	/**
	 * Returns the HTML for the heading of the statements section
	 *
	 * @param string $heading message key of the heading
	 *
	 * @return string
	 */
	private function getHtmlForSectionHeading( $heading ) {
		$html = $this->templateFactory->render(
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
	 * Returns the HTML for a group of claims.
	 *
	 * @param Claim[] $claims
	 * @return string
	 */
	private function getHtmlForClaimGroup( array $claims ) {
		$propertyHtml = '';

		$propertyId = $claims[0]->getMainSnak()->getPropertyId();
		$propertyLink = $this->propertyIdFormatter->format( $propertyId );

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
				$htmlForEditSection
			);
		}

		$toolbarHtml = $this->templateFactory->render( 'wikibase-toolbar-wrapper',
			$this->sectionEditLinkGenerator->getSingleButtonToolbarHtml(
				'',
				array(),
				'add',
				wfMessage( 'wikibase-add' )
			)
		);

		return $this->templateFactory->render( 'wb-claimlistview',
			$propertyHtml,
			$this->templateFactory->render(
				'wb-claimgrouplistview-groupname',
				$propertyLink
			) . $toolbarHtml,
			$propertyId->getSerialization()
		);
	}

}
