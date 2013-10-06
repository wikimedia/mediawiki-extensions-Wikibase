<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\Claim;
use Wikibase\Entity;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;

/**
 * Class for claim modification operations
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ChangeOpClaim extends ChangeOpBase {

	/**
	 * @since 0.4
	 *
	 * @var Claim
	 */
	protected $claim;

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
	 * @param ClaimGuidGenerator $guidGenerator
	 *
	 * @throws \InvalidArgumentException
	 */
	public function __construct( $claim, $guidGenerator ) {
		if ( !$claim instanceof Claim ) {
			throw new InvalidArgumentException( '$claim needs to be an instance of Claim' );
		}

		if( !$guidGenerator instanceof ClaimGuidGenerator ){
			throw new InvalidArgumentException( '$guidGenerator needs to be an instance of ClaimGuidGenerator' );
		}

		$this->claim = $claim;
		$this->guidGenerator = $guidGenerator;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {

		$guidValidator = new ClaimGuidValidator();
		$guidParser = WikibaseRepo::getDefaultInstance()->getClaimGuidParser();

		if( $this->claim->getGuid() === null ){
			$this->claim->setGuid( $this->guidGenerator->newGuid() );
		}
		$guid = $this->claim->getGuid();
		$guid = $guidParser->parse( $guid );

		if ( $guidValidator->validate( $guid->getSerialization() ) === false ) {
			throw new ChangeOpException( "Claim does not have a valid GUID" );
		} else if ( !$entity->getId()->equals( $guid->getEntityId() ) ){
			throw new ChangeOpException( "Claim GUID invalid for given entity" );
		}

		$entity->addClaim( $this->claim );
		$this->updateSummary( $summary, 'add' );

		return true;
	}

}
