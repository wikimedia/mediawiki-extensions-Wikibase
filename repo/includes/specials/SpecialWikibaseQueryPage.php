<?php

/**
 * Base for special pages that show the result of a Query. Rewriting of QueryPage but with abstraction of the storage system and without cache support.
 *
 * @since 0.3
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Thomas Pellissier Tanon
 */
abstract class SpecialWikibaseQueryPage extends SpecialWikibasePage {

	/**
	 * The offset in use
	 *
	 * @since 0.3
	 *
	 * @var integer
	 */
	protected $offset = 0;

	/**
	 * The limit in use
	 *
	 * @since 0.3
	 *
	 * @var integer
	 */
	protected $limit = 0;

	/**
	 * The number of rows returned by the query. Reading this variable
	 * only makes sense in functions that are run after the query has been
	 * done.
	 *
	 * @since 0.3
	 *
	 * @var integer
	 */
	protected $numRows;

	/**
	 * Formats a row for display.
	 * If the function returns false, the line output will be skipped.
	 *
	 * @since 0.3
	 *
	 * @param Title $title
	 * @return string|false
	 */
	protected function formatRow( Title $title ) {
		return Linker::linkKnown( $title );
	}

	/**
	 * Return the result of the query as array of Title
	 *
	 * @since 0.3
	 *
	 * @param integer $offset
	 * @param integer $limit
	 * @return Title[]
	 */
	protected abstract function getResult( $offset = 0, $limit = 0 );

	/**
	 * Output the query result
	 *
	 * @since 0.3
	 */
	protected function showQuery() {

		$out = $this->getOutput();

		if ( $this->limit == 0 && $this->offset == 0 ) {
			list( $this->limit, $this->offset ) = $this->getRequest()->getLimitOffset();
		}

		$result = $this->getResult( $this->limit + 1, $this->offset );

		$this->numRows = count( $result );

		$out->addHTML( Xml::openElement( 'div', array( 'class' => 'mw-spcontent' ) ) );

		if ( $this->numRows > 0 ) {
			$out->addHTML( $this->msg( 'showingresults' )->numParams(
				min( $this->numRows, $this->limit ), # do not show the one extra row, if exist
				$this->offset + 1 )->parseAsBlock() );
			# Disable the "next" link when we reach the end
			$paging = $this->getLanguage()->viewPrevNext( $this->getTitleForNavigation(), $this->offset,
				$this->limit, array(), ( $this->numRows <= $this->limit ) );
			$out->addHTML( '<p>' . $paging . '</p>' );
		} else {
			# No results to show, so don't bother with "showing X of Y" etc.
			# -- just let the user know and give up now
			$out->addWikiMsg( 'specialpage-empty' );
			$out->addHTML( Xml::closeElement( 'div' ) );
			return true;
		}

		$this->outputResults(
			$result,
			min( $this->numRows, $this->limit ), # do not format the one extra row, if exist
			$this->offset
		);

		$out->addHTML( '<p>' . $paging . '</p>' );

		$out->addHTML( Xml::closeElement( 'div' ) );

		return true;
	}

	/**
	 * Format and output report results using the given information plus OutputPage
	 *
	 * @since 0.3
	 *
	 * @param $results Title[]
	 * @param integer $num number of available result rows
	 * @param integer $offset paging offset
	 */
	protected function outputResults( array $results, $num, $offset ) {
		global $wgLang;

		if ( $num > 0 ) {
			$html = "\n<ol start='" . ( $offset + 1 ) . "' class='special'>\n";
			for ( $i = 0; $i < $num; $i++ ) {
				$line = $this->formatRow( $results[$i] );
				if ( $line ) {
					$html .= "<li>{$line}</li>\n";
				}
			}
			$html .= "</ol>\n";

			$this->getOutput()->addHTML( $html );
		}
	}

	/**
	 * Oneping tag of the list o
	 *
	 * @since 0.3
	 *
	 * @param integer $offset
	 * @return string
	 */
	protected function openList( $offset ) {
		return ;
	}

	/**
	 * Return the Title of the special page with full subpages informations in oder to be used for naviagtion.
	 *
	 * @since 0.3
	 *
	 * @return Title
	 */
	protected function getTitleForNavigation() {
		return $this->getTitle();
	}
}
