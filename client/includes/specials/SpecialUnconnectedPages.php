<?php

/**
 * List client pages that is not connected to repository items.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 */
class SpecialUnconnectedPages extends SpecialWikibaseQueryPage {

	/**
	 * The title as a string to start search at
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $startPage = '';

	/**
	 * The startPage as a title to start search at
	 *
	 * @since 0.4
	 *
	 * @var Title
	 */
	protected $startTitle = null;

	/**
	 * The namespace to filter
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $namespace = null;

	/**
	 * If the search should only include pages with iw-links
	 *
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $iwData = '';

	public function __construct() {
		parent::__construct( 'UnconnectedPages' );

	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.2
	 *
	 * @param string $subPage
	 * @return boolean
	 */
	public function execute( $subPage ) {
		if ( !parent::execute( $subPage ) ) {
			return false;
		}

		# 10 seconds server-side caching max
		$this->getOutput()->setSquidMaxage( 10 );

		$output = $this->getOutput();
		$request = $this->getRequest();

		$namespaces = \Wikibase\Settings::get( 'namespaces' );

		$this->startPage = $request->getText( 'page', $subPage );
		if ( $this->startPage !== '' ) {
			$title = \Title::newFromText( $this->startPage );
			if ( $title !== null && in_array( $title->getNamespace(), $namespaces ) ) {
				$this->startTitle = $title;
			}
		}

		$this->iwData = $request->getText( 'iwdata', '' );

		$out = '';

		if ( $this->startPage !== '' && $this->startTitle === null ) {
			$out .= Html::element( 'div', array(), $this->msg( 'wikibase-unconnectedpages-page-warning' )->text() );
		}

		$out .= Html::openElement(
			'form',
			array(
				'action' => $this->getTitle()->getLocalURL(),
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
		$output->addHTML( $out );

		$this->showQuery();
	}

	/**
	 * @see SpecialWikibaseQueryPage::getResult
	 *
	 * @since 0.4
	 *
	 * @param integer $offset
	 * @param integer $limit
	 *
	 * @return EntityId[]
	 * TODO: it's a bit odd that this returns an array of EntityId
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {

		$dbr = wfGetDB( DB_SLAVE );
		$conds = array();

		$namespaces = \Wikibase\Settings::get( 'namespaces' );
		if ( $this->startTitle !== null ) {
			$conds[] = 'page_title >= ' . $dbr->addQuotes( $this->startTitle->getText() );
			if ( isset( $namespaces[$this->startTitle->getNamespace()] ) ) {
				$conds[] = 'page_namespace = ' . $this->startTitle->getNamespace();
			}
		}
		if ( $this->iwData === 'only' ) {
			$conds[] = 'll_from IS NOT NULL';
		}

		$rows = $dbr->select(
			array(
				'page_props',
				'page',
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
			array_merge(
				array(
					'LENGTH(pp_value) = 0',
					"pp_propname = 'ConnectedItem'"
				),
				$conds
			),
			__METHOD__,
			array(
				'OFFSET' => $offset,
				'LIMIT' => $limit,
				'GROUP BY' => 'page_id',
				'ORDER BY' => 'page_title ASC'
			),
			array(
				'page' => array( 'LEFT JOIN', 'page_id = pp_page' ),
				'langlinks' => array( 'LEFT JOIN', 'page_id = ll_from' )
			)
		);

		$entries = array();
		foreach ( $rows as $row ) {
			$title = \Title::newFromRow( $row );
			$numIWLinks = $row->page_num_iwlinks;
			$entries[] = array( 'title' => $title, 'num' => $numIWLinks);
		}
		return $entries;
	}

	/**
	 * @see SpecialWikibaseQueryPage::formatRow
	 *
	 * @since 0.4
	 *
	 * @param $entry The entry is for this call a row from the select in getResult
	 * TODO: just getting an ID here is odd
	 *
	 * @return string|null
	 */
	protected function formatRow( $entry ) {
		try {
			$out = Linker::linkKnown( $entry['title'] );
			if ( $entry['num'] > 0 ) {
				$out .= ' ' . $this->msg( 'wikibase-unconnectedpages-format-row', $entry['num'] )->text();
			}
			return $out;
		} catch ( MWException $e ) {
			wfWarn( "Error formatting result row: " . $e->getMessage() );
			return false;
		}
	}

	/**
	 * @see SpecialWikibaseQueryPage::getTitleForNavigation
	 *
	 * @since 0.4
	 *
	 * @return Title
	 */
	protected function getTitleForNavigation() {
		return $this->getTitle( $this->startPage );
	}

}
