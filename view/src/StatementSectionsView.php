<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Traversable;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\View\Template\TemplateFactory;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class StatementSectionsView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var StatementGrouper
	 */
	private $statementGrouper;

	/**
	 * @var StatementGroupListView
	 */
	private $statementListView;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @var VueNoScriptRendering
	 */
	private $vueNoScriptRendering;

	/**
	 * @var bool
	 */
	private $vueStatementsView;

	public function __construct(
		TemplateFactory $templateFactory,
		StatementGrouper $statementGrouper,
		StatementGroupListView $statementListView,
		LocalizedTextProvider $textProvider,
		VueNoScriptRendering $vueNoScriptRendering,
		bool $vueStatementsView
	) {
		$this->templateFactory = $templateFactory;
		$this->statementGrouper = $statementGrouper;
		$this->statementListView = $statementListView;
		$this->textProvider = $textProvider;
		$this->vueNoScriptRendering = $vueNoScriptRendering;
		$this->vueStatementsView = $vueStatementsView;
	}

	/**
	 * @param StatementList $allStatements
	 * @param array<string,StatementList> $groupedStatements
	 * @param string $entityId
	 * @return string HTML
	 */
	private function getVueStatementSectionsHtml(
		StatementList $allStatements,
		array $groupedStatements,
		string $entityId,
	): string {
		$this->vueNoScriptRendering->loadStatementData( $allStatements );
		$rendered = '';
		foreach ( $this->iterateOverNonEmptyStatementSections( $groupedStatements ) as $key => $statementsList ) {
			$rendered .= $this->vueNoScriptRendering->renderStatementsSectionHtml(
				$entityId,
				$this->getHtmlForSectionHeading( $key ),
				$statementsList,
			);
		}
		$rendered .= '<div id="wikibase-wbui2025-status-message-mount-point" aria-live="polite"></div>';
		return $rendered;
	}

	/**
	 * @param EntityId $entityId
	 * @param StatementList $allStatements
	 * @param StatementList[] $groupedStatements
	 * @return string HTML
	 */
	private function getVueStatementsHtml( EntityId $entityId, StatementList $allStatements, array $groupedStatements ): string {
		return "<div id='wikibase-wbui2025-statementgrouplistview'>" .
			$this->getVueStatementSectionsHtml(
				$allStatements,
				$groupedStatements,
				$entityId->getSerialization(),
			) .
			"</div>";
	}

	/**
	 * @param StatementList[] $statementLists
	 */
	private function iterateOverNonEmptyStatementSections( array $statementLists ): Traversable {
		foreach ( $statementLists as $key => $statements ) {
			if ( !is_string( $key ) || !( $statements instanceof StatementList ) ) {
				throw new InvalidArgumentException(
					'$statementLists must be an associative array of StatementList objects'
				);
			}

			if ( $key !== 'statements' && $statements->isEmpty() ) {
				continue;
			}

			yield $key => $statements;
		}
	}

	/**
	 * @param StatementList $statementList
	 * @param ?EntityId $entityId
	 * @param bool $wbui2025Ready whether the caller supports wbui2025
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function getHtml( StatementList $statementList, ?EntityId $entityId = null, bool $wbui2025Ready = false ) {
		$statementLists = $this->statementGrouper->groupStatements( $statementList );
		if ( $wbui2025Ready && $this->vueStatementsView ) {
			Assert::invariant( $entityId !== null, 'entityId should be set when wbui2025Ready' );
			return $this->getVueStatementsHtml( $entityId, $statementList, $statementLists );
		}

		$html = '';
		foreach ( $this->iterateOverNonEmptyStatementSections( $statementLists ) as $key => $statements ) {
			$html .= $this->getHtmlForSectionHeading( $key );
			$html .= $this->statementListView->getHtml( $statements->toArray() );
		}

		return $html;
	}

	/**
	 * @param string $key
	 *
	 * @return string HTML
	 */
	private function getHtmlForSectionHeading( $key ) {
		/**
		 * Message keys:
		 * wikibase-statementsection-statements
		 * wikibase-statementsection-identifiers
		 */
		$messageKey = 'wikibase-statementsection-' . strtolower( $key );
		$className = 'wikibase-statements';

		if ( $key === 'statements' ) {
			$id = 'claims';
		} else {
			$id = $key;
			$className .= ' wikibase-statements-' . $key;
		}

		// TODO: Add link to SpecialPage that allows adding a new statement.
		return $this->templateFactory->render(
			'wb-section-heading',
			$this->textProvider->getEscaped( $messageKey ),
			$id,
			$className
		);
	}

}
