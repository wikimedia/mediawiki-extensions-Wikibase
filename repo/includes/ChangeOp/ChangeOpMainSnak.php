<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Summary;
use Wikibase\Validators\SnakValidator;

/**
 * Class for mainsnak change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpMainSnak extends ChangeOpBase {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $claimGuid;

	/**
	 * @since 0.4
	 *
	 * @var Snak
	 */
	protected $snak;

	/**
	 * @var SnakValidator
	 */
	private $snakValidator;

	/**
	 * Constructs a new mainsnak change operation
	 *
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param Snak $snak
	 * @param ClaimGuidGenerator $guidGenerator
	 * @param SnakValidator $snakValidator
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$claimGuid,
		Snak $snak,
		ClaimGuidGenerator $guidGenerator,
		SnakValidator $snakValidator
	) {
		if ( !is_string( $claimGuid ) ) {
			throw new InvalidArgumentException( '$claimGuid needs to be a string' );
		}

		$this->claimGuid = $claimGuid;
		$this->snak = $snak;
		$this->guidGenerator = $guidGenerator;
		$this->snakValidator = $snakValidator;
	}

	/**
	 * @return string
	 */
	public function getClaimGuid() {
		return $this->claimGuid;
	}

	/**
	 * @see ChangeOp::apply()
	 * - a new claim with $snak as mainsnak gets added when $claimGuid is empty and $snak is set
	 * - the claim's mainsnak gets set to $snak when $claimGuid and $snak are set
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$claims = new Claims( $entity->getClaims() );

		if ( is_null( $this->claimGuid ) || empty( $this->claimGuid ) ) {
			$this->addClaim( $entity, $claims, $summary );
		} else {
			$this->setClaim( $claims, $summary );
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
		$claim->setGuid( $this->guidGenerator->newGuid( $entity->getId() ) );
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
			throw new ChangeOpException( "Entity does not have claim with GUID " . $this->claimGuid );
		}

		$claim = $claims->getClaimWithGuid( $this->claimGuid );
		$propertyId = $claim->getMainSnak()->getPropertyId();

		if ( !$propertyId->equals( $this->snak->getPropertyId() ) ) {
			throw new ChangeOpException( "Claim with GUID "
				. $this->claimGuid . " uses property "
				. $propertyId . ", can't change to "
				. $this->snak->getPropertyId() );
		}

		$claim->setMainSnak( $this->snak );
		$this->updateSummary( $summary, null, '', $this->getClaimSummaryArgs( $this->snak ) );
	}

	/**
	 * @since 0.4
	 *
	 * @param Snak $mainSnak
	 *
	 * @return array
	 */
	protected function getClaimSummaryArgs( Snak $mainSnak ) {
		$propertyId = $mainSnak->getPropertyId();
		return array( array( $propertyId->getSerialization() => $mainSnak ) );
	}

	/**
	 * @see ChangeOp::validate()
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 *
	 * @throws ChangeOpException
	 *
	 * @return Result
	 */
	public function validate( Entity $entity ) {
		return $this->snakValidator->validate( $this->snak );
	}
}
