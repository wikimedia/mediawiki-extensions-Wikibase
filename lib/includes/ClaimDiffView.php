<?php

namespace Wikibase;
use Html;
use Diff;
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
	private $entityLookup;

	/**
	 * Constructor.
	 *
	 */
	public function __construct( $entityLookup = null ) {
		$this->entityLookup = $entityLookup;
	}

	/**
	 * Get HTML for the property label/id shown in the diff header.
	 *
	 * @since 0.4
	 *
	 * @param EntityId $propertyId
	 *
	 * @return string
	 */
	public function getClaimHeaderPropertyHtml( EntityId $propertyId ) {
		$html = ' / ' . Html::element( 'i', array(), $this->getEntityLabel( $propertyId ) );
		return $html;
	}

	/**
	 * Get HTML for the diff header to indicate what part of the claim has changed.
	 *
	 * @since 0.4
	 *
	 * @param ClaimDifference $claimDifference
	 *
	 * @return string
	 */
	public function getClaimHeaderSnakHtml( ClaimDifference $claimDifference ) {
		$html = '';

		if ( $claimDifference->getReferencesChange() !== null ) {
			$html = ' / ' . wfMessage( 'wikibase-diffview-reference' )->escaped();
		} elseif ( $claimDifference->getRankChange() !== null ) {
			$html = ' / ' . wfMessage( 'wikibase-diffview-rank' )->escaped();
		} elseif ( $claimDifference->getQualifiersChange() !== null ) {
			$html = ' / ' . wfMessage( 'wikibase-diffview-qualifier' )->escaped();
		}

		return $html;
	}

	/**
	 * Get HTML for a list of snaks.
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
	 * Get HTML for a changed snak.
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

	/**
	 * Get HTML for changed statement rank.
	 *
	 * @since 0.4
	 *
	 * @param integer $rank
	 *
	 * @return string
	 * 
	 * TODO: needs to be implemented.
	 */
	public function getRankHtml( $rank ) {
		return 'rank diff html not implemented yet';
	}

	/**
	 * Does all the work for generating the HTML for a given ClaimDifference.
	 *
	 * @since 0.4
	 *
	 * @param ClaimDifference $claimDifference
	 *
	 * @return string
	 */
	public function getClaimDifferenceHtml( ClaimDifference $claimDifference ) {
		$html = '';

		//TODO: we can do shorter.
		if ( $claimDifference->getReferencesChange() !== null ) {
			$diffOp = $claimDifference->getReferencesChange();
			if ( $diffOp->getType() === 'add' ) {
				$newValue = $this->getSnakListHtml( $diffOp->getNewValue() );
				$html = $this->generateAddOpHtml( $newValue );
			} elseif ( $diffOp->getType() === 'remove' ) {
				$oldValue = $this->getSnakListHtml( $diffOp->getOldValue() );
				$html = $this->generateRemoveOpHtml( $oldValue );
			} elseif ( $diffOp->getType() === 'change' ) {
				$oldValue = $this->getSnakListHtml( $diffOp->getOldValue() );
				$newValue = $this->getSnakListHtml( $diffOp->getNewValue() );
				$html = $this->generateChangeOpHtml( $oldValue, $newValue );
			} else {
				throw new MWException( 'diffop unknown' );
			}
		} elseif ( $claimDifference->getMainsnakChange() !== null ) {
			$diffOp = $claimDifference->getMainsnakChange();
			if ( $diffOp->getType() === 'add' ) {
				$newValue = $this->getSnakHtml( $diffOp->getNewValue() );
				$html = $this->generateAddOpHtml( $newValue );
			} elseif ( $diffOp->getType() === 'remove' ) {
				$oldValue = $this->getSnakHtml( $diffOp->getOldValue() );
				$html = $this->generateRemoveOpHtml( $oldValue );
			} elseif ( $diffOp->getType() === 'change' ) {
				$oldValue = $this->getSnakHtml( $diffOp->getOldValue() );
				$newValue = $this->getSnakHtml( $diffOp->getNewValue() );
				$html = $this->generateChangeOpHtml( $oldValue, $newValue );
			} else {
				throw new MWException( 'diffop unknown' );
			}
		} elseif ( $claimDifference->getRankChange() !== null ) {
			$diffOp = $claimDifference->getRankChange();
			if ( $diffOp->getType() === 'add' ) {
				$newValue = $this->getRankHtml( $diffOp->getNewValue() );
				$html = $this->generateAddOpHtml( $newValue );
			} elseif ( $diffOp->getType() === 'remove' ) {
				$oldValue = $this->getRankHtml( $diffOp->getOldValue() );
				$html = $this->generateRemoveOpHtml( $oldValue );
			} elseif ( $diffOp->getType() === 'change' ) {
				$oldValue = $this->getRankHtml( $diffOp->getOldValue() );
				$newValue = $this->getRankHtml( $diffOp->getNewValue() );
				$html = $this->generateChangeOpHtml( $oldValue, $newValue );
			} else {
				throw new MWException( 'diffop unknown' );
			}
		} elseif ( $claimDifference->getQualifiersChange() !== null ) {
			$diffOp = $claimDifference->getQualifiersChange();
			if ( $diffOp->getType() === 'add' ) {
				$newValue = $this->getSnakListHtml( $diffOp->getNewValue() );
				$html = $this->generateAddOpHtml( $newValue );
			} elseif ( $diffOp->getType() === 'remove' ) {
				$oldValue = $this->getSnakListHtml( $diffOp->getOldValue() );
				$html = $this->generateRemoveOpHtml( $oldValue );
			} elseif ( $diffOp->getType() === 'change' ) {
				$oldValue = $this->getSnakListHtml( $diffOp->getOldValue() );
				$newValue = $this->getSnakListHtml( $diffOp->getNewValue() );
				$html = $this->generateChangeOpHtml( $oldValue, $newValue );
			} else {
				throw new MWException( 'diffop unknown' );
			}
		} else {
			throw new MWException( 'empty claimdifference' );
		}

		return $html;
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

		$lookedUpLabel = $this->entityLookup->getEntity( $id )->getLabel(
				$this->getContext()->getLanguage()->getCode()
		);
		if ( $lookedUpLabel !== false ) {
			$label = $lookedUpLabel;
		}

		return $label;
	}
}
