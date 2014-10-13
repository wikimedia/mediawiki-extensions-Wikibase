<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Summary;

/**
 * Class for claim remove operation
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpClaimRemove extends ChangeOpBase {

	/**
	 * @since 0.5
	 *
	 * @var string
	 */
	protected $claimGuid;

	/**
	 * @return string
	 */
	public function getClaimGuid() {
		return $this->claimGuid;
	}

	/**
	 * Constructs a new mainsnak change operation
	 *
	 * @since 0.5
	 *
	 * @param string $claimGuid
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claimGuid ) {
		if ( !is_string( $claimGuid ) || $claimGuid === '' ) {
			throw new InvalidArgumentException( '$claimGuid needs to be a string and must not be empty' );
		}

		$this->claimGuid = $claimGuid;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		$claims = new Claims( $entity->getClaims() );

		$this->removeClaim( $claims, $summary );

		$entity->setClaims( $claims );

		return true;
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
		//TODO: move validation logic from apply() here.
		return parent::validate( $entity );
	}

}