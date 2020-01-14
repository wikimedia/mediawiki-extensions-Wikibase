<?php

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\LabelDescriptionUniquenessValidator;
use Wikibase\Repo\Validators\LabelUniquenessValidator;
use Wikibase\Repo\Validators\SiteLinkUniquenessValidator;

/**
 * @covers \Wikibase\Repo\Validators\EntityConstraintProvider
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityConstraintProviderTest extends \PHPUnit\Framework\TestCase {

	private function getEntityConstraintProvider( $itemTermsMigrationStage, $propertyTermsMigrationStage ) {
		$duplicateDetector = $this->getMockBuilder( LabelDescriptionDuplicateDetector::class )
			->disableOriginalConstructor()
			->getMock();

		$siteLinkConflictLookup = $this->createMock( SiteLinkConflictLookup::class );

		return new EntityConstraintProvider(
			$duplicateDetector,
			$siteLinkConflictLookup,
			$itemTermsMigrationStage,
			$propertyTermsMigrationStage
		);
	}

	public function provideTestGetUpdateValidators() {
		return [
			'for items' => [
				'entityType' => Item::ENTITY_TYPE,
				'propertyTermsMigrationStage' => 0, // irrelevant
				'itemTermsMigrationStage' => [], // irrelevant
				'expectedValidatorTypes' => [
					SiteLinkUniquenessValidator::class
				]
			],

			'for properties - proeprty terms migration on MIGRATION_OLD' => [
				'entityType' => Property::ENTITY_TYPE,
				'propertyTermsMigrationStage' => MIGRATION_OLD,
				'itemTermsMigrationStage' => [], // irrelevant
				'expectedValidatorTypes' => [
					LabelUniquenessValidator::class
				]
			],

			'for properties - proeprty terms migration on MIGRATION_WRITE_BOTH' => [
				'entityType' => Property::ENTITY_TYPE,
				'propertyTermsMigrationStage' => MIGRATION_WRITE_BOTH,
				'itemTermsMigrationStage' => [], // irrelevant
				'expectedValidatorTypes' => [
					LabelUniquenessValidator::class
				]
			],

			'for properties - proeprty terms migration on MIGRATION_WRITE_NEW' => [
				'entityType' => Property::ENTITY_TYPE,
				'propertyTermsMigrationStage' => MIGRATION_WRITE_NEW,
				'itemTermsMigrationStage' => [], // irrelevant
				'expectedValidatorTypes' => []
			],

			'for properties - proeprty terms migration on MIGRATION_NEW' => [
				'entityType' => Property::ENTITY_TYPE,
				'propertyTermsMigrationStage' => MIGRATION_NEW,
				'itemTermsMigrationStage' => [], // irrelevant
				'expectedValidatorTypes' => []
			]
		];
	}

	/**
	 * @dataProvider provideTestGetUpdateValidators
	 */
	public function testGetUpdateValidators(
		$entityType,
		$propertyTermsMigrationStage,
		$itemTermsMigrationStage,
		$expectedValidatorTypes
	) {
		$provider = $this->getEntityConstraintProvider( $itemTermsMigrationStage, $propertyTermsMigrationStage );

		$validators = $provider->getUpdateValidators( $entityType );

		$this->assertValidators( $expectedValidatorTypes, $validators );
	}

	public function provideTestGetCreationValidators() {
		$itemTermsMigrationStage = [
			2 => MIGRATION_NEW,
			4 => MIGRATION_WRITE_NEW,
			6 => MIGRATION_WRITE_BOTH,
			'max' => MIGRATION_OLD
		];

		return [
			// Property
			'for properties - proeprty terms migration on MIGRATION_OLD' => [
				'entityType' => Property::ENTITY_TYPE,
				'propertyTermsMigrationStage' => MIGRATION_OLD,
				'itemTermsMigrationStage' => [], // irrelevant
				'entityId' => PropertyId::newFromNumber( 1 ), // irrelevant
				'expectedValidatorTypes' => [
					LabelUniquenessValidator::class
				]
			],

			'for properties - proeprty terms migration on MIGRATION_WRITE_BOTH' => [
				'entityType' => Property::ENTITY_TYPE,
				'propertyTermsMigrationStage' => MIGRATION_WRITE_BOTH,
				'itemTermsMigrationStage' => [], // irrelevant,
				'entityId' => PropertyId::newFromNumber( 1 ), // irrelevant
				'expectedValidatorTypes' => [
					LabelUniquenessValidator::class
				]
			],

			'for properties - proeprty terms migration on MIGRATION_WRITE_NEW' => [
				'entityType' => Property::ENTITY_TYPE,
				'propertyTermsMigrationStage' => MIGRATION_WRITE_NEW,
				'itemTermsMigrationStage' => [], // irrelevant,
				'entityId' => PropertyId::newFromNumber( 1 ), // irrelevant
				'expectedValidatorTypes' => []
			],

			'for properties - proeprty terms migration on MIGRATION_NEW' => [
				'entityType' => Property::ENTITY_TYPE,
				'propertyTermsMigrationStage' => MIGRATION_NEW,
				'itemTermsMigrationStage' => [], // irrelevant,
				'entityId' => PropertyId::newFromNumber( 1 ), // irrelevant
				'expectedValidatorTypes' => []
			],

			// Item
			'for items - when item id falls in terms stage MIGRATION_OLD' => [
				'entityType' => Item::ENTITY_TYPE,
				'propertyTermsMigrationStage' => 0,
				'itemTermsMigrationStage' => $itemTermsMigrationStage,
				'entityId' => ItemId::newFromNumber( 7 ),
				'expectedValidatorTypes' => [
					SiteLinkUniquenessValidator::class,
					LabelDescriptionUniquenessValidator::class
				]
			],

			'for items - when item id falls in terms stage MIGRATION_WRITE_BOTH' => [
				'entityType' => Item::ENTITY_TYPE,
				'propertyTermsMigrationStage' => 0,
				'itemTermsMigrationStage' => $itemTermsMigrationStage,
				'entityId' => ItemId::newFromNumber( 5 ),
				'expectedValidatorTypes' => [
					SiteLinkUniquenessValidator::class,
					LabelDescriptionUniquenessValidator::class
				]
			],

			'for items - when item id falls in terms stage MIGRATION_WRITE_NEW' => [
				'entityType' => Item::ENTITY_TYPE,
				'propertyTermsMigrationStage' => 0,
				'itemTermsMigrationStage' => $itemTermsMigrationStage,
				'entityId' => ItemId::newFromNumber( 3 ),
				'expectedValidatorTypes' => [
					SiteLinkUniquenessValidator::class
				]
			],

			'for items - when item id falls in terms stage MIGRATION_NEW' => [
				'entityType' => Item::ENTITY_TYPE,
				'propertyTermsMigrationStage' => 0,
				'itemTermsMigrationStage' => $itemTermsMigrationStage,
				'entityId' => ItemId::newFromNumber( 1 ),
				'expectedValidatorTypes' => [
					SiteLinkUniquenessValidator::class
				]
			]
		];
	}

	/**
	 * @dataProvider provideTestGetCreationValidators
	 */
	public function testGetCreationValidators(
		$entityType,
		$propertyTermsMigrationStage,
		$itemTermsMigrationStage,
		$entityId,
		$expectedValidatorTypes
	) {
		$provider = $this->getEntityConstraintProvider( $itemTermsMigrationStage, $propertyTermsMigrationStage );

		$validators = $provider->getCreationValidators( $entityType, $entityId );

		$this->assertValidators( $expectedValidatorTypes, $validators );
	}

	private function assertValidators( $expectedValidatorTypes, $validators ) {
		$this->assertInternalType( 'array', $validators );

		$validatorTypes = array_map(
			function ( $validator ) {
				return get_class( $validator );
			},
			$validators
		);

		$this->assertEquals( $expectedValidatorTypes, $validatorTypes );
	}

}
