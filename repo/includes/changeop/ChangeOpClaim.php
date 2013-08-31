<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;

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
	 * @since 0.5
	 *
	 * @var ClaimGuidGenerator
	 */
	protected $guidGenerator;

	/**
	 * @since 0.4
	 *
	 * @param Claim $claim
	 * @param string $action should be add or remove
	 *
	 * @param ClaimGuidGenerator $guidGenerator
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $claim, $action, ClaimGuidGenerator $guidGenerator ) {
		if ( !$claim instanceof Claim ) {
			throw new InvalidArgumentException( '$claim needs to be an instance of Claim' );
		}

		if ( !is_string( $action ) ) {
			throw new InvalidArgumentException( '$action needs to be a string' );
		}

		$this->claim = $claim;
		$this->action = $action;
		$this->guidGenerator = $guidGenerator;
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

			$guidValidator = new ClaimGuidValidator();

			if( $this->claim->getGuid() === null ){
				$this->claim->setGuid( $this->guidGenerator->newGuid() );
			}
			$guid = $this->claim->getGuid();

			if ( $guidValidator->validate( $this->claim->getGuid() ) === false ) {
				throw new ChangeOpException( "Claim does not have a valid GUID" );
			} else if ( strtoupper( $entity->getId()->getPrefixedId() ) !== substr( $guid, 0, strpos( $guid, '$' ) ) ){
				throw new ChangeOpException( "Claim GUID invalid for given entity" );
			}

			$entity->addClaim( $this->claim );
			$this->updateSummary( $summary, 'add' );

		} elseif ( $this->action === "remove" ) {

			$guid = $this->claim->getGuid();
			$claims = new Claims ( $entity->getClaims() );
			if( !$claims->hasClaimWithGuid( $guid ) ){
				throw new ChangeOpException( 'Cannot remove a claim that does not exist on given entity' );
			}
			$claims->removeClaimWithGuid( $guid );
			$entity->setClaims( $claims );
			$this->updateSummary( $summary, 'remove' );

		} else {
			throw new ChangeOpException( "Unknown action for change op: $this->action" );
		}
		return true;
	}
}
