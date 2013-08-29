<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\Lib\ClaimGuidGenerator;

/**
 * Class for claim change operation
 *
 * @since 0.4
 *
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpClaim extends ChangeOp {

	/**
	 * @since 0.4
	 *
	 * @var Claim
	 */
	protected $claim;

	/**
	 * @since 0.4
	 *
	 * @var array
	 */
	protected $action;

	/**
	 * @since 0.4
	 *
	 * @param Claim $claim
	 * @param string $action should be add or remove
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claim, $action ) {
		if ( !$claim instanceof Claim ) {
			throw new InvalidArgumentException( '$claim needs to be an instance of Claim' );
		}

		if ( !is_string( $action ) ) {
			throw new InvalidArgumentException( '$action needs to be a string' );
		}

		$this->claim = $claim;
		$this->action = $action;
	}

	/**
	 * Applies the change to the given entity
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @return bool
	 *
	 * @throws ChangeOpException
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		if ( $this->action === "add" ) {
			$guidGenerator = new ClaimGuidGenerator( $entity->getId() );
			$this->claim->setGuid( $guidGenerator->newGuid() );
			$entity->addClaim( $this->claim );
			$this->updateSummary( $summary, 'add' );
		} elseif ( $this->action === "remove" ) {
			$claims = new Claims ( $entity->getClaims() );
			$claims->removeClaimWithGuid( $this->claim->getGuid() );
			$entity->setClaims( $claims );
			$this->updateSummary( $summary, 'remove' );
		} else {
			throw new ChangeOpException( "Unknown action for change op: $this->action" );
		}
		return true;
	}
}
