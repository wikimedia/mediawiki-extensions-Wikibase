<?php

namespace Wikibase;
use Html;
use Diff\IDiff;
use Diff\DiffOp;

/**
 * Class for generating views of DiffOp objects.
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class DiffView extends \ContextSource {

	/**
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $path;

	/**
	 * @since 0.1
	 *
	 * @var IDiff
	 */
	protected $diff;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param array $path
	 * @param IDiff $diff
	 * @param \IContextSource|null $contextSource
	 */
	public function __construct( array $path, IDiff $diff, \IContextSource $contextSource = null ) {
		$this->path = $path;
		$this->diff = $diff;

		if ( !is_null( $contextSource ) ) {
			$this->setContext( $contextSource );
		}
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
	 * @throws \MWException
	 */
	protected function generateOpHtml( array $path, DiffOp $op ) {
		if ( $op->isAtomic() ) {
			$html = $this->generateDiffHeaderHtml( implode( ' / ', $path ) );

			//TODO: no path, but localized section title.

			//FIXME: complex objects as values?
			if ( $op->getType() === 'add' ) {
				$html .= $this->generateAddOpHtml( $op->getNewValue() );
			} elseif ( $op->getType() === 'remove' ) {
				$html .= $this->generateRemoveOpHtml( $op->getOldValue() );
			} elseif ( $op->getType() === 'change' ) {
				$html .= $this->generateChangeOpHtml( $op->getNewValue(), $op->getOldValue() );
			} else {
				throw new \MWException( 'Invalid diffOp type' );
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
	 * Generates HTML for an add diffOp
	 *
	 * @since 0.4
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function generateAddOpHtml( $value ) {
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'colspan'=>'2' ), '&nbsp;' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
			Html::rawElement( 'div', array(),
				Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
					$value ) ) );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Generates HTML for an remove diffOp
	 *
	 * @since 0.4
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function generateRemoveOpHtml( $value ) {
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
			Html::rawElement( 'div', array(),
				Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ),
					$value ) ) );
		$html .= Html::rawElement( 'td', array( 'colspan'=>'2' ), '&nbsp;' );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Generates HTML for an change diffOp
	 *
	 * @since 0.4
	 *
	 * @param string $oldValue
	 * @param string $newValue
	 *
	 * @return string
	 */
	protected function generateChangeOpHtml( $oldValue, $newValue ) {
		//TODO: use WordLevelDiff!
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
			Html::rawElement( 'div', array(),
				Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ),
					$oldValue ) ) );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
			Html::rawElement( 'div', array(),
				Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
					$newValue ) ) );
		$html .= Html::closeElement( 'tr' );
		$html .= Html::closeElement( 'tr' );

		return $html;
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
