<?php
namespace Wikibase;

use Html;
use Diff\Diff;

/**
 * Class for generating HTML for Claim Diffs.
 *
 * @todo we might want a SnakFormatter class and others that handle specific stuff
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
	 * Generates HTML of a claim change.
	 * @since 0.4
	 *
	 * @param ClaimDifference $claimDifference
	 * @param Claim $baseClaim
	 *
	 * @return string
	 */
	public function visualizeClaimChange( ClaimDifference $claimDifference, Claim $baseClaim ) {
		$html = '';

		if ( $claimDifference->getMainSnakChange() !== null ) {
			$html .= $this->visualizeMainSnakChange( $claimDifference->getMainSnakChange() );
		}

		if ( $claimDifference->getRankChange() !== null ) {
			$html .= $this->visualizeRankChange( $claimDifference->getRankChange() );
		}

		// TODO: html for qualifier changes

		if ( $claimDifference->getReferenceChanges() !== null ) {
			$html .= $this->visualizeReferenceChanges(
				$claimDifference->getReferenceChanges(),
				$baseClaim
			);
		}

		return $html;
	}

	/**
	 * Get diff html for a new claim
	 *
	 * @since 0.4
	 *
	 * @param Claim $claim
	 *
	 * @return string
	 */
	public function visualizeNewClaim( Claim $claim ) {
		$mainSnak = $claim->getMainSnak();

		$html = '';

		$html .= $this->getSnakHtml(
			null,
			$mainSnak
		);

		return $html;
	}

	/**
	 * Get diff html for a removed claim
	 *
	 * @since 0.4
	 *
	 * @param Claim $claim
	 *
	 * @return string
	 */
	public function visualizeRemovedClaim( Claim $claim ) {
		$mainSnak = $claim->getMainSnak();

		$html = '';

		$html .= $this->getSnakHtml(
			$mainSnak,
			null
		);

		return $html;
	}

	/**
	 * Get Html for a main snak change
	 *
	 * @since 0.4
	 *
	 * @param $mainSnakChange
	 *
	 * @return string
	 */
	protected function visualizeMainSnakChange( $mainSnakChange ) {
		$valueFormatter = new DiffOpValueFormatter(
			// todo: should show specific headers for both columns
			$this->getMainSnakHeader( $mainSnakChange->getNewValue() ),
			$this->getMainSnakValue( $mainSnakChange->getOldValue() ),
			$this->getMainSnakValue( $mainSnakChange->getNewValue() )
		);

		return $valueFormatter->generateHtml();
	}

	/**
	 * Get Html for rank change
	 *
	 * @since 0.4
	 *
	 * @param $rankChange
	 *
	 * @return string
	 */
	protected function visualizeRankChange( $rankChange ) {
		$valueFormatter = new DiffOpValueFormatter(
			wfMessage( 'wikibase-diffview-rank' ),
			$rankChange->getOldValue(),
			$rankChange->getNewValue()
		);
		return $valueFormatter->generateHtml();
	}

	/**
	 * Format a snak
	 *
	 * @since 0.4
	 *
	 * @param Snak|null $oldSnak
	 * @param Snak|null $newSnak
	 * @param string|null $prependHeader
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
	 * Get formatted values of SnakList in an array
	 *
	 * @since 0.4
	 *
	 * @param SnakList $snakList
	 *
	 * @return string[]
	 */
	 protected function getSnakListValues( $snakList ) {
		$values = array();

		foreach ( $snakList as $snak ) {
			// TODO: change hardcoded ": " so something like wfMessage( 'colon-separator' ),
			// but this will require further refactoring as it would add HTML which gets escaped
			$values[] =
				$this->getEntityLabel( $snak->getPropertyId() ) .
				': '.
				$this->getMainSnakValue( $snak );
		}

		return $values;
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
		$propertyLabel = $this->getEntityLabel( $propertyId );
		$headerText = wfMessage( 'wikibase-entity-property' ) . ' / ' . $propertyLabel;

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

			// FIXME! should use some value formatter
			if ( $dataValue instanceof EntityId ) {
				$diffValueString = $this->getEntityLabel( $dataValue );
			} else {
				$diffValueString = $dataValue->getValue();
			}

			return $diffValueString;
		} else {
			return $snakType;
		}
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

		$entity = $this->entityLookup->getEntity( $entityId );

		if ( $entity instanceof Entity ) {
			$lookedUpLabel = $this->entityLookup->getEntity( $entityId )->getLabel( $this->langCode );

			if ( $lookedUpLabel !== false ) {
				$label = $lookedUpLabel;
			}
		}

		return $label;
	}

	/**
	 * Get Html for reference changes
	 *
	 * @since 0.4
	 *
	 * @param Diff $referenceChanges
	 * @param Claim $claim
	 *
	 * @return string
	 */
	protected function visualizeReferenceChanges( Diff $referenceChanges, Claim $claim ) {
		$html = '';

		$claimMainSnak = $claim->getMainSnak();
		$claimHeader = $this->getMainSnakHeader( $claimMainSnak );

		$newRef = $oldRef = null;

		// Because references only have hashes and no ids,
		// changing a reference value is treated as a diffop add and diffop remove;
		// For diff visualization, it should be more like a change
		// @todo assert that both reference changes refer to the same reference
		foreach( $referenceChanges as $referenceChange ) {
			if ( $referenceChange instanceof \Diff\DiffOpAdd ) {
				$newRef = $this->getSnakListValues( $referenceChange->getNewValue()->getSnaks() );
			} else if ( $referenceChange instanceof \Diff\DiffOpRemove ) {
				$oldRef = $this->getSnakListValues( $referenceChange->getOldValue()->getSnaks() );
			} else if ( $referenceChange instanceof \Diff\DiffOpChange ) {
				$oldRef = $this->getSnakListValues( $referenceChange->getOldValue()->getSnaks() );
				$newRef = $this->getSnakListValues( $referenceChange->getNewValue()->getSnaks() );
			} else {
				// something went wrong, never should happen
				throw new \MWException( 'There are no references in the reference change.' );
			}

			$valueFormatter = new DiffOpValueFormatter(
				$claimHeader . ' / ' . wfMessage( 'wikibase-diffview-reference' ),
				$oldRef,
				$newRef
			);

			$oldRef = $newRef = null;

			$html .= $valueFormatter->generateHtml();

		}

		return $html;
	}

	/**
	 * Format diff header for a reference
	 *
	 * @since 0.4
	 *
	 * @param Reference $ref
	 *
	 * @return string
	 */
	protected function getRefHeader( $ref ) {
		$headerSnaks = $ref->getSnaks();

		foreach( $headerSnaks as $headerSnak ) {
			$header = $this->getMainSnakHeader( $headerSnak );
			break;
		}

		return $header;
	}

}
