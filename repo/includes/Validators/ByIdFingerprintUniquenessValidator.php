<?php

namespace Wikibase\Repo\Validators;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\ChangeOp\ChangeOpFingerprintResult;

/**
 * Decorator for FingerprintUniquenessValidator that knows whether it should actually
 * perform the validation in new store based on the entity id and the migration stage.
 *
 * This is temporary solution and should be removed completely once we switch to new
 * store for good.
 *
 * @license GPL-2.0-or-later
 */
class ByIdFingerprintUniquenessValidator extends FingerprintUniquenessValidator {

	/** @var array */
	private $itemTermsMigrationStages;

	/** @var int */
	private $propertyTermsMigrationStage;

	/** @var FingerprintUniquenessValidator */
	private $fingerprintUniquenessValidator;

	public function __construct(
		array $itemTermsMigrationStages,
		int $propertyTermsMigrationStage,
		FingerprintUniquenessValidator $fingerprintUniquenessValidator
	) {
		$this->itemTermsMigrationStages = $itemTermsMigrationStages;
		$this->propertyTermsMigrationStage = $propertyTermsMigrationStage;
		$this->fingerprintUniquenessValidator = $fingerprintUniquenessValidator;
	}

	public function validate( $value ) {
		if ( !$value instanceof ChangeOpFingerprintResult ) {
			throw new InvalidArgumentException( '$value can only be of type ChangeOpFingerprintResult' );
		}

		$entityId = $value->getEntityId();
		if ( $entityId->getEntityType() === Item::ENTITY_TYPE ) {
			'@phan-var ItemId $entityId';
			return $this->validateItem( $value, $entityId );
		} else {
			return $this->validateProperty( $value );
		}
	}

	private function validateProperty( $value ) {
		if ( $this->propertyTermsMigrationStage > MIGRATION_WRITE_BOTH ) {
			return $this->fingerprintUniquenessValidator->validate( $value );
		}

		return Result::newSuccess();
	}

	private function validateItem( $value, ItemId $itemId ) {
		$entityNumericId = $itemId->getNumericId();
		foreach ( $this->itemTermsMigrationStages as $maxId => $migrationStage ) {
			if ( $maxId === 'max' ) {
				$maxId = Int32EntityId::MAX;
			} elseif ( !is_int( $maxId ) ) {
				throw new InvalidArgumentException( "'{$maxId}' in tmpItemTermsMigrationStages is not integer" );
			}

			if ( $entityNumericId > $maxId ) {
				continue;
			}

			if ( $migrationStage > MIGRATION_WRITE_BOTH ) {
				return $this->fingerprintUniquenessValidator->validate( $value );
			}

			break;
		}

		return Result::newSuccess();
	}
}
