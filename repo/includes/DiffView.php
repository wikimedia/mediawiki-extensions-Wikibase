<?php

namespace Wikibase;

use Diff\Diff;
use Diff\DiffOp;
use Html;
use IContextSource;
use MWException;
use SiteStore;

/**
 * Class for generating views of DiffOp objects.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Adam Shorland
 */
class DiffView extends \ContextSource {

	/** @var SiteStore */
	public $siteStore;

	/**
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $path;

	/**
	 * @since 0.1
	 *
	 * @var Diff
	 */
	protected $diff;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param array $path
	 * @param Diff $diff
	 * @param SiteStore $siteStore
	 * @param IContextSource|null $contextSource
	 */
	public function __construct( array $path, Diff $diff, SiteStore $siteStore, IContextSource $contextSource = null ) {
		$this->path = $path;
		$this->diff = $diff;
		$this->siteStore = $siteStore;

		if ( !is_null( $contextSource ) ) {
			$this->setContext( $contextSource );
		}
		$this->siteStore = $siteStore;
	}

	/**
	 * Builds and returns the HTML to represent the Diff.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHtml() {
		return $this->generateOpHtml( $this->path, $this->diff );
	}

	/**
	 * Does the actual work.
	 *
	 * @since 0.1
	 *
	 * @param array $path
	 * @param DiffOp $op
	 *
	 * @return string
	 * @throws MWException
	 */
	protected function generateOpHtml( array $path, DiffOp $op ) {
		if ( $op->isAtomic() ) {
			$html = $this->generateDiffHeaderHtml( implode( ' / ', $path ) );

			//TODO: no path, but localized section title

			//FIXME: complex objects as values?
			if ( $op->getType() === 'add' ) {
				$html .= $this->generateChangeOpHtml( null, $op->getNewValue(), $path );
			} elseif ( $op->getType() === 'remove' ) {
				$html .= $this->generateChangeOpHtml( $op->getOldValue(), null, $path );
			} elseif ( $op->getType() === 'change' ) {
				$html .= $this->generateChangeOpHtml( $op->getOldValue(), $op->getNewValue(), $path );
			} else {
				throw new MWException( 'Invalid diffOp type' );
			}
		} else {
			$html = '';
			foreach ( $op as $key => $subOp ) {
				$html .= $this->generateOpHtml(
					array_merge( $path, array( $key ) ),
					$subOp
				);
			}
		}

		return $html;
	}

	/**
	 * Generates HTML for an change diffOp
	 *
	 * @since 0.4
	 *
	 * @param string|null $oldValue
	 * @param string|null $newValue
	 * @param array $path
	 *
	 * @return string
	 */
	protected function generateChangeOpHtml( $oldValue, $newValue, $path ) {
		//TODO: use WordLevelDiff!
		$html = Html::openElement( 'tr' );
		if( $oldValue !== null ){
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
				Html::rawElement( 'div', array(), $this->getDeletedLine( $oldValue, $path ) ) );
		}
		if( $newValue !== null ){
			if( $oldValue === null ){
				$html .= Html::rawElement( 'td', array( 'colspan'=>'2' ), '&nbsp;' );
			}
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
			$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
				Html::rawElement( 'div', array(), $this->getAddedLine( $newValue, $path ) ) );
		}
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * @param string $value
	 * @param array $path
	 * @return string
	 */
	protected function getDeletedLine( $value, $path ) {
		// @todo: inject a formatter instead of doing special cases based on the path here!
		if( $path[0] === $this->getLanguage()->getMessage( 'wikibase-diffview-link' ) ) {
			$url = $this->getPageLink( $path[1], $value );

			return Html::rawElement( 'del', array( 'class' => 'diffchange diffchange-inline' ),
				Html::element( 'a', array( 'href' => $url ), $value )
			);
		} else {
			return Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ), $value );
		}
	}

	/**
	 * @param string $value
	 * @param array $path
	 * @return string
	 */
	protected function getAddedLine( $value, $path ) {
		// @todo: inject a formatter instead of doing special cases based on the path here!
		if( $path[0] === $this->getLanguage()->getMessage( 'wikibase-diffview-link' ) ){
			$url = $this->getPageLink( $path[1], $value );

			return Html::rawElement( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
				Html::element( 'a', array( 'href' => $url ), $value )
			);
		} else {
			return Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ), $value );
		}
	}

	/**
	 * @param string $siteId
	 * @param string $pageName
	 *
	 * @return string
	 */
	protected function getPageLink( $siteId, $pageName ) {
		$site = $this->siteStore->getSite( $siteId );
        $url = $site->getPageUrl( $pageName );

		return $url;
	}

	/**
	 * Generates HTML for the header of the diff operation
	 *
	 * @since 0.4
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function generateDiffHeaderHtml( $name ) {
		$html = Html::openElement( 'tr' );
		$html .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
		$html .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}
}
