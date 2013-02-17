<?php

namespace Wikibase;

use Html;
use Diff;

/**
 * Class for generating HTML for Claim Diffs.
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
 */
class ClaimDifferenceVisualizer {

	/**
	 * @since 0.4
	 *
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param EntityLookup $entityLookup
	 */
	public function __construct( $entityLookup ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @since 0.4
	 *
	 * @param ClaimDifference $claimDifference
	 *
	 * @return string
	 */
	public function visualizeDiff( ClaimDifference $claimDifference ) {
		$html = $this->generateHeaderHtml( 'TODO: claim difference' ); // TODO

		if ( $claimDifference->getMainSnakChange() !== null ) {
			$mainSnakChange = $claimDifference->getMainSnakChange();
			$html .= $this->generateChangeOpHtml( $mainSnakChange->getOldValue(), $mainSnakChange->getNewValue() );
		}

		if ( $claimDifference->getRankChange() !== null ) {
			$rankChange = $claimDifference->getRankChange();
			$html .= $this->generateChangeOpHtml( $rankChange->getOldValue(), $rankChange->getNewValue() );
		}

		// TODO: html for qualifier changes

		// TODO: html for reference changes

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
	protected function generateHeaderHtml( $name ) {
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
		$html .= Html::rawElement( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
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
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
			Html::rawElement( 'div', array(),
				Html::rawElement( 'del', array( 'class' => 'diffchange diffchange-inline' ),
					$oldValue ) ) );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
			Html::rawElement( 'div', array(),
				Html::rawElement( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
					$newValue ) ) );
		$html .= Html::closeElement( 'tr' );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

}
