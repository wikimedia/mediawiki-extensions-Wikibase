<?php

namespace Wikibase;
use IContextSource;
use Html;
use Diff\IDiff;
use Diff\DiffOp;

/**
 * Class for generating views of EntityDiff objects.
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */

class EntityDiffView extends DiffView {

	/**
	 * @since 0.4
	 *
	 * @var ClaimDiffer|null
	 */
	private static $claimDiffer;

	/**
	 * @since 0.4
	 *
	 * @var ClaimDiffView|null
	 */
	private static $claimDiffView;

	/**
	 * Returns a new EntityDiffView for the provided EntityDiff.
	 *
	 * @since 0.4
	 *
	 * @param EntityDiff $diff
	 * @param IContextSource $contextSource
	 * @param EntityLookup|null $entityLookup
	 *
	 * @return EntityDiffView
	 */
	public static function newForDiff( EntityDiff $diff, IContextSource $contextSource = null, $claimDiffer = null, $claimDiffView = null ) {
		self::$claimDiffer = $claimDiffer;
		self::$claimDiffView = $claimDiffView;
		return new static( array(), $diff, $contextSource );
		// TODO: grep for new EntityDiffView and rep by this
	}

	/**
	 * Does the actual work.
	 *
	 * @since 0.4
	 *
	 * @param array $path
	 * @param DiffOp $op
	 *
	 * @return string
	 * @throws \MWException
	 */
	protected function generateOpHtml( array $path, DiffOp $op ) {
		if ( $op->isAtomic() ) {
			if ( $path[0] === 'claim' ) {
				if ( $op->getType() === 'change' || $op->getType() === 'remove' ) {
					$propertyId = $op->getOldValue()->getPropertyId();
				} elseif ( $op->getType() === 'add' ) {
					$propertyId = $op->getNewValue()->getPropertyId();
				}
				$name = $path[0] . ' / ';# . Html::element( 'i', array(), $this->getEntityLabel( $propertyId ) );
			} else {
				$name = implode( ' / ', $path ); // TODO: l10n
			}

			$diffHtml = '';

			if ( $op->getType() === 'add' ) {
				$newValue = $op->getNewValue();
				if ( !is_string( $newValue ) && $path[0] === 'claim' ) {
					$newValue = self::$claimDiffView->getSnakHtml( $newValue->getMainSnak() );
				}
				$diffHtml .= $this->generateAddOpHtml( $newValue );
			} elseif ( $op->getType() === 'remove' ) {
				$oldValue = $op->getOldValue();
				if ( !is_string( $oldValue ) && $path[0] === 'claim' ) {
					$oldValue = self::$claimDiffView->getSnakHtml( $oldValue->getMainSnak() );
				}
				$diffHtml .= $this->generateRemoveOpHtml( $oldValue );
			} elseif ( $op->getType() === 'change' ) {
				$newValue = $op->getNewValue();
				$oldValue = $op->getOldValue();

				if ( !is_string( $newValue ) && $path[0] === 'claim' ) {
					$claimDifference = self::$claimDiffer->diffClaims( $oldValue, $newValue );
					$diffHtml .= self::$claimDiffView->getClaimDifferenceHtml( $claimDifference );
				} else {
					$diffHtml .= $this->generateChangeOpHtml( $oldValue, $newValue );
				}
			}
			else {
				throw new \MWException( 'Invalid diffOp type' );
			}
			$html = $this->generateDiffHeaderHtml( $name ) . $diffHtml;
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

}