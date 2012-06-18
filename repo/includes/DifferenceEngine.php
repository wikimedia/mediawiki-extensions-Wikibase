<?php

namespace Wikibase;
use Content, _DiffOp_Add, _DiffOp_Delete, _DiffOp_Change, _DiffOp_Copy;

/**
 * Difference engine for structured data.
 *
 * @since 0.1
 *
 * @file WikibaseDifferenceEngine.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 */
class DifferenceEngine extends \DifferenceEngine {

	function __construct( $context = null, $old = 0, $new = 0, $rcid = 0, $refreshCache = false, $unhide = false ) {
		parent::__construct($context, $old, $new, $rcid, $refreshCache, $unhide);

		$this->mRefreshCache = true; #FIXME: debug only!
	}

	function addHeader( $diff, $otitle, $ntitle, $multi = '', $notice = '' ) {
		// if we don't want a two column table layout, we have to change this
		return parent::addHeader( $diff, $otitle, $ntitle, $multi, $notice );
	}

	protected function getRevisionHeader( \Revision $rev, $complete = '' ) {
		// if we want to show different links on the revision label, we have to change this
		return parent::getRevisionHeader( $rev, $complete );
	}

	function generateContentDiffBody( Content $old, Content $new ) {
		wfProfileIn( __METHOD__ );

		$diff = ItemDiff::newFromItems( $old, $new );

		#FIXME: debug only
		$text = print_r( $diff, true );
		$html = '<pre style="display:none">' . htmlspecialchars( $text ) . '</pre>';

		foreach ( $diff as $key => $op ) {
			if ( $op->isEmpty() ) continue;

			$html .= $this->generateOpHtml( array($key), $op );
		}

		wfProfileOut( __METHOD__ );
		return $html;
	}

	protected function generateOpHtml( array $path, IDiffOp $op ) {
		$html = '';

		//TODO: no path, but localized section title.
		
		//FIXME: complex objects as values?
		if ( $op instanceof DiffOpAdd ) {
			$html .= \Html::openElement( 'tr' );
			$html .= \Html::rawElement( 'td', array( 'colspan'=>'2' ), '&#160;' );
			$html .= \Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
			$html .= \Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
				\Html::rawElement( 'div', null,
					\Html::element( 'ins', array( 'class' => 'diffchange' ),
						$op->getNewValue() ) ) );
			$html .= \Html::closeElement( 'tr' );
		} elseif ( $op instanceof DiffOpRemove ) {
			$html .= \Html::openElement( 'tr' );
			$html .= \Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
			$html .= \Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
				\Html::rawElement( 'div', null,
					\Html::element( 'del', array( 'class' => 'diffchange' ),
						$op->getOldValue() ) ) );
			$html .= \Html::rawElement( 'td', array( 'colspan'=>'2' ), '&#160;' );
			$html .= \Html::closeElement( 'tr' );
		} elseif ( $op instanceof DiffOpChange ) {
			//TODO: use WordLevelDiff!

			$html .= \Html::openElement( 'tr' );
			$html .= \Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
			$html .= \Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
				\Html::rawElement( 'div', null,
					\Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ),
						$op->getOldValue() ) ) );
			$html .= \Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
			$html .= \Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
				\Html::rawElement( 'div', null,
					\Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
						$op->getNewValue() ) ) );
			$html .= \Html::closeElement( 'tr' );
		}

		if ( $html !== '' ) {
			$name = implode( ' / ', $path ); #TODO: l10n

			$header = \Html::openElement( 'tr' );
			$header .= \Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
			$header .= \Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
			$header .= \Html::closeElement( 'tr' );

			$html = $header . $html;
		} else {
			foreach ( $op as $key => $subop ) {
				$p = array_merge( $path, array( $key ) );
				$html .= $this->generateOpHtml( $p, $subop );
			}
		}

		return $html;
	}

/*
<tr><td colspan="2" class="diff-lineno">Line 2:</td>
	<td colspan="2" class="diff-lineno">Line 2:</td></tr>
<tr><td class='diff-marker'>&#160;</td><td class='diff-context'></td>
	<td class='diff-marker'>&#160;</td><td class='diff-context'></td></tr>
<tr><td class='diff-marker'>&#160;</td><td class='diff-context'><div>Consult the [//meta.wikimedia.org/wiki/Help:Contents User's Guide] for information on using the wiki software.</div></td>
	<td class='diff-marker'>&#160;</td><td class='diff-context'><div>Consult the [//meta.wikimedia.org/wiki/Help:Contents User's Guide] for information on using the wiki software.</div></td></tr>
<tr><td colspan="2">&#160;</td><td class='diff-marker'>+</td>
	<td class='diff-addedline'><div><ins class="diffchange"></ins></div></td></tr>
<tr><td colspan="2">&#160;</td><td class='diff-marker'>+</td><td class='diff-addedline'><div><ins class="diffchange">...!</ins></div></td></tr>
<tr><td class='diff-marker'>&#160;</td><td class='diff-context'></td><td class='diff-marker'>&#160;</td><td class='diff-context'></td></tr>
<tr><td class='diff-marker'>&#160;</td><td class='diff-context'><div>== Getting started ==</div></td><td class='diff-marker'>&#160;</td><td class='diff-context'><div>== Getting started ==</div></td></tr>
*/

}
