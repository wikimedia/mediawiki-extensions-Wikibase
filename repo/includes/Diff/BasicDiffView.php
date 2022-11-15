<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Html;
use MWException;
use WordLevelDiff;

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
	 */
	public function getHtml(): string {
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
				'@phan-var DiffOpAdd $op';
				$html .= $this->generateHtmlDiffTableRow(
					null,
					$this->getAddedLine( $op->getNewValue() )
				);
			} elseif ( $op->getType() === 'remove' ) {
				/** @var DiffOpRemove $op */
				'@phan-var DiffOpRemove $op';
				$html .= $this->generateHtmlDiffTableRow(
					$this->getDeletedLine( $op->getOldValue() ),
					null
				 );
			} elseif ( $op->getType() === 'change' ) {
				/** @var DiffOpChange $op */
				'@phan-var DiffOpChange $op';
				$wordLevelDiff = new WordLevelDiff(
					[ $op->getOldValue() ],
					[ $op->getNewValue() ]
				);
				$html .= $this->generateHtmlDiffTableRow(
					$wordLevelDiff->orig()[0],
					$wordLevelDiff->closing()[0]
				 );
			} else {
				throw new MWException( 'Invalid diffOp type' );
			}
		} else {
			$html = '';
			// @phan-suppress-next-line PhanTypeNoPropertiesForeach
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
	 * Generates an HTML table row for a change diffOp
	 * given HTML snippets representing old and new
	 * sides of the Diff
	 */
	protected function generateHtmlDiffTableRow( ?string $oldHtml, ?string $newHtml ): string {
		$html = Html::openElement( 'tr' );
		if ( $oldHtml !== null ) {
			$html .= Html::rawElement( 'td', [ 'class' => 'diff-marker', 'data-marker' => '−' ] );
			$html .= Html::rawElement( 'td', [ 'class' => 'diff-deletedline' ],
				Html::rawElement( 'div', [], $oldHtml ) );
		}
		if ( $newHtml !== null ) {
			if ( $oldHtml === null ) {
				$html .= Html::element( 'td', [ 'colspan' => '2' ], "\u{00A0}" );
			}
			$html .= Html::rawElement( 'td', [ 'class' => 'diff-marker', 'data-marker' => '+' ] );
			$html .= Html::rawElement( 'td', [ 'class' => 'diff-addedline' ],
				Html::rawElement( 'div', [], $newHtml ) );
		}
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	private function getDeletedLine( string $value ): string {
		return $this->getChangedLine( 'del', $value );
	}

	private function getAddedLine( string $value ): string {
		return $this->getChangedLine( 'ins', $value );
	}

	private function getChangedLine( string $tag, string $value ): string {
		return Html::element( $tag, [ 'class' => 'diffchange diffchange-inline' ], $value );
	}

	/**
	 * Generates HTML for the header of the diff operation
	 */
	protected function generateDiffHeaderHtml( string $name ): string {
		$html = Html::openElement( 'tr' );
		$html .= Html::element( 'td', [ 'colspan' => '2', 'class' => 'diff-lineno' ], $name );
		// @phan-suppress-next-line PhanPluginDuplicateAdjacentStatement
		$html .= Html::element( 'td', [ 'colspan' => '2', 'class' => 'diff-lineno' ], $name );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

}
