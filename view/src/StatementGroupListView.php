<?php

namespace Wikibase\View;

use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\View\Template\TemplateFactory;

/**
 * Generates HTML to display statements.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
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
	 * @var EditSectionGenerator
	 */
	private $editSectionGenerator;

	/**
	 * @var ClaimHtmlGenerator
	 */
	private $claimHtmlGenerator;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EntityIdFormatter $propertyIdFormatter
	 * @param EditSectionGenerator $sectionEditLinkGenerator
	 * @param ClaimHtmlGenerator $claimHtmlGenerator
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityIdFormatter $propertyIdFormatter,
		EditSectionGenerator $sectionEditLinkGenerator,
		ClaimHtmlGenerator $claimHtmlGenerator
	) {
		$this->propertyIdFormatter = $propertyIdFormatter;
		$this->editSectionGenerator = $sectionEditLinkGenerator;
		$this->claimHtmlGenerator = $claimHtmlGenerator;
		$this->templateFactory = $templateFactory;
	}

	/**
	 * Builds and returns the HTML representing a WikibaseEntity's statements.
	 *
	 * @since 0.5
	 *
	 * @param Statement[] $statements
	 * @return string HTML
	 */
	public function getHtml( array $statements ) {
		$statementsByProperty = $this->groupStatementsByProperties( $statements );

		$statementsHtml = '';
		foreach ( $statementsByProperty as $statements ) {
			$statementsHtml .= $this->getHtmlForStatementGroupView( $statements );
		}

		return $this->templateFactory->render(
			'wikibase-statementgrouplistview',
			$this->templateFactory->render( 'wikibase-listview', $statementsHtml )
		);
	}

	/**
	 * @param Statement[] $statements
	 *
	 * @return array[]
	 */
	private function groupStatementsByProperties( array $statements ) {
		$byProperty = array();

		foreach ( $statements as $statement ) {
			$propertyId = $statement->getMainSnak()->getPropertyId();
			$byProperty[$propertyId->getSerialization()][] = $statement;
		}

		return $byProperty;
	}

	/**
	 * @param Statement[] $statements
	 *
	 * @return string HTML
	 */
	private function getHtmlForStatementGroupView( array $statements ) {
		$propertyId = $statements[0]->getMainSnak()->getPropertyId();
		$addStatementHtml = $this->editSectionGenerator->getAddStatementToGroupSection( $propertyId );

		return $this->templateFactory->render(
			'wikibase-statementgroupview',
			$this->propertyIdFormatter->formatEntityId( $propertyId ),
			$this->getHtmlForStatementListView( $statements, $addStatementHtml ),
			$propertyId->getSerialization()
		);
	}

	/**
	 * @param Statement[] $statements
	 * @param string $addStatementHtml
	 *
	 * @return string HTML
	 */
	private function getHtmlForStatementListView( array $statements, $addStatementHtml ) {
		$statementViewsHtml = '';

		foreach ( $statements as $statement ) {
			$statementViewsHtml .= $this->claimHtmlGenerator->getHtmlForClaim(
				$statement,
				$this->editSectionGenerator->getStatementEditSection( $statement )
			);
		}

		return $this->templateFactory->render( 'wikibase-statementlistview',
			$statementViewsHtml,
			$addStatementHtml
		);
	}

}
