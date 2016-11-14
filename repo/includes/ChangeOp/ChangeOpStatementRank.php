<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\StatementRankSerializer;
use Wikibase\Summary;

/**
 * Class for statement rank change operation
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpStatementRank extends ChangeOpBase {

	/**
	 * @var string
	 */
	private $statementGuid;

	/**
	 * @var integer
	 */
	private $rank;

	/**
	 * Constructs a new statement rank change operation
	 *
	 * @since 0.4
	 *
	 * @param string $statementGuid
	 * @param integer $rank
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $statementGuid, $rank ) {
		if ( !is_string( $statementGuid ) ) {
			throw new InvalidArgumentException( '$statementGuid needs to be a string' );
		}

		if ( !is_integer( $rank ) ) {
			throw new InvalidArgumentException( '$rank needs to be an integer' );
		}

		$this->statementGuid = $statementGuid;
		$this->rank = $rank;
	}

	/**
	 * @see ChangeOp::apply()
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
					$statementRankSerializer->serialize( $this->rank )
				]
			);
		}
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
