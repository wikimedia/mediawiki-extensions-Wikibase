<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\Snak;
use Wikibase\Lib\EntityIdFormatter;

/**
 * Class for mainsnak change operation
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpMainSnak extends ChangeOp {

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
	 * Constructs a new mainsnak change operation
	 *
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param Snak|null $snak
	 * @param Lib\EntityIdFormatter $idFormatter
	 * @throws \InvalidArgumentException
	 *
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
	 * - a new claim with $snak as mainsnak gets added when $claimGuid is empty and $snak is set
	 * - the claim's mainsnak gets set to $snak when $claimGuid and $snak are set
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

		if ( $this->claimGuid === '' ) {
			$this->addClaim( $entity, $claims, $summary );
		} else {
			if ( $this->snak !== null ) {
				$this->setClaim( $claims, $summary );
			} else {
				$this->removeClaim( $claims, $summary );
			}
		}

		$entity->setClaims( $claims );

		return true;
	}

	/**
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param Claims $claims
	 * @param Summary $summary
	 */
	protected function addClaim( Entity $entity, Claims $claims, Summary $summary = null ) {
		//TODO: check for claim uniqueness?
		$claim = $entity->newClaim( $this->snak );
		$claims->addClaim( $claim );
		$this->updateSummary( $summary, 'create', '', $this->getClaimSummaryArgs( $this->snak ) );
		$this->claimGuid = $claim->getGuid();
	}

	/**
	 * @since 0.4
	 *
	 * @param Claims $claims
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function setClaim( Claims $claims, Summary $summary = null ) {
		if( !$claims->hasClaimWithGuid( $this->claimGuid ) ) {
			throw new ChangeOpException( "Entity does not have claim with GUID $this->claimGuid" );
		}
		$claims->getClaimWithGuid( $this->claimGuid )->setMainSnak( $this->snak );
		$this->updateSummary( $summary, null, '', $this->getClaimSummaryArgs( $this->snak ) );
	}

	/**
	 * @since 0.4
	 *
	 * @param Claims $claims
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function removeClaim( Claims $claims, Summary $summary = null ) {
		if( !$claims->hasClaimWithGuid( $this->claimGuid ) ) {
			throw new ChangeOpException( "Entity does not have claim with GUID $this->claimGuid" );
		}
		$removedSnak = $claims->getClaimWithGuid( $this->claimGuid )->getMainSnak();
		$claims->removeClaimWithGuid( $this->claimGuid );
		$this->updateSummary( $summary, 'remove', '', $this->getClaimSummaryArgs( $removedSnak ) );
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
