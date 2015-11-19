<?php

namespace Wikibase\View;

use InvalidArgumentException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\View\Template\TemplateFactory;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class StatementSectionsView {

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var StatementGroupListView
	 */
	private $statementListView;

	public function __construct(
		TemplateFactory $templateFactory,
		StatementGroupListView $statementListView
	) {
		$this->templateFactory = $templateFactory;
		$this->statementListView = $statementListView;
	}

	/**
	 * @param StatementList[] $statementLists
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function getHtml( array $statementLists ) {
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
		$msg = wfMessage( 'wikibase-statementsection-' . strtolower( $key ) );
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
			$msg->escaped(),
			$id,
			$className
		);
	}

}
