<?php

namespace Wikibase\Api;

use DataValues\IllegalValueException;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\SnakConstructionService;
use ApiBase, MWException;
use Wikibase\EntityContent;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\Summary;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Entity;
use Wikibase\Property;
use Wikibase\EntityContentFactory;
use Wikibase\Lib\ClaimGuidValidator;
use Wikibase\Snak;
use ValueParsers\ParseException;

/**
 * Helper class for modifying claims
 *
 * @since 0.4
 *
 * @ingroup WikibaseRepo
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimModificationHelper {

	/**
	 * @since 0.4
	 *
	 * @var \ApiMain
	 */
	protected $apiMain;

	/**
	 * @since 0.4
	 *
	 * @var EntityContentFactory
	 */
	protected $entityContentFactory;

	/**
	 * @since 0.4
	 *
	 * @var SnakConstructionService
	 */
	protected $snakConstructionService;

	/**
	 * @since 0.4
	 *
	 * @var EntityIdParser
	 */
	protected $entityIdParser;

	/**
	 * @since 0.4
	 *
	 * @var ClaimGuidValidator
	 */
	protected $claimGuidValidator;

	/**
	 * @since 0.4
	 *
	 * @var SnakValidationHelper
	 */
	protected $snakValidation;

	/**
	 * @since 0.4
	 *
	 * @param \ApiMain $apiMain
	 * @param EntityContentFactory $entityContentFactory
	 * @param SnakConstructionService $snakConstructionService
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct(
		\ApiMain $apiMain,
		EntityContentFactory $entityContentFactory,
		SnakConstructionService $snakConstructionService,
		EntityIdParser $entityIdParser,
		ClaimGuidValidator $claimGuidValidator,
		SnakValidationHelper $snakValidation
	) {
		$this->apiMain = $apiMain;
		$this->entityContentFactory = $entityContentFactory;
		$this->snakConstructionService = $snakConstructionService;
		$this->entityIdParser = $entityIdParser;
		$this->claimGuidValidator = $claimGuidValidator;
		$this->snakValidation = $snakValidation;
	}

	/**
	 * @since 0.4
	 *
	 * @param Claim $claim
	 * @param string $key
	 */
	public function addClaimToApiResult( Claim $claim, $key = 'claim' ) {
		$serializerFactory = new SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $claim );
		$serializer->getOptions()->setIndexTags( $this->apiMain->getResult()->getIsRawMode() );

		$this->apiMain->getResult()->addValue(
			null,
			$key,
			$serializer->getSerialized( $claim )
		);
	}

	/**
	 * @since 0.4
	 *
	 * @param EntityId $entityId
	 *
	 * @return \Title
	 *
	 * @deprecated use EntityHelper::getEntityTitleFromEntityId()
	 */
	public function getEntityTitle( EntityId $entityId ) {
		$entityTitle = $this->entityContentFactory->getTitleForId( $entityId );

		if ( $entityTitle === null ) {
			$this->apiMain->dieUsage( 'No such entity' , 'no-such-entity' );
		}

		return $entityTitle;
	}

	/**
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 *
	 * @return bool
	 */
	public function validateClaimGuid( $claimGuid ) {
		try {
			return $this->claimGuidValidator->validate( $claimGuid );
		} catch ( ParseException $e ) {
			$this->apiMain->dieUsage( 'Invalid claim guid' , 'invalid-guid' );
		}
	}

	/**
	 * @since 0.4
	 *
	 * @param string $claimGuid
	 * @param Entity $entity
	 *
	 * @return Claim
	 */
	public function getClaimFromEntity( $claimGuid, Entity $entity ) {
		$claims = new Claims( $entity->getClaims() );

		if ( !$claims->hasClaimWithGuid( $claimGuid ) ) {
			$this->apiMain->dieUsage( 'Could not find the claim' , 'no-such-claim' );
		}

		return $claims->getClaimWithGuid( $claimGuid );
	}

	/**
	 * @since 0.4
	 *
	 * @param array $params
	 * @param EntityId $propertyId
	 *
	 * @return \Wikibase\Snak
	 *
	 * @throws ParseException
	 * @throws IllegalValueException
	 */
	public function getSnakInstance( $params, EntityId $propertyId ) {
		$valueData = null;
		if ( isset( $params['value'] ) ) {
			$valueData = \FormatJson::decode( $params['value'], true );
			if ( $valueData === null ) {
				$this->apiMain->dieUsage( 'Could not decode snak value', 'invalid-snak' );
			}
		}

		if ( $propertyId->getEntityType() !== Property::ENTITY_TYPE ) {
			$this->apiMain->dieUsage( 'Property expected, got ' . $propertyId->getEntityType(), 'invalid-snak' );
		}

		try {
			$snak = $this->snakConstructionService->newSnak( $propertyId, $params['snaktype'], $valueData );
		}
		catch ( IllegalValueException $ex ) {
			$this->apiMain->dieUsage( 'Invalid snak: IllegalValueException', 'invalid-snak' );
		}
		catch ( InvalidArgumentException $ex ) {
			// shouldn't happen, but might.
			$this->apiMain->dieUsage( 'Invalid snak: InvalidArgumentException', 'invalid-snak' );
		}

		$this->validateSnak( $snak );

		return $snak;
	}

	/**
	 * @since 0.4
	 *
	 * @param Snak $snak
	 */
	public function validateSnak( Snak $snak ) {
		$this->snakValidation->validateSnak( $snak );
	}

	/**
	 * Parses an entity id string coming from the user
	 *
	 * @since 0.4
	 * @param string $entityIdParam
	 * @return EntityId
	 *
	 * @deprecated use EntityHelper::getEntityIdFromString()
	 */
	public function getEntityIdFromString( $entityIdParam ) {
		try {
			$entityId = $this->entityIdParser->parse( $entityIdParam );
		} catch ( ParseException $parseException ) {
			$this->apiMain->dieUsage( 'Invalid entity ID: ParseException', 'invalid-entity-id' );
		}

		return $entityId;
	}

	/**
	 * Creates a new Summary instance suitable for representing the action performed by this module.
	 *
	 * @since 0.4
	 *
	 * @param array $params
	 *
	 * @return Summary
	 */
	public function createSummary( array $params, \ApiBase $module ) {
		$summary = new Summary( $module->getModuleName() );
		if ( isset( $params['summary'] ) ) {
			$summary->setUserSummary( $params['summary'] );
		}
		return $summary;
	}

	/**
	 * @see ApiBase::getPossibleErrors()
	 */
	public function getPossibleErrors() {
		return array(
			array( 'code' => 'invalid-guid', 'info' => $this->apiMain->msg( 'wikibase-api-invalid-guid' )->text() ),
			array( 'code' => 'no-such-entity', 'info' => $this->apiMain->msg( 'wikibase-api-no-such-entity' )->text() ),
			array( 'code' => 'no-such-claim', 'info' => $this->apiMain->msg( 'wikibase-api-no-such-claim' )->text() ),
			array( 'code' => 'invalid-snak', 'info' => $this->apiMain->msg( 'wikibase-api-invalid-snak' )->text() ),
			array( 'code' => 'invalid-entity-id', 'info' => $this->apiMain->msg( 'wikibase-api-invalid-entity-id' )->text() ),
		);
	}
}
