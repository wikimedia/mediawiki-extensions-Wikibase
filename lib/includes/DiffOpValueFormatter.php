<?php

namespace Wikibase;

use Html;
use Diff;

/**
 * Class for formatting diffs, @todo might be renamed or something....
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class DiffOpValueFormatter {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $oldValue;

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $newValue;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param string $name
	 * @param string $oldValue
	 * @param string $newValue
	 */
	public function __construct( $name, $oldValue, $newValue ) {
		$this->name = $name;
		$this->oldValue = $oldValue;
		$this->newValue = $newValue;
	}

	/**
	 * Generates HTML for the header of the diff operation
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function generateHeaderHtml() {
		$oldHeader = is_string( $this->oldValue ) ? $this->name : '';
		$newHeader = is_string( $this->newValue ) ? $this->name : '';

		$html = Html::openElement( 'tr' );
		$html .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $oldHeader );
		$html .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $newHeader );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Generates HTML for an change diffOp
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function generateChangeOpHtml() {
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
			Html::rawElement( 'div', array(),
				Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ),
					$this->oldValue ) ) );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
			Html::rawElement( 'div', array(),
				Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
					$this->newValue ) ) );
		$html .= Html::closeElement( 'tr' );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Generates HTML for an add diffOp
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function generateAddOpHtml() {
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'colspan'=>'2' ), '&nbsp;' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
			Html::rawElement( 'div', array(),
				Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
					$this->newValue )
			)
		);
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Generates HTML for an remove diffOp
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function generateRemoveOpHtml() {
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
			Html::rawElement( 'div', array(),
				Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ),
					$this->oldValue ) ) );
		$html .= Html::rawElement( 'td', array( 'colspan'=>'2' ), '&nbsp;' );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Generates HTML for a diffOP
	 *
	 * @since 0.4
	 *
	 * @return string
	 */
	public function generateHtml() {
		$html = '';

		if ( is_string( $this->name ) ) {
			$html .= $this->generateHeaderHtml();
		}

		if ( is_string( $this->oldValue ) && is_string( $this->newValue ) ) {
			$html .= $this->generateChangeOpHtml();
		} else if ( is_string( $this->newValue ) ) {
			$html .= $this->generateAddOpHtml();
		} else if ( is_string( $this->oldValue ) ) {
			$html .= $this->generateRemoveOpHtml();
		}

		return $html;
	}

}
