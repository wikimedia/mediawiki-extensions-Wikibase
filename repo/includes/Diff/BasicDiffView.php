<?php

namespace Wikibase\Repo\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Html;
use MWException;

/**
 * Class for generating views of DiffOp objects.
 *
 * @license GPL-2.0-or-later
 */
class BasicDiffView implements DiffView {

	/**
	 * @var string[]
	 */
	private $path;

	/**
	 * @var Diff
	 */
	private $diff;

	/**
	 * @param string[] $path
	 * @param Diff $diff
	 */
	public function __construct( array $path, Diff $diff ) {
		$this->path = $path;
		$this->diff = $diff;
	}

	/**
	 * Builds and returns the HTML to represent the Diff.
	 *
	 * @return string
	 */
	public function getHtml() {
		return $this->generateOpHtml( $this->path, $this->diff );
	}

	/**
	 * Does the actual work.
	 *
	 * @param string[] $path
	 * @param DiffOp $op
	 *
	 * @return string
	 * @throws MWException
	 */
	protected function generateOpHtml( array $path, DiffOp $op ) {
		if ( $op->isAtomic() ) {
			$localizedPath = $path;

			$html = $this->generateDiffHeaderHtml( implode( ' / ', $localizedPath ) );

			//TODO: no path, but localized section title

			//FIXME: complex objects as values?
			if ( $op->getType() === 'add' ) {
				/** @var DiffOpAdd $op */
				$html .= $this->generateChangeOpHtml( null, $op->getNewValue(), $path );
			} elseif ( $op->getType() === 'remove' ) {
				/** @var DiffOpRemove $op */
				$html .= $this->generateChangeOpHtml( $op->getOldValue(), null, $path );
			} elseif ( $op->getType() === 'change' ) {
				/** @var DiffOpChange $op */
				$html .= $this->generateChangeOpHtml( $op->getOldValue(), $op->getNewValue(), $path );
			} else {
				throw new MWException( 'Invalid diffOp type' );
			}
		} else {
			$html = '';
			foreach ( $op as $key => $subOp ) {
				$html .= $this->generateOpHtml(
					array_merge( $path, [ $key ] ),
					$subOp
				);
			}
		}

		return $html;
	}

	/**
	 * Generates HTML for an change diffOp
	 *
	 * @param string|null $oldValue
	 * @param string|null $newValue
	 * @param string[] $path
	 *
	 * @return string
	 */
	protected function generateChangeOpHtml( $oldValue, $newValue, array $path ) {
		//TODO: use WordLevelDiff!
		$html = Html::openElement( 'tr' );
		if ( $oldValue !== null ) {
			$html .= Html::rawElement( 'td', [ 'class' => 'diff-marker' ], '-' );
			$html .= Html::rawElement( 'td', [ 'class' => 'diff-deletedline' ],
				Html::rawElement( 'div', [], $this->getDeletedLine( $oldValue, $path ) ) );
		}
		if ( $newValue !== null ) {
			if ( $oldValue === null ) {
				$html .= Html::rawElement( 'td', [ 'colspan' => '2' ], '&nbsp;' );
			}
			$html .= Html::rawElement( 'td', [ 'class' => 'diff-marker' ], '+' );
			$html .= Html::rawElement( 'td', [ 'class' => 'diff-addedline' ],
				Html::rawElement( 'div', [], $this->getAddedLine( $newValue, $path ) ) );
		}
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * @param string $value
	 * @param string[] $path
	 *
	 * @return string
	 */
	private function getDeletedLine( $value, array $path ) {
		return $this->getChangedLine( 'del', $value, $path );
	}

	/**
	 * @param string $value
	 * @param string[] $path
	 *
	 * @return string
	 */
	private function getAddedLine( $value, array $path ) {
		return $this->getChangedLine( 'ins', $value, $path );
	}

	/**
	 * @param string $tag
	 * @param string $value
	 * @param string[] $path
	 *
	 * @return string
	 */
	private function getChangedLine( $tag, $value, array $path ) {
		return Html::element( $tag, [ 'class' => 'diffchange diffchange-inline' ], $value );
	}

	/**
	 * Generates HTML for the header of the diff operation
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function generateDiffHeaderHtml( $name ) {
		$html = Html::openElement( 'tr' );
		$html .= Html::element( 'td', [ 'colspan' => '2', 'class' => 'diff-lineno' ], $name );
		$html .= Html::element( 'td', [ 'colspan' => '2', 'class' => 'diff-lineno' ], $name );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

}
