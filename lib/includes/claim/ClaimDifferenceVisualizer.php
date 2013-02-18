<?php
namespace Wikibase;

use Html;

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
 * @author Katie Filbert < aude.wiki@gmail.com >
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
	 * @param string $langCode
	 */
	public function __construct( $entityLookup, $langCode ) {
		$this->entityLookup = $entityLookup;
		$this->langCode = $langCode;
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
			$valueFormatter = new DiffOpValueFormatter(
				// todo: should shoe specific headers for both columns
				$this->getMainSnakHeader( $mainSnakChange->getNewValue() ),
				$this->getMainSnakValue( $mainSnakChange->getOldValue() ),
				$this->getMainSnakValue( $mainSnakChange->getNewValue() )
			);
			$html .= $valueFormatter->generateHtml();
		}

		if ( $claimDifference->getRankChange() !== null ) {
			$rankChange = $claimDifference->getRankChange();
			$valueFormatter = new DiffOpValueFormatter(
				wfMessage( 'wikibase-diffview-rank' ),
				$rankChange->getOldValue(),
				$rankChange->getNewValue()
			);
			$html .= $valueFormatter->generateHtml();
		}

		// TODO: html for qualifier changes

		if ( $claimDifference->getReferenceChanges() !== null ) {
			$referenceChanges = $claimDifference->getReferenceChanges();

			// somehow changing a reference value is treated as a diffop add and diffop remove
			// for diff visualization, it should be more like a change
			// @todo assert that both reference changes refer to the same reference
			if ( count( $referenceChanges ) === 2 ) {

				$oldValue = $newValue = null;

				foreach( $referenceChanges as $referenceChange ) {
					if ( $referenceChange instanceof \Diff\DiffOpAdd ) {
						$newValue = $referenceChange->getNewValue();
					} else if ( $referenceChange instanceof \Diff\DiffOpRemove ) {
						$oldValue = $referenceChange->getOldValue();
					}
				}

				$html .= $this->getRefHtml( $oldValue, $newValue, 'change' );
			} else {
				foreach( $referenceChanges as $referenceChange ) {
					if ( $referenceChange instanceof \Diff\DiffOpAdd ) {
						$html .= $this->getRefHtml( null, $referenceChange->getNewValue(), 'add' );
					} else if ( $referenceChange instanceof \Diff\DiffOpRemove ) {
						$html .= $this->getRefHtml( $referenceChange->getOldValue(), null, 'remove' );
					} else if ( $referenceChange instanceof \Diff\DiffOpChange ) {
						$html .= $this->getRefHtml( $referenceChange->getOldValue(),
							$reference->getNewValue(), 'change' );
					}
				}
			}
		}

		return $html;
	}

	/**
	 * Format a snak
	 *
	 * @since 0.4
	 *
	 * @param Snak|null $oldSnak
	 * @param Snak|null $newSnak
	 * @param string $prependHeader
	 *
	 * @return string
	 */
	public function getSnakHtml( $oldSnak, $newSnak, $prependHeader = null ) {
		$snakHeader = '';
		// @todo fix ugly cruft!
		if ( $prependHeader !== null ) {
			$snakHeader = $prependHeader;
		}

		if ( $newSnak instanceof Snak || $oldSnak instanceof Snak ) {
			$headerSnak = $newSnak instanceof Snak ? $newSnak : $oldSnak;
			$snakHeader .= $this->getMainSnakHeader( $headerSnak );
		} else {
			// something went wrong
			throw new \MWException( 'Snak parameters not provided.' );
		}

		$oldValue = null;
		$newValue = null;

		if ( $oldSnak instanceof Snak ) {
			$oldValue = $this->getMainSnakValue( $oldSnak );
		}

		if ( $newSnak instanceof Snak ) {
			$newValue = $this->getMainSnakValue( $newSnak );
		}

		$valueFormatter = new DiffOpValueFormatter( $snakHeader, $oldValue, $newValue );

		return $valueFormatter->generateHtml();
	}

	/**
	 * Get formatted SnakList
	 *
	 * @since 0.4
	 *
	 * @param SnakList $snakList
	 *
	 * @return string
	 */
	 protected function getSnakListHtml( $snakList ) {
		$html = '';

		foreach ( $snakList as $snak ) {

			if ( $html !== '' ) {
				$html .= Html::rawElement( 'br', array(), '' );
			}
			// @fixme
			$html .= $this->getMainSnakValue( $snak );
		}

		return $html;
	}

	/**
	 * Get formatted header for a snak
	 *
	 * @since 0.4
	 *
	 * @param Snak $mainSnak
	 *
	 * @return string
 	 */
	protected function getMainSnakHeader( Snak $mainSnak ) {
		$propertyId = $mainSnak->getPropertyId();
		$property = $this->entityLookup->getEntity( $propertyId );
		$dataTypeLabel = $property->getDataType()->getLabel( $this->langCode );

		$label = $property->getLabel( $this->langCode );
		$propertyLabel = $label !== false ? $label : $property->getPrefixedId();

		$headerText = wfMessage( 'wikibase-entity-property' ) . ' / ' . $dataTypeLabel . ' / ' . $label;

		return $headerText;
	}

	/**
	 * Get main snak value in string form
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 *
	 * @return string
 	 */
	protected function getMainSnakValue( Snak $snak ) {
		$snakType = $snak->getType();

		if ( $snakType === 'value' ) {
			$dataValue = $snak->getDataValue();

			// FIXME!
			if ( $dataValue instanceof EntityId ) {
				$diffValueString = $this->getEntityLabel( $dataValue );
			} else {
				$diffValueString = $dataValue->getValue();
			}

			return $diffValueString;
		}

		// fixme, error handling for unknown snak value types
		return '';
	}

	/**
	 * Get an entity label
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @return string
	 */
	protected function getEntityLabel( EntityId $entityId  ) {
		$label = $entityId->getPrefixedId();

		$lookedUpLabel = $this->entityLookup->getEntity( $entityId )->getLabel( $this->langCode );

		if ( $lookedUpLabel !== false ) {
			$label = $lookedUpLabel;
		}

		return $label;
	}

	/**
	 * Format reference change
	 *
	 * @since 0.4
	 *
	 * @param $oldRef Reference|null
	 * @param $newRef Reference|null
	 * @param $opType string
	 *
	 * @return string
	 */
	protected function getRefHtml( $oldRef, $newRef, $opType ) {
		$html = $oldHtml = $newHtml = '';

		if ( $oldRef !== null ) {
			$oldHtml .= $this->getSnakListHtml( $oldRef->getSnaks() );
		}

		if ( $newRef !== null ) {
			$newHtml .= $this->getSnakListHtml( $newRef->getSnaks() );
		}

		$valueFormatter = new DiffOpValueFormatter(
			wfMessage( 'wikibase-diffview-reference' ),
			$oldHtml,
			$newHtml
		);

		return $valueFormatter->generateHtml();
	}

}
