<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Summary;

/**
 * Class for reference removal change operation
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpReferenceRemove extends ChangeOpBase {

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
	protected $referenceHash;

	/**
	 * Constructs a new reference removal change operation
	 *
	 * @since 0.5
	 *
	 * @param string $claimGuid
	 * @param string $referenceHash
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claimGuid, $referenceHash ) {
		if ( !is_string( $claimGuid ) || $claimGuid === '' ) {
			throw new InvalidArgumentException( '$claimGuid needs to be a string and must not be empty' );
		}

		if ( !is_string( $referenceHash ) || $referenceHash === '' ) {
			throw new InvalidArgumentException( '$referenceHash needs to be a string and must not be empty' );
		}

		$this->claimGuid = $claimGuid;
		$this->referenceHash = $referenceHash;
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
		if ( ! ( $claim instanceof Statement ) ) {
			throw new ChangeOpException( 'The referenced claim is not a statement and thus cannot have references' );
		}

		$references = $claim->getReferences();
		$this->removeReference( $references, $summary );

		if ( $summary !== null ) {
			$summary->addAutoSummaryArgs( $this->getSnakSummaryArgs( $claim->getMainSnak() ) );
		}

		$claim->setReferences( $references );
		$entity->setClaims( $claims );
		return true;
	}

	/**
	 * @since 0.4
	 *
	 * @param ReferenceList $references
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function removeReference( ReferenceList $references, Summary $summary = null ) {
		if ( !$references->hasReferenceHash( $this->referenceHash ) ) {
			throw new ChangeOpException( "Reference with hash $this->referenceHash does not exist" );
		}
		$references->removeReferenceHash( $this->referenceHash );
		$this->updateSummary( $summary, 'remove' );
		if ( $summary !== null ) {
			$summary->addAutoCommentArgs( 1 ); //atomic edit, only one reference changed
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param Snak $snak
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
