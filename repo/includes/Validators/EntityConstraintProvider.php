<?php

namespace Wikibase\Repo\Validators;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\LabelDescriptionDuplicateDetector;
use Wikibase\Repo\Store\SiteLinkConflictLookup;

/**
 * Provides constraints for each entity type.
 * Used to enforce global constraints upon save.
 *
 * @see docs/constraints.wiki
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityConstraintProvider {

	/**
	 * @var LabelDescriptionDuplicateDetector
	 */
	private $duplicateDetector;

	/**
	 * @var SiteLinkConflictLookup
	 */
	private $siteLinkConflictLookup;

	/**
	 * @var array
	 */
	private $itemTermsMigrationStages;

	/**
	 * @var int
	 */
	private $propertyTermsMigrationStage;

	public function __construct(
		LabelDescriptionDuplicateDetector $duplicateDetector,
		SiteLinkConflictLookup $siteLinkConflictLookup,
		array $itemTermsMigrationStages,
		int $propertyTermsMigrationStage
	) {
		$this->duplicateDetector = $duplicateDetector;
		$this->siteLinkConflictLookup = $siteLinkConflictLookup;
		$this->itemTermsMigrationStages = $itemTermsMigrationStages;
		$this->propertyTermsMigrationStage = $propertyTermsMigrationStage;

		//TODO: Make validators configurable. Allow more types to register.
	}

	/**
	 * Returns validators for hard global constraints that should be enforced on every update
	 * of an entity of the given type (including creation).
	 *
	 * @param string $entityType
	 *
	 * @return EntityValidator[]
	 */
	public function getUpdateValidators( $entityType ) {
		$validators = [];

		switch ( $entityType ) {
			case Property::ENTITY_TYPE:
				if ( $this->propertyTermsMigrationStage < MIGRATION_WRITE_NEW ) {
					// Only validate label uniqueness in old store when we are actually still reading from it.
					// Validation of fingerprint uniqueness in new store are differently done
					// see ChangeOpFingerprintResult::validate
					$validators[] = new LabelUniquenessValidator( $this->duplicateDetector );
				}
				break;

			case Item::ENTITY_TYPE:
				$validators[] = new SiteLinkUniquenessValidator( $this->siteLinkConflictLookup );
				break;
		}

		return $validators;
	}

	/**
	 * Returns validators for soft global constraints that should be enforced only
	 * upon creation of an entity of the given type. This will include at least the
	 * validators returned by getUpdateValidators() for that type.
	 *
	 * @note During updates, such soft constraints should be checked selectively by the
	 * respective ChangeOps, so not all such (potentially expensive) validators are applied
	 * for all updates.
	 *
	 * @return EntityValidator[]
	 */
	public function getCreationValidators( $entityType, EntityId $entityId ): array {
		$validators = $this->getUpdateValidators( $entityType );

		switch ( $entityType ) {
			case Property::ENTITY_TYPE:
				break;

			case Item::ENTITY_TYPE:
				if ( !$entityId instanceof ItemId ) {
					throw new InvalidArgumentException( '$entityId can only be ItemId' );
				}

				// Only validate label and description uniqueness in old store when we are actually still reading from it.
				// Validation of fingerprint uniqueness in new store are differently done
				// see ChangeOpFingerprintResult::validate
				$entityNumericId = $entityId->getNumericId();
				foreach ( $this->itemTermsMigrationStages as $maxId => $migrationStage ) {
					if ( $maxId === 'max' ) {
						$maxId = Int32EntityId::MAX;
					} elseif ( !is_int( $maxId ) ) {
						throw new InvalidArgumentException( "'{$maxId}' in tmpItemTermsMigrationStages is not integer" );
					}

					if ( $entityNumericId > $maxId ) {
						continue;
					}

					if ( $migrationStage < MIGRATION_WRITE_NEW ) {
						$validators[] = new LabelDescriptionUniquenessValidator( $this->duplicateDetector );
					}

					break;
				}
				break;
		}

		return $validators;
	}

}
