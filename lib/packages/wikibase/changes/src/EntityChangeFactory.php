<?php

namespace Wikibase\Lib\Changes;

use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityChangeFactory {

	/**
	 * @var EntityDiffer
	 */
	private $entityDiffer;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var string[] Maps entity type IDs to subclasses of EntityChange.
	 */
	private $changeClasses;

	/** @var string */
	private $defaultEntityChange;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param EntityDiffer $entityDiffer
	 * @param EntityIdParser $idParser
	 * @param string[] $changeClasses Maps entity type IDs to subclasses of EntityChange.
	 * Entity types not mapped explicitly are assumed to use EntityChange itself.
	 * @param string $defaultEntityChange
	 * @param LoggerInterface|null $logger
	 */
	public function __construct(
		EntityDiffer $entityDiffer,
		EntityIdParser $idParser,
		array $changeClasses,
		string $defaultEntityChange = EntityChange::class,
		?LoggerInterface $logger = null
	) {
		$this->entityDiffer = $entityDiffer;
		$this->idParser = $idParser;
		$this->changeClasses = $changeClasses;
		$this->defaultEntityChange = $defaultEntityChange;
		$this->logger = $logger ?: new NullLogger();
	}

	/**
	 * @param string $action The action name
	 * @param EntityId $entityId
	 * @param array $fields additional fields to set
	 *
	 * @return EntityChange
	 */
	public function newForEntity( $action, EntityId $entityId, array $fields = [] ): EntityChange {
		$entityType = $entityId->getEntityType();

		if ( isset( $this->changeClasses[ $entityType ] ) ) {
			$class = $this->changeClasses[$entityType];
		} else {
			$class = $this->defaultEntityChange;
		}

		/** @var EntityChange $instance */
		$instance = new $class( $fields );

		$instance->setLogger( $this->logger );

		$instance->setEntityId( $entityId );

		if ( !$instance->hasField( ChangeRow::INFO ) ) {
			$instance->setField( ChangeRow::INFO, [] );
		}

		// Note: the change type determines how newForChangeType will
		// instantiate and handle the change
		$type = 'wikibase-' . $entityId->getEntityType() . '~' . $action;
		$instance->setField( ChangeRow::TYPE, $type );

		return $instance;
	}

	/**
	 * @param string $changeType
	 * @param EntityId $entityId
	 * @param array $fields additional fields to set
	 *
	 * @throws InvalidArgumentException
	 * @return EntityChange
	 */
	public function newForChangeType( $changeType, EntityId $entityId, array $fields ): EntityChange {
		$changeType = explode( '~', $changeType, 2 );
		Assert::parameter(
			isset( $changeType[1] ),
			'$changeType',
			'must conform to the format "wikibase-<entityType>~<action>"'
		);

		$action = $changeType[1];
		return $this->newForEntity( $action, $entityId, $fields );
	}

	/**
	 * @param array $fields all data fields, including at least 'type' and 'object_id'.
	 *
	 * @throws InvalidArgumentException
	 * @return EntityChange
	 */
	public function newFromFieldData( array $fields ): EntityChange {
		Assert::parameter( isset( $fields[ChangeRow::TYPE] ), '$fields[\'type\']', 'must be set' );
		Assert::parameter( isset( $fields[ChangeRow::OBJECT_ID] ), '$fields[\'object_id\']', 'must be set' );

		$entityId = $this->idParser->parse( $fields[ChangeRow::OBJECT_ID] );
		return $this->newForChangeType( $fields[ChangeRow::TYPE], $entityId, $fields );
	}

	/**
	 * Constructs an EntityChange from the given old and new Entity.
	 *
	 * @param string      $action The action name
	 * @param EntityDocument|null $oldEntity
	 * @param EntityDocument|null $newEntity
	 *
	 * @return EntityChange
	 * @throws Exception
	 */
	public function newFromUpdate(
		$action,
		EntityDocument $oldEntity = null,
		EntityDocument $newEntity = null
	): EntityChange {
		if ( $oldEntity === null && $newEntity === null ) {
			throw new Exception( 'Either $oldEntity or $newEntity must be given' );
		}

		if ( $oldEntity === null ) {
			$id = $newEntity->getId();
			$diff = $this->entityDiffer->getConstructionDiff( $newEntity );
		} elseif ( $newEntity === null ) {
			$id = $oldEntity->getId();
			$diff = $this->entityDiffer->getDestructionDiff( $oldEntity );
		} elseif ( $oldEntity->getType() !== $newEntity->getType() ) {
			throw new Exception( 'Entity type mismatch' );
		} else {
			$id = $newEntity->getId();
			$diff = $this->entityDiffer->diffEntities( $oldEntity, $newEntity );
		}

		$instance = $this->newForEntity( $action, $id );
		$aspectsFactory = new EntityDiffChangedAspectsFactory( $this->logger );
		$aspectsDiff = $aspectsFactory->newFromEntityDiff( $diff );
		$instance->setCompactDiff( $aspectsDiff );

		return $instance;
	}

}
