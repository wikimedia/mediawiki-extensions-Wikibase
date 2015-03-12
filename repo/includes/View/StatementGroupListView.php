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
class StatementGroupListView {

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
			$claimsHtml .= $this->getHtmlForStatementGroupView( $claims );
		}

		$html = $this->templateFactory->render(
			'wikibase-statementgrouplistview',
			$this->templateFactory->render( 'wikibase-listview', $claimsHtml )
		);

		// TODO: Add link to SpecialPage that allows adding a new claim.
		$sectionHeading = $this->getHtmlForSectionHeading( 'wikibase-statements' );

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
	 * @param Claim[] $claims
	 * @return string
	 */
	private function getHtmlForStatementGroupView( array $claims ) {
		$propertyId = $claims[0]->getMainSnak()->getPropertyId();

		return $this->templateFactory->render(
			'wikibase-statementgroupview',
			$this->propertyIdFormatter->formatEntityId( $propertyId ),
			$this->getHtmlForStatementListView( $claims ),
			$propertyId->getSerialization()
		);
	}

	/**
	 * @param Claim[] $claims
	 * @return string
	 */
	private function getHtmlForStatementListView( array $claims ) {
		$statementViewsHtml = '';

		// This is just an empty toolbar wrapper. It's used as a marker to the JavaScript so that it places
		// the toolbar at the right position in the DOM. Without this, the JavaScript would just append the
		// toolbar to the end of the element.
		// TODO: Create special pages, link to them
		$toolbarPlaceholderHtml = $this->sectionEditLinkGenerator->getEmptyEditSectionContainer( '' );

		foreach( $claims as $claim ) {
			$statementViewsHtml .= $this->claimHtmlGenerator->getHtmlForClaim(
				$claim,
				$toolbarPlaceholderHtml
			);
		}

		return $this->templateFactory->render( 'wikibase-statementlistview',
			$statementViewsHtml,
			$toolbarPlaceholderHtml
		);
	}

}
