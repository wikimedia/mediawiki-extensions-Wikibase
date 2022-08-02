<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\Summary;
use Wikibase\Repo\Validators\SnakValidator;

/**
 * Class for qualifier change operation
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpQualifier extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $statementGuid;

	/**
	 * @var Snak
	 */
	private $snak;

	/**
	 * @var string
	 */
	private $snakHash;

	/**
	 * @var SnakValidator
	 */
	private $snakValidator;

	/**
	 * Constructs a new qualifier change operation
	 *
	 * @param string $statementGuid
	 * @param Snak $snak
	 * @param string $snakHash
	 * @param SnakValidator $snakValidator
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $statementGuid, Snak $snak, $snakHash, SnakValidator $snakValidator ) {
		if ( !is_string( $statementGuid ) || $statementGuid === '' ) {
			throw new InvalidArgumentException( '$statementGuid needs to be a string and must not be empty' );
		}

		if ( !is_string( $snakHash ) ) {
			throw new InvalidArgumentException( '$snakHash needs to be a string' );
		}

		$this->statementGuid = $statementGuid;
		$this->snak = $snak;
		$this->snakHash = $snakHash;
		$this->snakValidator = $snakValidator;
	}

	/**
	 * @see ChangeOp::apply()
	 * - a new qualifier gets added when $snakHash is empty and $snak is set
	 * - the qualifier gets set to $snak when $snakHash and $snak are set
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

		$qualifiers = $statement->getQualifiers();

		if ( $this->snakHash === '' ) {
			$this->addQualifier( $qualifiers, $summary );
		} else {
			$this->setQualifier( $qualifiers, $summary );
		}

		$statement->setQualifiers( $qualifiers );

		return new GenericChangeOpResult( $entity->getId(), true );
	}

	/**
	 * @param SnakList $qualifiers
	 * @param Summary|null $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function addQualifier( SnakList $qualifiers, Summary $summary = null ) {
		if ( $qualifiers->hasSnak( $this->snak ) ) {
			throw new ChangeOpException( 'The statement has already a qualifier with hash ' . $this->snak->getHash() );
		}
		$qualifiers->addSnak( $this->snak );
		//TODO: add the mainsnak as autocomment-arg & change messages
		$this->updateSummary( $summary, 'add', '', $this->getSnakSummaryArgs( $this->snak ) );
	}

	/**
	 * @param SnakList $qualifiers
	 * @param Summary|null $summary
	 *
	 * @throws ChangeOpException
	 */
	protected function setQualifier( SnakList $qualifiers, Summary $summary = null ) {
		if ( !$qualifiers->hasSnakHash( $this->snakHash ) ) {
			throw new ChangeOpException( "Qualifier with hash $this->snakHash does not exist" );
		}
		if ( $qualifiers->hasSnak( $this->snak ) ) {
			throw new ChangeOpException( 'The statement has already a qualifier with hash ' . $this->snak->getHash() );
		}
		$qualifiers->removeSnakHash( $this->snakHash );
		$qualifiers->addSnak( $this->snak );
		$this->updateSummary( $summary, 'update', '', $this->getSnakSummaryArgs( $this->snak ) );
	}

	/**
	 * @param Snak $snak
	 *
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
		return $this->snakValidator->validate( $this->snak );
	}

}
