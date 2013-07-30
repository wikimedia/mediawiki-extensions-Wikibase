<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\Snak;
use Wikibase\Lib\EntityIdFormatter;

/**
 * Class for claim change operation
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpClaim extends ChangeOp {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $claimGuid;

	/**
	 * @since 0.4
	 *
	 * @var Snak|null
	 */
	protected $snak;

	/**
	 * @since 0.4
	 *
	 * @var EntityIdFormatter
	 */
	protected $idFormatter;

	/**
	 * Constructs a new claim change operation
	 *
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param Snak|null $snak
	 * @param EntityIdFormatter $entityIdFormatter
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claimGuid, $snak, EntityIdFormatter $idFormatter ) {
		if ( !is_string( $claimGuid ) ) {
			throw new InvalidArgumentException( '$claimGuid needs to be a string' );
		}

		if ( !( $snak instanceof Snak ) && !is_null( $snak ) ) {
			throw new InvalidArgumentException( '$snak needs to be an instance of Snak or null' );
		}

		if ( $claimGuid === '' && $snak === null ) {
			throw new InvalidArgumentException( 'Either $claimGuid or $snak needs to be set' );
		}

		$this->claimGuid = $claimGuid;
		$this->snak = $snak;
		$this->idFormatter = $idFormatter;
	}

	public function getClaimGuid() {
		return $this->claimGuid;
	}

	/**
	 * Applies the change to the given entity
	 *
	 * - the claim gets removed when $claimGuid is set and $snak is not set
	 * - a new claim with $snak gets added when $claimGuid is empty and $snak is set
	 * - the claim's main snak gets set to $snak when $claimGuid and $snak are set
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @return bool
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$claims = new Claims( $entity->getClaims() );

		if ( $this->claimGuid === '' ) {
			//create claim
			$claim = $entity->newClaim( $this->snak );
			$claims->addClaim( $claim );
			$this->updateSummary( $summary, 'create', '', $this->getClaimSummaryArgs( $this->snak ) );
			$this->claimGuid = $claim->getGuid();
		} else {
			if( !$claims->hasClaimWithGuid( $this->claimGuid ) ) {
				return false;
			}
			if ( $this->snak !== null ) {
				//set claim
				$claims->getClaimWithGuid( $this->claimGuid )->setMainSnak( $this->snak );
				$this->updateSummary( $summary, null, '', $this->getClaimSummaryArgs( $this->snak ) );
			} else {
				//remove claim
				$removedSnak = $claims->getClaimWithGuid( $this->claimGuid )->getMainSnak();
				$claims->removeClaimWithGuid( $this->claimGuid );
				$this->updateSummary( $summary, 'remove', '', $this->getClaimSummaryArgs( $removedSnak ) );
			}
		}

		$entity->setClaims( $claims );

		return true;
	}

	/**
	 * @since 0.4
	 *
	 * @param Snak $mainSnak
	 *
	 * @return array
	 */
	protected function getClaimSummaryArgs( Snak $mainSnak ) {
		$propertyId = $this->idFormatter->format( $mainSnak->getPropertyId() );

		//TODO: use formatters here!
		if ( $mainSnak instanceof PropertyValueSnak ) {
			$value = $mainSnak->getDataValue();
		} else {
			$value = $mainSnak->getType();
		}

		$args = array( $propertyId => array( $value ) );
		return array( $args );
	}
}
