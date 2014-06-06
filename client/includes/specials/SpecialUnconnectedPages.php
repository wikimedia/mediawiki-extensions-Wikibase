<?php

namespace Wikibase\Client\Specials;

use DatabaseBase;
use Html;
use Linker;
use MWException;
use SpecialPage;
use Title;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\NamespaceChecker;

/**
 * List client pages that is not connected to repository items.
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialUnconnectedPages extends SpecialPage {

	/**
	 * The title as a string to start search at
	 *
	 * @var string
	 */
	private $startPage = '';

	/**
	 * The startPage as a title to start search at
	 *
	 * @var Title|null
	 */
	private $startTitle = null;

	/**
	 * @var NamespaceChecker|null
	 */
	private $namespaceChecker = null;

	/**
	 * If the search should only include pages with iw-links
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	private $iwData = '';

	public function __construct() {
		parent::__construct( 'UnconnectedPages' );
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->msg( 'special-' . strtolower( $this->getName() ) )->text();
	}

	public function setHeaders() {
		$out = $this->getOutput();
		$out->setArticleRelated( false );
		$out->setPageTitle( $this->getDescription() );
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @param null|string $subPage
	 */
	public function execute( $subPage ) {
		$this->setHeaders();

		$contLang = $this->getContext()->getLanguage();
		$this->outputHeader( $contLang->lc( 'wikibase-' . $this->getName() ) . '-summary' );

		// If the user is authorized, display the page, if not, show an error.
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
		}

		$this->setFieldsFromRequestData( $subPage );
		$this->addFormToOutput();
		$this->showQuery();
	}

	private function setFieldsFromRequestData( $subPage ) {
		$request = $this->getRequest();

		$this->startPage = $request->getText( 'page', $subPage );
		if ( $this->startPage !== '' ) {
			$this->startTitle = Title::newFromText( $this->startPage );
		}

		$this->iwData = $request->getText( 'iwdata', '' );
	}

	private function addFormToOutput() {
		$out = '';

		if ( $this->startPage !== '' && $this->startTitle === null ) {
			$out .= Html::element( 'div', array(), $this->msg( 'wikibase-unconnectedpages-page-warning' )->text() );
		}

		$out .= Html::openElement(
				'form',
				array(
					'action' => $this->getPageTitle()->getLocalURL(),
					'name' => 'unconnectedpages',
					'id' => 'wbc-unconnectedpages-form'
				)
			)
			. Html::openElement( 'fieldset' )
			. Html::element( 'legend', array(), $this->msg( 'wikibase-unconnectedpages-legend' )->text() )
			. Html::openElement( 'p' )
			. Html::element( 'label', array( 'for' => 'language' ), $this->msg( 'wikibase-unconnectedpages-page' )->text() )
			. ' '
			. Html::input(
				'page',
				$this->startPage,
				'text',
				array(
					'id' => 'page'
				)
			)
			. Html::input(
				'submit',
				$this->msg( 'wikibase-unconnectedpages-submit' )->text(),
				'submit',
				array(
					'id' => 'wbc-unconnectedpages-submit',
					'class' => 'wbc-input-button'
				)
			)
			. ' '
			. Html::input(
				'iwdata',
				'only',
				'checkbox',
				array(
					'id' => 'wbc-unconnectedpages-iwdata',
					'class' => 'wbc-input-button',
					$this->iwData === 'only' ? 'checked' : 'unchecked' => ''
				)
			)
			. ' '
			. Html::element( 'label', array( 'for' => 'wbc-unconnectedpages-iwdata' ), $this->msg( 'wikibase-unconnectedpages-iwdata-label' )->text() )
			. Html::closeElement( 'p' )
			. Html::closeElement( 'fieldset' )
			. Html::closeElement( 'form' );

		$this->getOutput()->addHTML( $out );
	}

	private function showQuery() {
		$query = array();
		if ( $this->iwData === 'only' ) {
			$query['iwdata'] = $this->iwData;
		}

		$paging = false;
		$out = $this->getOutput();

		list( $limit, $offset ) = $this->getRequest()->getLimitOffset();

		$result = $this->getResult( $offset, $limit + 1 );

		$numRows = count( $result );

		$out->addHTML( Html::openElement( 'div', array( 'class' => 'mw-spcontent' ) ) );

		if ( $numRows > 0 ) {
			$out->addHTML( $this->msg( 'showingresults' )->numParams(
			// do not format the one extra row, if exist
				min( $numRows, $limit ),
				$offset + 1
			)->parseAsBlock() );
			// Disable the "next" link when we reach the end
			$paging = $this->getLanguage()->viewPrevNext(
				$this->getPageTitle( $this->startPage ),
				$offset,
				$limit,
				$query,
				$numRows <= $limit
			);
			$out->addHTML( Html::rawElement( 'p', array(), $paging ) );
		} else {
			// No results to show, so don't bother with "showing X of Y" etc.
			// -- just let the user know and give up now
			$out->addWikiMsg( 'specialpage-empty' );
			$out->addHTML( Html::closeElement( 'div' ) );
		}

		$this->outputResults(
			$result,
			// do not format the one extra row, if it exist
			min( $numRows, $limit ),
			$offset
		);

		if( $paging ) {
			$out->addHTML( Html::rawElement( 'p', array(), $paging ) );
		}

		$out->addHTML( Html::closeElement( 'div' ) );
	}

	/**
	 * Format and output report results using the given information plus OutputPage
	 *
	 * @param EntityId[] $results
	 * @param integer $num number of available result rows
	 * @param integer $offset paging offset
	 */
	private function outputResults( array $results, $num, $offset ) {
		if ( $num <= 0 ) {
			return;
		}

		$html = Html::openElement( 'ol', array( 'start' => $offset + 1, 'class' => 'special' ) );
		for ( $i = 0; $i < $num; $i++ ) {
			$line = $this->formatRow( $results[$i] );
			if ( $line !== false ) {
				$html .= Html::rawElement( 'li', array(), $line );
			}
		}
		$html .= Html::closeElement( 'ol' );

		$this->getOutput()->addHTML( $html );
	}

	/**
	 * Set the NamespaceChecker
	 *
	 * @since 0.4
	 *
	 * @param NamespaceChecker $namespaceChecker
	 */
	public function setNamespaceChecker( NamespaceChecker $namespaceChecker ) {
		$this->namespaceChecker = $namespaceChecker;
	}

	/**
	 * Get the NamespaceChecker
	 *
	 * @since 0.4
	 *
	 * @return NamespaceChecker
	 */
	public function getNamespaceChecker() {
		if ( $this->namespaceChecker === null ) {
			$settings = WikibaseClient::getDefaultInstance()->getSettings();
			$this->namespaceChecker = new NamespaceChecker(
				$settings->getSetting( 'excludeNamespaces' ),
				$settings->getSetting( 'namespaces' )
			);
		}

		return $this->namespaceChecker;
	}

	/**
	 * Build conditionals for namespace
	 *
	 * @since 0.4
	 *
	 * @param DatabaseBase $dbr
	 * @param Title $title
	 * @param NamespaceChecker $checker
	 *
	 * @return string[]
	 */
	public function buildConditionals( $dbr, Title $title = null, $checker = null ) {
		$conds = array();

		if ( $title === null ) {
			$title = $this->startTitle;
		}

		if ( $title !== null ) {
			$conds[] = 'page_title >= ' . $dbr->addQuotes( $title->getDBkey() );
			$conds[] = 'page_namespace = ' . $title->getNamespace();
		} else {
			if ( $checker === null ) {
				$checker = $this->getNamespaceChecker();
			}

			$conds[] = 'page_namespace IN (' . implode(',', $checker->getWikibaseNamespaces() ) . ')';
		}

		return $conds;
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @since 0.4
	 */
	public function getResult( $offset = 0, $limit = 0 ) {
		$dbr = wfGetDB( DB_SLAVE );

		$conds = $this->buildConditionals( $dbr );
		$conds['page_is_redirect'] = '0';
		$conds[] = 'pp_propname IS NULL';
		if ( $this->iwData === 'only' ) {
			$conds[] = 'll_from IS NOT NULL';
		}

		$rows = $dbr->select(
			array(
				'page',
				'page_props',
				'langlinks'
			),
			array(
				'page_namespace',
				'page_title',
				'page_id',
				'page_len',
				'page_is_redirect',
				'page_num_iwlinks' => 'count(ll_from)'
			),
			$conds,
			__METHOD__,
			array(
				'OFFSET' => $offset,
				'LIMIT' => $limit,
				'GROUP BY' => 'page_namespace, page_title',
				'ORDER BY' => 'page_namespace, page_title',
				'USE INDEX' => array( 'page' => 'name_title' )
			),
			array(
				'page_props' => array( 'LEFT JOIN', array( 'page_id = pp_page', "pp_propname = 'wikibase_item'" ) ),
				'langlinks' => array( 'LEFT JOIN', 'll_from = page_id' )
			)
		);

		$entries = array();
		foreach ( $rows as $row ) {
			$title = Title::newFromRow( $row );
			$numIWLinks = $row->page_num_iwlinks;
			$entries[] = array( 'title' => $title, 'num' => $numIWLinks);
		}
		return $entries;
	}

	/**
	 * @param array $entry
	 *
	 * @return string|bool
	 */
	private function formatRow( $entry ) {
		try {
			$out = Linker::linkKnown( $entry['title'] );
			if ( $entry['num'] > 0 ) {
				$out .= ' ' . $this->msg( 'wikibase-unconnectedpages-format-row' )
					->numParams( $entry['num'] )->text();
			}
			return $out;
		} catch ( MWException $e ) {
			wfWarn( "Error formatting result row: " . $e->getMessage() );
			return false;
		}
	}

}
