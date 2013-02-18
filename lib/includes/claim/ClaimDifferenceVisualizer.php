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
	public function visualizeDiff( ClaimDifference $claimDifference, $langCode ) {
		$html = '';

		if ( $claimDifference->getMainSnakChange() !== null ) {
			$mainSnakChange = $claimDifference->getMainSnakChange();
			$valueVisualizer = new DiffOpValueVisualizer(
				$this->getMainSnakHeader( $mainSnakChange->getNewValue(), $langCode ),
				$this->getMainSnakValue( $mainSnakChange->getOldValue(), $langCode ),
				$this->getMainSnakValue( $mainSnakChange->getNewValue(), $langCode )
			);
			$html .= $valueVisualizer->generateHtml();
		}

		if ( $claimDifference->getRankChange() !== null ) {
			$rankChange = $claimDifference->getRankChange();
			$valueVisualizer = new DiffOpValueVisualizer(
				wfMessage( 'wikibase-diffview-rank' ),
				$rankChange->getOldValue(),
				$rankChange->getNewValue()
			);
			$html .= $valueVisualizer->generateHtml();
		}

		// TODO: html for qualifier changes

		if ( $claimDifference->getReferenceChanges() !== null ) {
			$referenceChanges = $claimDifference->getReferenceChanges();

			foreach( $referenceChanges as $referenceChange ) {
				if ( $referenceChange instanceof \Diff\DiffOpAdd ) {
					$html .= $this->getRefHtml( $referenceChange->getNewValue(), $langCode, 'add' );
				} else if ( $referenceChange instanceof \Diff\DiffOpRemove ) {
					$html .= $this->getRefHtml( $referenceChange->getOldValue(), $langCode, 'remove' );
				}
				// todo reference DiffOpChange
			}
		}

		return $html;
	}

	public function getSnakHtml( Snak $mainSnak, $langCode, $type, $prependHeader = null ) {
		$snakHeader = '';
		if ( $prependHeader !== null ) {
			$snakHeader = $prependHeader;
		}
		$snakHeader .= $this->getMainSnakHeader( $mainSnak, $langCode );

		$snakValue = $this->getMainSnakValue( $mainSnak, $langCode );

		if ( $type === 'add' ) {
			$valueVisualizer = new DiffOpValueVisualizer( $snakHeader, null, $snakValue );
		} else if ( $type === 'remove' ) {
			$valueVisualizer = new DiffOpValueVisualizer( $snakHeader, $snakValue, null );
		} else {
			throw new \MWException( 'Unknown diffop type' );
		}

		return $valueVisualizer->generateHtml();
	}

	protected function getMainSnakHeader( Snak $mainSnak, $langCode ) {
		$propertyId = $mainSnak->getPropertyId();
		$property = $this->entityLookup->getEntity( $propertyId );
		$dataTypeLabel = $property->getDataType()->getLabel( $langCode );

		$label = $property->getLabel( $langCode );
		$propertyLabel = $label !== false ? $label : $property->getPrefixedId();

		$headerText = wfMessage( 'wikibase-entity-property' ) . ' / ' . $dataTypeLabel . ' / ' . $label;

		return $headerText;
	}

	protected function getMainSnakValue( Snak $snak, $langCode ) {
		$snakType = $snak->getType();

		if ( $snakType === 'value' ) {
			$dataValue = $snak->getDataValue();

			// FIXME!
			if ( $dataValue instanceof EntityId ) {
				$diffValueString = $this->getEntityLabel( $dataValue, $langCode );
			} else {
				$diffValueString = $dataValue->getValue();
			}

			return $diffValueString;
		}

		return null;
	}

	protected function getEntityLabel( EntityId $entityId , $langCode ) {
		$label = $entityId->getPrefixedId();

		$lookedUpLabel = $this->entityLookup->getEntity( $entityId )->getLabel( $langCode );

		if ( $lookedUpLabel !== false ) {
			$label = $lookedUpLabel;
		}

		return $label;
	}

	protected function getRefHtml( Reference $ref, $langCode, $opType ) {
		$html = '';

		$refSnakList = $ref->getSnaks();
		foreach ( $refSnakList as $snak ) {
			if ( $html !== '' ) {
				$html .= Html::rawElement( 'br', array(), '' );
			}
			$html .= $this->getSnakHtml( $snak, $langCode, $opType,
				wfMessage( 'wikibase-diffview-reference' ) . ' / ' );
		}

		return $html;
	}

}
