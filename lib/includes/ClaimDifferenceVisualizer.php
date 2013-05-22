<?php
namespace Wikibase;

use DataValues\TimeValue;
use Html;
use Diff\Diff;
use Wikibase\Lib\EntityIdFormatter;

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
	 * @since 0.4
	 *
	 * @var string
	 */
	private $langCode;

	/**
	 * @since 0.4
	 *
	 * @var EntityIdFormatter
	 */
	private $idFormatter;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param EntityLookup $entityLookup
	 * @param string $langCode
	 * @param EntityIdFormatter $idFormatter
	 */
	public function __construct( $entityLookup, $langCode, EntityIdFormatter $idFormatter ) {
		$this->entityLookup = $entityLookup;
		$this->langCode = $langCode;
		$this->idFormatter = $idFormatter;
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

		if ( $claimDifference->getQualifierChanges() !== null ) {
			$html .= $this->visualizeQualifierChanges(
				$claimDifference->getQualifierChanges(),
				$baseClaim
			);
		}

		if ( $claimDifference->getReferenceChanges() !== null ) {
			$html .= $this->visualizeSnakListChanges(
				$claimDifference->getReferenceChanges(),
				$baseClaim,
				wfMessage( 'wikibase-diffview-reference' )
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
			$this->getSnakHeader( $mainSnakChange->getNewValue() ),
			$this->getSnakValue( $mainSnakChange->getOldValue() ),
			$this->getSnakValue( $mainSnakChange->getNewValue() )
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
			$snakHeader .= $this->getSnakHeader( $headerSnak );
		} else {
			// something went wrong
			throw new \MWException( 'Snak parameters not provided.' );
		}

		$oldValue = null;
		$newValue = null;

		if ( $oldSnak instanceof Snak ) {
			$oldValue = $this->getSnakValue( $oldSnak );
		}

		if ( $newSnak instanceof Snak ) {
			$newValue = $this->getSnakValue( $newSnak );
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
				$this->getSnakValue( $snak );
		}

		return $values;
	}

	/**
	 * Get formatted header for a snak
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 *
	 * @return string
 	 */
	protected function getSnakHeader( Snak $snak ) {
		$propertyId = $snak->getPropertyId();
		$propertyLabel = $this->getEntityLabel( $propertyId );
		$headerText = wfMessage( 'wikibase-entity-property' ) . ' / ' . $propertyLabel;

		return $headerText;
	}

	/**
	 * Get snak value in string form
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 *
	 * @return string
 	 */
	protected function getSnakValue( Snak $snak ) {
		$snakType = $snak->getType();

		if ( $snakType === 'value' ) {
			$dataValue = $snak->getDataValue();

			// FIXME! should use some value formatter
			if ( $dataValue instanceof EntityId ) {
				$diffValueString = $this->getEntityLabel( $dataValue );
			} else if ( $dataValue instanceof TimeValue ) {
				// TODO: this will just display the plain ISO8601-string,
				// we should instead use a decent formatter
				$diffValueString = $dataValue->getTime();
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
		$entity = $this->entityLookup->getEntity( $entityId );

		if ( $entity instanceof Entity ) {
			$lookedUpLabel = $this->entityLookup->getEntity( $entityId )->getLabel( $this->langCode );

			if ( $lookedUpLabel !== false ) {
				return $lookedUpLabel;
			}
		}

		return $this->idFormatter->format( $entityId );
	}

	/**
	 * Get Html for snaklist changes
	 *
	 * @since 0.4
	 *
	 * @param Diff[] $changes
	 * @param Claim $claim
	 *
	 * @return string
	 */
	protected function visualizeSnakListChanges( Diff $changes, Claim $claim, $breadcrumb ) {
		$html = '';

		$claimMainSnak = $claim->getMainSnak();
		$claimHeader = $this->getSnakHeader( $claimMainSnak );
		$newVal = $oldVal = null;

		foreach( $changes as $change ) {
			if ( $change instanceof \Diff\DiffOpAdd ) {
				$newVal = $this->getSnakListValues( $change->getNewValue()->getSnaks() );
			} else if ( $change instanceof \Diff\DiffOpRemove ) {
				$oldVal = $this->getSnakListValues( $change->getOldValue()->getSnaks() );
			} else if ( $change instanceof \Diff\DiffOpChange ) {
				$oldVal = $this->getSnakListValues( $change->getOldValue()->getSnaks() );
				$newVal = $this->getSnakListValues( $change->getNewValue()->getSnaks() );
			} else {
				// something went wrong, never should happen
				throw new \MWException( 'Unknown change operation.' );
			}

			$valueFormatter = new DiffOpValueFormatter(
				$claimHeader . ' / ' . $breadcrumb,
				$oldVal,
				$newVal
			);

			$oldVal = $newVal = null;
			$html .= $valueFormatter->generateHtml();
		}

		return $html;
	}

	/**
	 * Get Html for qualifier changes
	 *
	 * @since 0.4
	 *
	 * @param Diff[] $changes
	 * @param Claim $claim
	 *
	 * @return string
	 */
	protected function visualizeQualifierChanges( Diff $changes, Claim $claim ) {
		$html = '';

		$claimMainSnak = $claim->getMainSnak();
		$claimHeader = $this->getSnakHeader( $claimMainSnak );
		$newVal = $oldVal = null;

		foreach( $changes as $change ) {
			// TODO: change hardcoded ": " so something like wfMessage( 'colon-separator' ),
			// but this will require further refactoring as it would add HTML which gets escaped
			if ( $change instanceof \Diff\DiffOpAdd ) {
				$newVal =
					$this->getEntityLabel( $change->getNewValue()->getPropertyId() ) .
					': ' .
					$this->getSnakValue( $change->getNewValue() );
			} else if ( $change instanceof \Diff\DiffOpRemove ) {
				$oldVal =
					$this->getEntityLabel( $change->getOldValue()->getPropertyId() ) .
					': ' .
					$this->getSnakValue( $change->getOldValue() );
			} else if ( $change instanceof \Diff\DiffOpChange ) {
				$oldVal =
					$this->getEntityLabel( $change->getOldValue()->getPropertyId() ) .
					': ' .
					$this->getSnakValue( $change->getOldValue() );
				$newVal =
					$this->getEntityLabel( $change->getNewValue()->getPropertyId() ) .
					': ' .
					$this->getSnakValue( $change->getNewValue() );
			} else {
				// something went wrong, never should happen
				throw new \MWException( 'Unknown change operation.' );
			}

			$valueFormatter = new DiffOpValueFormatter(
					$claimHeader . ' / ' . wfMessage( 'wikibase-diffview-qualifier' ),
					$oldVal,
					$newVal
			);

			$oldVal = $newVal = null;
			$html .= $valueFormatter->generateHtml();
		}

		return $html;
	}

}
