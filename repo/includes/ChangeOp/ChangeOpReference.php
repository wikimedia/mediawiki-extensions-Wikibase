<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Validators\SnakValidator;

/**
 * Class for reference change operation
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpReference extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $statementGuid;

	/**
	 * @var Reference
	 */
	private $reference;

	/**
	 * @var string
	 */
	private $referenceHash;

	/**
	 * @var int|null
	 */
	private $index;

	/**
	 * @var SnakValidator
	 */
	private $snakValidator;

	/**
	 * Constructs a new reference change operation
	 *
	 * @param string $statementGuid
	 * @param Reference $reference
	 * @param string $referenceHash (if empty '' a new reference will be created)
	 * @param SnakValidator $snakValidator
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$statementGuid,
		Reference $reference,
		$referenceHash,
		SnakValidator $snakValidator,
		$index = null
	) {
		if ( !is_string( $statementGuid ) || $statementGuid === '' ) {
			throw new InvalidArgumentException( '$statementGuid needs to be a string and must not be empty' );
		}

		if ( !is_string( $referenceHash ) ) {
			throw new InvalidArgumentException( '$referenceHash needs to be a string' );
		}

		if ( !is_int( $index ) && $index !== null ) {
			throw new InvalidArgumentException( '$index must be an integer or null' );
		}

		$this->statementGuid = $statementGuid;
		$this->reference = $reference;
		$this->referenceHash = $referenceHash;
		$this->index = $index;
		$this->snakValidator = $snakValidator;
	}

	/**
	 * @see ChangeOp::apply()
	 * - a new reference gets added when $referenceHash is empty and $reference is set
	 * - the reference gets set to $reference when $referenceHash and $reference are set
	 *
	 * @param EntityDocument $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeOpException
	 */
	public function apply( EntityDocument $entity, Summary $summary = null ) {
		if ( !( $entity instanceof StatementListProvider ) ) {
			throw new InvalidArgumentException( '$entity must be a StatementListProvider' );
		}

		$statements = $entity->getStatements();
		$statement = $statements->getFirstStatementWithGuid( $this->statementGuid );

		if ( $statement === null ) {
			throw new ChangeOpException( "Entity does not have a statement with GUID $this->statementGuid" );
		}

		$references = $statement->getReferences();

		if ( $this->referenceHash === '' ) {
			$this->addReference( $references, $summary );
		} else {
			$this->setReference( $references, $summary );
		}

		if ( $summary !== null ) {
			$summary->addAutoSummaryArgs( $this->getSnakSummaryArgs( $statement->getMainSnak() ) );
		}

		$statement->setReferences( $references );

		return new GenericChangeOpResult( $entity->getId(), true );
	}

	/**
	 * @param ReferenceList $references
	 * @param Summary|null $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function addReference( ReferenceList $references, Summary $summary = null ) {
		if ( $references->hasReference( $this->reference ) ) {
			$hash = $this->reference->getHash();
			throw new ChangeOpException( "The statement has already a reference with hash $hash" );
		}
		$references->addReference( $this->reference, $this->index );
		$this->updateSummary( $summary, 'add' );
	}

	/**
	 * @param ReferenceList $references
	 * @param Summary|null $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function setReference( ReferenceList $references, Summary $summary = null ) {
		if ( !$references->hasReferenceHash( $this->referenceHash ) ) {
			throw new ChangeOpException( "Reference with hash $this->referenceHash does not exist" );
		}

		$currentIndex = $references->indexOf( $this->reference );

		if ( $this->index === null && $currentIndex !== false ) {
			// Set index to current index to not have the reference removed and appended but
			// retain its position within the list of references.
			$this->index = $currentIndex;
		}

		if ( $references->hasReference( $this->reference ) && $this->index === $currentIndex ) {
			throw new ChangeOpException( 'The statement has already a reference with hash '
			. $this->reference->getHash() . ' and index (' . $currentIndex . ') is not changed' );
		}
		$references->removeReferenceHash( $this->referenceHash );
		$references->addReference( $this->reference, $this->index );
		$this->updateSummary( $summary, 'set' );
	}

	/**
	 * @param Snak $snak
	 * @return array
	 */
	protected function getSnakSummaryArgs( Snak $snak ) {
		$propertyId = $snak->getPropertyId();

		return [ [ $propertyId->getSerialization() => $snak ] ];
	}

	/**
	 * @see ChangeOp::validate
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 */
	public function validate( EntityDocument $entity ) {
		return $this->snakValidator->validateReference( $this->reference );
	}

}
