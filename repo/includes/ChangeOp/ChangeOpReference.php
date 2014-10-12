<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Summary;
use Wikibase\Validators\SnakValidator;

/**
 * Class for reference change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpReference extends ChangeOpBase {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $claimGuid;

	/**
	 * @since 0.4
	 *
	 * @var Reference
	 */
	protected $reference;

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $referenceHash;

	/**
	 * @since 0.5
	 *
	 * @var int|null
	 */
	protected $index;

	/**
	 * @var SnakValidator
	 */
	private $snakValidator;

	/**
	 * Constructs a new reference change operation
	 *
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param Reference $reference
	 * @param string $referenceHash (if empty '' a new reference will be created)
	 * @param SnakValidator $snakValidator
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claimGuid, Reference $reference, $referenceHash, SnakValidator $snakValidator, $index = null ) {
		if ( !is_string( $claimGuid ) || $claimGuid === '' ) {
			throw new InvalidArgumentException( '$claimGuid needs to be a string and must not be empty' );
		}

		if ( !is_string( $referenceHash ) ) {
			throw new InvalidArgumentException( '$referenceHash needs to be a string' );
		}

		if ( !( $reference instanceof Reference ) ) {
			throw new InvalidArgumentException( '$reference needs to be an instance of Reference' );
		}

		if( !is_null( $index ) && !is_integer( $index ) ) {
			throw new InvalidArgumentException( '$index needs to be null or an integer value' );
		}

		$this->claimGuid = $claimGuid;
		$this->reference = $reference;
		$this->referenceHash = $referenceHash;
		$this->index = $index;
		$this->snakValidator = $snakValidator;
	}

	/**
	 * @see ChangeOp::apply()
	 * - a new reference gets added when $referenceHash is empty and $reference is set
	 * - the reference gets set to $reference when $referenceHash and $reference are set
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

		if ( $this->referenceHash === '' ) {
			$this->addReference( $references, $summary );
		} else {
			$this->setReference( $references, $summary );
		}

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
	protected function addReference( ReferenceList $references, Summary $summary = null ) {
		if ( $references->hasReference( $this->reference ) ) {
			$hash = $this->reference->getHash();
			throw new ChangeOpException( "Claim has already a reference with hash $hash" );
		}
		$references->addReference( $this->reference, $this->index );
		$this->updateSummary( $summary, 'add' );
	}

	/**
	 * @since 0.4
	 *
	 * @param ReferenceList $references
	 * @param Summary $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function setReference( ReferenceList $references, Summary $summary = null ) {
		if ( !$references->hasReferenceHash( $this->referenceHash ) ) {
			throw new ChangeOpException( "Reference with hash $this->referenceHash does not exist" );
		}

		$currentIndex = $references->indexOf( $this->reference );

		if( is_null( $this->index ) && $currentIndex !== false ) {
			// Set index to current index to not have the reference removed and appended but
			// retain its position within the list of references.
			$this->index = $currentIndex;
		}

		if ( $references->hasReference( $this->reference ) && $this->index === $currentIndex ) {
			throw new ChangeOpException( "Claim has already a reference with hash "
			. "{$this->reference->getHash()} and index ($currentIndex) is not changed" );
		}
		$references->removeReferenceHash( $this->referenceHash );
		$references->addReference( $this->reference, $this->index );
		$this->updateSummary( $summary, 'set' );
	}

	/**
	 * @since 0.4
	 *
	 * @param Snak $snak
	 * @return array
	 */
	protected function getSnakSummaryArgs( Snak $snak ) {
		$propertyId = $snak->getPropertyId();

		return array( array( $propertyId->getPrefixedId() => $snak ) );
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
		return $this->snakValidator->validateReference( $this->reference );
	}
}
