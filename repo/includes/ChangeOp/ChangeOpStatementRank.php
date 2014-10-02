<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Summary;

/**
 * Class for statement rank change operation
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpStatementRank extends ChangeOpBase {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $claimGuid;

	/**
	 * @since 0.4
	 *
	 * @var integer
	 */
	protected $rank;

	/**
	 * Constructs a new statement rank change operation
	 *
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param integer $rank
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claimGuid, $rank ) {
		if ( !is_string( $claimGuid ) ) {
			throw new InvalidArgumentException( '$claimGuid needs to be a string' );
		}

		if ( !is_integer( $rank ) ) {
			throw new InvalidArgumentException( '$rank needs to be an integer' );
		}

		$this->claimGuid = $claimGuid;
		$this->rank = $rank;
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
			throw new ChangeOpException( 'The referenced claim is not a statement and thus cannot have a rank' );
		}

		$oldRank = $claim->getRank();
		$claim->setRank( $this->rank );
		$this->updateSummary( $summary, null, '', $this->getSnakSummaryArgs( $claim->getMainSnak() ) );

		if ( $summary !== null ) {
			$summary->addAutoCommentArgs(
				array( ClaimSerializer::serializeRank( $oldRank ), ClaimSerializer::serializeRank( $this->rank ) )
			);
		}

		$entity->setClaims( $claims );

		return true;
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
		//TODO: move validation logic from apply() here.
		return parent::validate( $entity );
	}
}
