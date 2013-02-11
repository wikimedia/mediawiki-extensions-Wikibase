<?php

namespace Wikibase;
use Html;
use Diff\IDiff;
use Diff\DiffOp;

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
class ClaimDiffView extends DiffView {

	/**
	 * @since 0.4
	 *
	 * @var EntityLookup|null
	 */
	private static $entityLookup;

	/**
	 * Constructor.
	 *
	 */
	public function __construct( $entityLookup = null ) {
		self::$entityLookup = $entityLookup;
	}

	/**
	 * Get HTML for a list of snaks
	 *
	 * @since 0.4
	 *
	 * @param SnakList $snakList
	 *
	 * @return string
	 *
	 * TODO: This is just showing all snaks in a list. Could be improved.
	 */
	public function getSnakListHtml( SnakList $snakList ) {
		$html = '';
		foreach ( $snakList as $snak ) {
			if ( $html !== '' ) {
				$html .= Html::rawElement( 'br', array(), '' );
			}
			$html .= $this->getSnakHtml( $snak );
		}

		return $html;
	}

	/**
	 * Get HTML for a changed snak
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 *
	 * @return string
	 */
	public function getSnakHtml( Snak $snak ) {
		$snakType = $snak->getType();
		$diffValueString = Html::rawElement( 'i', array(), $snakType );

		if ( $snakType === 'value' ) {
			$dataValue = $snak->getDataValue();

			//FIXME: This will break for types other than EntityId or StringValue
			//we do not have a generic way to get string representations of the values yet
			if ( $dataValue instanceof EntityId ) {
				$diffValueString = $this->getEntityLabel( $dataValue );
			} else {
				$diffValueString = $dataValue->getValue();
			}
		}

		return $diffValueString;
	}

	public function getRankHtml( int $rank ) {
		return 'rank diff html not implemented yet';
	}

	public function getClaimDifferenceHtml( $claimDifference ) {
		$type = $claimDifference['type'];
		$diffOp = $claimDifference['diff'];
		if ( $diffOp->getType() === 'add' ) {
			if( $type === 'refs' || $type === 'q' ) {
				$newValue = $this->getSnakListHtml( $diffOp->getNewValue() );
			} elseif( $type === 'm' ) {
				$newValue = $this->getSnakHtml( $diffOp->getNewValue()->getMainSnak() );
			} elseif( $type === 'r' ) {
				$newValue = $this->getRankHtml( $diffOp->getNewValue()->getRank() );
			}
			return $this->generateAddOpHtml( $newValue );
		} elseif ( $diffOp->getType() === 'remove' ) {
			if( $type === 'refs' || $type === 'q' ) {
				$oldValue = $this->getSnakListHtml( $diffOp->getOldValue() );
			} elseif( $type === 'm' ) {
				$oldValue = $this->getSnakHtml( $diffOp->getOldValue()->getMainSnak() );
			} elseif( $type === 'r' ) {
				$oldValue = $this->getRankHtml( $diffOp->getOldValue()->getRank() );
			}
			return $this->generateRemoveOpHtml( $oldValue );
		} elseif ( $diffOp->getType() === 'change' ) {
			if( $type === 'refs' || $type === 'q' ) {
				$oldValue = $this->getSnakListHtml( $diffOp->getOldValue() );
				$newValue = $this->getSnakListHtml( $diffOp->getNewValue() );
			} elseif( $type === 'm' ) {
				$oldValue = $this->getSnakHtml( $diffOp->getOldValue()->getMainSnak() );
				$newValue = $this->getSnakHtml( $diffOp->getNewValue()->getMainSnak() );
			} elseif( $type === 'r' ) {
				$oldValue = $this->getRankHtml( $diffOp->getOldValue()->getRank() );
				$newValue = $this->getRankHtml( $diffOp->getNewValue()->getRank() );
			}
			return $this->generateChangeOpHtml( $oldValue, $newValue );
		}
	}


	/**
	 * Get the label of an entity represented by its EntityId
	 *
	 * @since 0.4
	 *
	 * @param EntityId $id
	 *
	 * @return string
	 */
	private function getEntityLabel( EntityId $id ) {
		$label = $id->getPrefixedId();

		$lookedUpLabel = self::$entityLookup->getEntity( $id )->getLabel(
				$this->getContext()->getLanguage()->getCode()
		);
		if ( $lookedUpLabel !== false ) {
			$label = $lookedUpLabel;
		}

		return $label;
	}
}
