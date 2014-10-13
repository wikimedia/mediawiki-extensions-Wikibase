<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\Snaks;
use Wikibase\Summary;
use Wikibase\Validators\SnakValidator;

/**
 * Class for qualifier change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpQualifier extends ChangeOpBase {

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
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $snakHash;

	/**
	 * @var SnakValidator
	 */
	private $snakValidator;

	/**
	 * Constructs a new qualifier change operation
	 *
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param Snak $snak
	 * @param string $snakHash
	 * @param SnakValidator $snakValidator
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claimGuid, Snak $snak, $snakHash, SnakValidator $snakValidator ) {
		if ( !is_string( $claimGuid ) || $claimGuid === '' ) {
			throw new InvalidArgumentException( '$claimGuid needs to be a string and must not be empty' );
		}

		if ( !is_string( $snakHash ) ) {
			throw new InvalidArgumentException( '$snakHash needs to be a string' );
		}

		$this->claimGuid = $claimGuid;
		$this->snak = $snak;
		$this->snakHash = $snakHash;
		$this->snakValidator = $snakValidator;
	}

	/**
	 * @see ChangeOp::apply()
	 * - a new qualifier gets added when $snakHash is empty and $snak is set
	 * - the qualifier gets set to $snak when $snakHash and $snak are set
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
			$this->setQualifier( $qualifiers, $summary );
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
			throw new ChangeOpException( 'Claim has already a qualifier with hash ' . $this->snak->getHash() );
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
			throw new ChangeOpException( 'Claim has already a qualifier with hash ' . $this->snak->getHash() );
		}
		$qualifiers->removeSnakHash( $this->snakHash );
		$qualifiers->addSnak( $this->snak );
		$this->updateSummary( $summary, 'update', '', $this->getSnakSummaryArgs( $this->snak ) );
	}

	/**
	 * @since 0.4
	 *
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
		return $this->snakValidator->validate( $this->snak );
	}

}
