<?php

namespace Wikibase\Repo\Tests\Validators;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\LabelUniquenessValidator;
use Wikibase\Repo\Validators\SiteLinkUniquenessValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;

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

	private function getEntityConstraintProvider() {
		$termValidatorFactory = $this->createMock( TermValidatorFactory::class );
		$termValidatorFactory->method( 'getLabelUniquenessValidator' )
			->willReturn(
				new LabelUniquenessValidator(
					$this->createMock( TermsCollisionDetector::class )
				)
			);
		return new EntityConstraintProvider(
			$this->createMock( SiteLinkConflictLookup::class ),
			$termValidatorFactory,
			[]
		);
	}

	public function provideTestGetUpdateValidators() {
		return [
			'for items' => [
				'entityType' => Item::ENTITY_TYPE,
				'expectedValidatorTypes' => [
					SiteLinkUniquenessValidator::class,
				],
			],
			'for properties' => [
				'entityType' => Property::ENTITY_TYPE,
				'expectedValidatorTypes' => [
					LabelUniquenessValidator::class,
				],
			],
		];
	}

	/**
	 * @dataProvider provideTestGetUpdateValidators
	 */
	public function testGetUpdateValidators(
		$entityType,
		$expectedValidatorTypes
	) {
		$provider = $this->getEntityConstraintProvider();

		$validators = $provider->getUpdateValidators( $entityType );

		$this->assertValidators( $expectedValidatorTypes, $validators );
	}

	public function provideTestGetCreationValidators() {
		return [
			// Property
			'for properties' => [
				'entityType' => Property::ENTITY_TYPE,
				'entityId' => NumericPropertyId::newFromNumber( 1 ), // irrelevant
				'expectedValidatorTypes' => [
					LabelUniquenessValidator::class,
				],
			],

			// Item
			'for items' => [
				'entityType' => Item::ENTITY_TYPE,
				'entityId' => ItemId::newFromNumber( 1 ),
				'expectedValidatorTypes' => [
					SiteLinkUniquenessValidator::class,
				],
			],
		];
	}

	/**
	 * @dataProvider provideTestGetCreationValidators
	 */
	public function testGetCreationValidators(
		$entityType,
		$entityId,
		$expectedValidatorTypes
	) {
		$provider = $this->getEntityConstraintProvider();

		$validators = $provider->getCreationValidators( $entityType, $entityId );

		$this->assertValidators( $expectedValidatorTypes, $validators );
	}

	private function assertValidators( $expectedValidatorTypes, $validators ) {
		$this->assertIsArray( $validators );

		$validatorTypes = array_map( 'get_class', $validators );

		$this->assertEquals( $expectedValidatorTypes, $validatorTypes );
	}

}
