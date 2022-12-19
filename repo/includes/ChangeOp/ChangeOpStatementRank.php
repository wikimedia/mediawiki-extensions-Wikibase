<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\Summary;
use Wikibase\Repo\StatementRankSerializer;

/**
 * Class for statement rank change operation
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpStatementRank extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $statementGuid;

	/**
	 * @var int
	 */
	private $rank;

	/**
	 * Constructs a new statement rank change operation
	 *
	 * @param string $statementGuid
	 * @param int $rank
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $statementGuid, $rank ) {
		if ( !is_string( $statementGuid ) ) {
			throw new InvalidArgumentException( '$statementGuid needs to be a string' );
		}

		if ( !is_int( $rank ) ) {
			throw new InvalidArgumentException( '$rank needs to be an integer' );
		}

		$this->statementGuid = $statementGuid;
		$this->rank = $rank;
	}

	/**
	 * @see ChangeOp::apply()
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

		$oldRank = $statement->getRank();
		$statement->setRank( $this->rank );
		$this->updateSummary( $summary, null, '', $this->getSnakSummaryArgs( $statement->getMainSnak() ) );

		if ( $summary !== null ) {
			$statementRankSerializer = new StatementRankSerializer();
			$summary->addAutoCommentArgs(
				[
					$statementRankSerializer->serialize( $oldRank ),
					$statementRankSerializer->serialize( $this->rank ),
				]
			);
		}

		return new GenericChangeOpResult( $entity->getId(), true );
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
	 * @return Result Always successful.
	 */
	public function validate( EntityDocument $entity ) {
		//TODO: move validation logic from apply() here.
		return Result::newSuccess();
	}

}
