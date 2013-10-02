<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\Snak;
use Wikibase\Statement;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\Serializers\ClaimSerializer;

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
	 * @since 0.4
	 *
	 * @var EntityIdFormatter
	 */
	protected $idFormatter;

	/**
	 * Constructs a new statement rank change operation
	 *
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param integer $rank
	 * @param Lib\EntityIdFormatter $idFormatter
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $claimGuid, $rank, EntityIdFormatter $idFormatter ) {
		if ( !is_string( $claimGuid ) ) {
			throw new InvalidArgumentException( '$claimGuid needs to be a string' );
		}

		if ( !is_integer( $rank ) ) {
			throw new InvalidArgumentException( '$rank needs to be an integer' );
		}

		$this->claimGuid = $claimGuid;
		$this->rank = $rank;
		$this->idFormatter = $idFormatter;
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
	 *
	 * @todo: REUSE!!
	 */
	protected function getSnakSummaryArgs( Snak $snak ) {
		$propertyId = $this->idFormatter->format( $snak->getPropertyId() );

		//TODO: use formatters here!
		if ( $snak instanceof PropertyValueSnak ) {
			$value = $snak->getDataValue();
		} else {
			$value = $snak->getType();
		}

		$args = array( $propertyId => array( $value ) );
		return array( $args );
	}
}
