<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\Snak;
use Wikibase\Snaks;
use Wikibase\Lib\EntityIdFormatter;

/**
 * Class for qualifier change operation
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
class ChangeOpQualifier extends ChangeOp {

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
	 * @var string
	 */
	protected $snakHash;

	/**
	 * @since 0.4
	 *
	 * @var EntityIdFormatter
	 */
	protected $idFormatter;

	/**
	 * Constructs a new qualifier change operation
	 *
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param Snak|null $snak
	 * @param string $snakHash
	 * @param EntityIdFormatter $entityIdFormatter
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claimGuid, $snak, $snakHash, EntityIdFormatter $idFormatter ) {
		if ( !is_string( $claimGuid ) || $claimGuid === '' ) {
			throw new InvalidArgumentException( '$claimGuid needs to be a string and must not be empty' );
		}

		if ( !is_string( $snakHash ) ) {
			throw new InvalidArgumentException( '$snakHash needs to be a string' );
		}

		if ( !( $snak instanceof Snak ) && !is_null( $snak ) ) {
			throw new InvalidArgumentException( '$snak needs to be an instance of Snak or null' );
		}

		if ( $snakHash === '' && $snak === null ) {
			throw new InvalidArgumentException( 'Either $snakHash or $snak needs to be set' );
		}

		$this->claimGuid = $claimGuid;
		$this->snak = $snak;
		$this->snakHash = $snakHash;
		$this->idFormatter = $idFormatter;
	}

	/**
	 * Applies the change to the given entity
	 *
	 * - the qualifier gets removed when $snakHash is set and $snak is not set
	 * - a new qualifier gets added when $snakHash is empty and $snak is set
	 * - the qualifier gets set to $snak when $snakHash and $snak are set
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @return bool
	 *
	 * @throws ChangeOpException
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$claims = new Claims( $entity->getClaims() );

		if( !$claims->hasClaimWithGuid( $this->claimGuid ) ) {
			throw new ChangeOpException( "Entity does not have claim with GUID $this->claimGuid" );
		}

		$claim = $claims->getClaimWithGuid( $this->claimGuid );
		$qualifiers = $claim->getQualifiers();

		if ( $this->snakHash === '' ) {
			$this->addQualifier( $qualifiers, $summary );
		} else {
			if ( $this->snak != null ) {
				$this->setQualifier( $qualifiers, $summary );
			} else {
				$this->removeQualifier( $qualifiers, $summary );
			}
		}

		$claim->setQualifiers( $qualifiers );
		$entity->setClaims( $claims );

		return true;
	}

	/**
	 * @since 0.4
	 *
	 * @param Snaks $qualifiers
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function addQualifier( Snaks $qualifiers, Summary $summary = null ) {
		if ( $qualifiers->hasSnak( $this->snak ) ) {
			throw new ChangeOpException( "Claim has already a qualifier with hash {$this->snak->getHash()}" );
		}
		$qualifiers->addSnak( $this->snak );
		//TODO: add the mainsnak as autocomment-arg & change messages
		$this->updateSummary( $summary, 'add', '', $this->getSnakSummaryArgs( $this->snak ) );
	}

	/**
	 * @since 0.4
	 *
	 * @param Snaks $qualifiers
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function setQualifier( Snaks $qualifiers, Summary $summary = null ) {
		if ( !$qualifiers->hasSnakHash( $this->snakHash ) ) {
			throw new ChangeOpException( "Qualifier with hash $this->snakHash does not exist" );
		}
		if ( $qualifiers->hasSnak( $this->snak ) ) {
			throw new ChangeOpException( "Claim has already a qualifier with hash {$this->snak->getHash()}" );
		}
		$qualifiers->removeSnakHash( $this->snakHash );
		$qualifiers->addSnak( $this->snak );
		$this->updateSummary( $summary, 'update', '', $this->getSnakSummaryArgs( $this->snak ) );
	}

	/**
	 * @since 0.4
	 *
	 * @param Snaks $qualifiers
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function removeQualifier( Snaks $qualifiers, Summary $summary = null ) {
		if ( !$qualifiers->hasSnakHash( $this->snakHash ) ) {
			throw new ChangeOpException( "Qualifier with hash $this->snakHash does not exist" );
		}
		$removedQualifier = $qualifiers->getSnak( $this->snakHash );
		$qualifiers->removeSnakHash( $this->snakHash );
		$this->updateSummary( $summary, 'remove', '', $this->getSnakSummaryArgs( $removedQualifier ) );
	}

	/**
	 * @since 0.4
	 *
	 * @param Snak $mainSnak
	 *
	 * @return array
	 *
	 * @todo: REUSE!!
	 */
	protected function getSnakSummaryArgs( Snak $snak ) {
		$propertyId = $this->idFormatter->format( $snak->getPropertyId() );

		//TODO: use formatters here!
		if ( $snak instanceof PropertyValueSnak ) {
			$value = $snak->getDataValue();
		} else {
			$value = $snak->getType();
		}

		$args = array( $propertyId => array( $value ) );
		return array( $args );
	}
}
