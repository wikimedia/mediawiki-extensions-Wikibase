<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\ByPropertyIdArray;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\ClaimGuidParser;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Summary;

/**
 * Class for claim modification operations
 *
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Adam Shorland
 * @author H. Snater < mediawiki@snater.com >
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
	 * @since 0.5
	 *
	 * @var ClaimGuidValidator
	 */
	private $guidValidator;

	/**
	 * @since 0.5
	 *
	 * @var ClaimGuidParser
	 */
	private $guidParser;

	/**
	 * @since 0.5
	 *
	 * @var int|null
	 */
	protected $index;

	/**
	 * @param Claim $claim
	 * @param ClaimGuidGenerator $guidGenerator
	 * @param ClaimGuidValidator $guidValidator
	 * @param ClaimGuidParser $guidParser
	 * @param int|null $index
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $claim, $guidGenerator, $guidValidator, $guidParser, $index = null ) {
		if ( !$claim instanceof Claim ) {
			throw new InvalidArgumentException( '$claim needs to be an instance of Claim' );
		}

		if( !$guidGenerator instanceof ClaimGuidGenerator ){
			throw new InvalidArgumentException( '$guidGenerator needs to be an instance of ClaimGuidGenerator' );
		}

		if( !$guidValidator instanceof ClaimGuidValidator ){
			throw new InvalidArgumentException( '$guidValidator needs to be an instance of ClaimGuidValidator' );
		}

		if( !$guidParser instanceof ClaimGuidParser ){
			throw new InvalidArgumentException( '$guidParser needs to be an instance of ClaimGuidParser' );
		}

		if( !is_null( $index ) && !is_integer( $index ) ) {
			throw new InvalidArgumentException( '$index needs to be null or an integer value' );
		}

		$this->claim = $claim;
		$this->guidGenerator = $guidGenerator;
		$this->guidValidator = $guidValidator;
		$this->guidParser = $guidParser;
		$this->index = $index;
	}

	/**
	 * @see ChangeOp::apply()
	 */
	public function apply( Entity $entity, Summary $summary = null ) {

		if( $this->claim->getGuid() === null ){
			$this->claim->setGuid( $this->guidGenerator->newGuid() );
		}
		$guid = $this->guidParser->parse( $this->claim->getGuid() );

		if ( $this->guidValidator->validate( $guid->getSerialization() ) === false ) {
			throw new ChangeOpException( "Claim does not have a valid GUID" );
		} else if ( !$entity->getId()->equals( $guid->getEntityId() ) ){
			throw new ChangeOpException( "Claim GUID invalid for given entity" );
		}

		$entityClaims = $entity->getClaims();
		$claims = new Claims( $entityClaims );

		if( !$claims->hasClaimWithGuid( $this->claim->getGuid() ) ) {
			// Adding a new claim.
			$this->updateSummary( $summary, 'create' );

			$indexedClaimList = new ByPropertyIdArray( $entityClaims );
			$indexedClaimList->buildIndex();

			$indexedClaimList->addObjectAtIndex( $this->claim, $this->index );

		} else {
			// Altering an existing claim.
			$this->updateSummary( $summary, 'update' );

			// Replace claim at its current index:
			$currentIndex = $claims->indexOf( $this->claim );
			$claims->removeClaimWithGuid( $this->claim->getGuid() );
			$claims->addClaim( $this->claim, $currentIndex );

			// Move claim to its designated index:
			$indexedClaimList = new ByPropertyIdArray( $claims );
			$indexedClaimList->buildIndex();

			$index = !is_null( $this->index ) ? $this->index : $currentIndex;
			$indexedClaimList->moveObjectToIndex( $this->claim, $index );
		}

		$claims = new Claims( $indexedClaimList->toFlatArray() );
		$entity->setClaims( $claims );

		return true;
	}
}
