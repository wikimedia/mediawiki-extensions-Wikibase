<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\Snaks;
use Wikibase\Summary;

/**
 * Class for qualifier removal change operation
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpQualifierRemove extends ChangeOpBase {

	/**
	 * @since 0.5
	 *
	 * @var string
	 */
	protected $claimGuid;

	/**
	 * @since 0.5
	 *
	 * @var string
	 */
	protected $snakHash;

	/**
	 * Constructs a new qualifier removal change operation
	 *
	 * @since 0.5
	 *
	 * @param string $claimGuid
	 * @param string $snakHash
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claimGuid, $snakHash ) {
		if ( !is_string( $claimGuid ) || $claimGuid === '' ) {
			throw new InvalidArgumentException( '$claimGuid needs to be a string and must not be empty' );
		}

		if ( !is_string( $snakHash ) || $snakHash === ''  ) {
			throw new InvalidArgumentException( '$snakHash needs to be a string and must not be empty' );
		}

		$this->claimGuid = $claimGuid;
		$this->snakHash = $snakHash;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$claims = new Claims( $entity->getClaims() );

		if( !$claims->hasClaimWithGuid( $this->claimGuid ) ) {
			throw new ChangeOpException( "Entity does not have claim with GUID $this->claimGuid" );
		}

		$claim = $claims->getClaimWithGuid( $this->claimGuid );
		$qualifiers = $claim->getQualifiers();

		$this->removeQualifier( $qualifiers, $summary );

		$claim->setQualifiers( $qualifiers );
		$entity->setClaims( $claims );

		return true;
	}

	/**
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
	 * @param Snak $snak
	 *
	 * @return array
	 */
	protected function getSnakSummaryArgs( Snak $snak ) {
		$propertyId = $snak->getPropertyId();
		return array( array( $propertyId->getSerialization() => $snak ) );
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
		//TODO: move validation logic from apply() here.
		return parent::validate( $entity );
	}

}