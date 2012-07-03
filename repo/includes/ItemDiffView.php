<?php

namespace Wikibase;
use Content, Html;

/**
 * Difference view for Wikibase items.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 */
class ItemDiffView extends \DifferenceEngine {

	function __construct( $context = null, $old = 0, $new = 0, $rcid = 0, $refreshCache = false, $unhide = false ) {
		parent::__construct($context, $old, $new, $rcid, $refreshCache, $unhide);

		$this->mRefreshCache = true; #FIXME: debug only!
	}

	function addHeader( $diff, $otitle, $ntitle, $multi = '', $notice = '' ) {
		// if we don't want a two column table layout, we have to change this
		return parent::addHeader( $diff, $otitle, $ntitle, $multi, $notice );
	}

	/**
	 * Get a header for a specified revision.
	 *
	 * @param $rev \Revision
	 * @param $complete String: 'complete' to get the header wrapped depending
	 *        the visibility of the revision and a link to edit the page.
	 * @return String HTML fragment
	 */
	protected function getRevisionHeader( \Revision $rev, $complete = '' ) {
		//NOTE: This must be kept in sync with the parent implementation.
		//      Perhaps some parts could be factored out to reduce code duplication.

		$lang = $this->getLanguage();
		$user = $this->getUser();
		$revtimestamp = $rev->getTimestamp();
		$timestamp = $lang->userTimeAndDate( $revtimestamp, $user );
		$dateofrev = $lang->userDate( $revtimestamp, $user );
		$timeofrev = $lang->userTime( $revtimestamp, $user );

		$header = $this->msg(
			$rev->isCurrent() ? 'currentrev-asof' : 'revisionasof',
			$timestamp,
			$dateofrev,
			$timeofrev
		)->escaped();

		if ( $complete !== 'complete' ) {
			return $header;
		}

		$title = $rev->getTitle();

		$header = \Linker::linkKnown( $title, $header, array(),
			array( 'oldid' => $rev->getID() ) );

		if ( $rev->userCan( \Revision::DELETED_TEXT, $user ) ) {
			if ( $title->quickUserCan( 'edit', $user ) ) {
				if ( $rev->isCurrent() ) {
					$editQuery = array( 'action' => 'edit' ); //XXX: does nothing, just like view
					$msg = $this->msg( 'editold' )->escaped();
				} else {
					$editQuery = array( 'action' => 'reset' ); //XXX: not yet implemented
					$editQuery['oldid'] = $rev->getID();
					$msg = $this->msg( 'wikibase-resetold' )->escaped();
				}

				$header .= ' (' . \Linker::linkKnown( $title, $msg, array(), $editQuery ) . ')';
			}

			if ( $rev->isDeleted( \Revision::DELETED_TEXT ) ) {
				$header = Html::rawElement( 'span', array( 'class' => 'history-deleted' ), $header );
			}
		} else {
			$header = Html::rawElement( 'span', array( 'class' => 'history-deleted' ), $header );
		}

		return $header;
	}

	function generateContentDiffBody( Content $old, Content $new ) {
		wfProfileIn( __METHOD__ );

		$diff = ItemDiff::newFromItems( $old->getItem(), $new->getItem() );

		#FIXME: debug only
		$text = print_r( $diff, true );
		$html = '<pre style="display:none">' . htmlspecialchars( $text ) . '</pre>';

		foreach ( $diff as $key => $op ) {
			if ( $op->isEmpty() ) {
				continue;
			}

			$html .= $this->generateOpHtml( array( $key ), $op );
		}

		wfProfileOut( __METHOD__ );
		return $html;
	}

	protected function generateOpHtml( array $path, IDiffOp $op ) {
		$html = '';

		//TODO: no path, but localized section title.
		
		//FIXME: complex objects as values?
		if ( $op->getType() === 'add' ) {
			$html .= Html::openElement( 'tr' );
			$html .= Html::rawElement( 'td', array( 'colspan'=>'2' ), '&#160;' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
				Html::rawElement( 'div', null,
					Html::element( 'ins', array( 'class' => 'diffchange' ),
						$op->getNewValue() ) ) );
			$html .= Html::closeElement( 'tr' );
		} elseif ( $op->getType() === 'remove' ) {
			$html .= Html::openElement( 'tr' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
				Html::rawElement( 'div', null,
					Html::element( 'del', array( 'class' => 'diffchange' ),
						$op->getOldValue() ) ) );
			$html .= Html::rawElement( 'td', array( 'colspan'=>'2' ), '&#160;' );
			$html .= Html::closeElement( 'tr' );
		} elseif ( $op->getType() === 'change' ) {
			//TODO: use WordLevelDiff!

			$html .= Html::openElement( 'tr' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
				Html::rawElement( 'div', null,
					Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ),
						$op->getOldValue() ) ) );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
				Html::rawElement( 'div', null,
					Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
						$op->getNewValue() ) ) );
			$html .= Html::closeElement( 'tr' );
		}

		if ( $html !== '' ) {
			$name = implode( ' / ', $path ); #TODO: l10n

			$header = Html::openElement( 'tr' );
			$header .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
			$header .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
			$header .= Html::closeElement( 'tr' );

			$html = $header . $html;
		} else {
			foreach ( $op as $key => $subOp ) {
				$newPath = array_merge( $path, array( $key ) );
				$html .= $this->generateOpHtml( $newPath, $subOp );
			}
		}

		return $html;
	}

	protected function getParserOutput( \WikiPage $page, \Revision $rev ) { //NOTE: needs a core change to work
		$parserOptions = \ParserOptions::newFromContext( $this->getContext() );
		$parserOptions->enableLimitReport();
		$parserOptions->setTidy( true );

		$parserOptions->setEditSection( false );
		$parserOptions->addExtraKey("diff=1"); // don't poison parser cache with diff-specific stuff

		$parserOutput = $page->getParserOutput( $parserOptions, $rev->getId() );
		return $parserOutput;
	}

}
