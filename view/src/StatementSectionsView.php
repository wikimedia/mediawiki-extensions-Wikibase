<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\View\Template\TemplateFactory;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
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

	public function __construct(
		TemplateFactory $templateFactory,
		StatementGrouper $statementGrouper,
		StatementGroupListView $statementListView
	) {
		$this->templateFactory = $templateFactory;
		$this->statementGrouper = $statementGrouper;
		$this->statementListView = $statementListView;
	}

	/**
	 * @param StatementList $statementList
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function getHtml( StatementList $statementList ) {
		$statementLists = $this->statementGrouper->groupStatements( $statementList );
		$html = '';

		foreach ( $statementLists as $key => $statements ) {
			if ( !is_string( $key ) || !( $statements instanceof StatementList ) ) {
				throw new InvalidArgumentException(
					'$statementLists must be an associative array of StatementList objects'
				);
			}

			if ( $key !== 'statements' && $statements->isEmpty() ) {
				continue;
			}

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
			htmlspecialchars( wfMessage( $messageKey )->text() ),
			$id,
			$className
		);
	}

}
