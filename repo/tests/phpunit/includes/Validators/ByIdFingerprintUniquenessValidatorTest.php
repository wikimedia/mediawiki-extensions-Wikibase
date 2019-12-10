<?php

namespace Wikibase\Repo\Tests\Validators;

use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\ChangeOp\ChangeOpFingerprintResult;
use Wikibase\Repo\Validators\ByIdFingerprintUniquenessValidator;
use Wikibase\Repo\Validators\FingerprintUniquenessValidator;

/**
 * @covers \Wikibase\Repo\Validators\ByIdFingerprintUniquenessValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 */
class ByIdFingerprintUniquenessValidatorTest extends TestCase {

	public function setUp(): void {
		$this->fingerprintUniquenessValidator = $this->createMock( FingerprintUniquenessValidator::class );
	}

	public function provider() {
		$itemTermsMigrationStages = [
			2 => MIGRATION_NEW,
			4 => MIGRATION_WRITE_NEW,
			6 => MIGRATION_WRITE_BOTH,
			'max' => MIGRATION_OLD
		];
		return [

			'does not call validation when property terms migration is MIGRATION_OLD' => [
				'propertyTermsMigrationStage' => MIGRATION_OLD,
				'itemTermsMigrationStages' => $itemTermsMigrationStages, // irrelevant
				'entityId' => PropertyId::newFromNumber( 123 ),
				'callsValidation' => false
			],

			'does not call validation when property terms migration is MIGRATION_WRITE_BOTH' => [
				'propertyTermsMigrationStage' => MIGRATION_WRITE_BOTH,
				'itemTermsMigrationStages' => $itemTermsMigrationStages, // irrelevant
				'entityId' => PropertyId::newFromNumber( 123 ),
				'callsValidation' => false
			],

			'calls validation when property terms migration is MIGRATION_WRITE_NEW' => [
				'propertyTermsMigrationStage' => MIGRATION_WRITE_NEW,
				'itemTermsMigrationStages' => $itemTermsMigrationStages, // irrelevant
				'entityId' => PropertyId::newFromNumber( 123 ),
				'callsValidation' => true
			],

			'calls validation when property terms migration is MIGRATION_NEW' => [
				'propertyTermsMigrationStage' => MIGRATION_NEW,
				'itemTermsMigrationStages' => $itemTermsMigrationStages, // irrelevant
				'entityId' => PropertyId::newFromNumber( 123 ),
				'callsValidation' => true
			],

			'does not call validation when item id falls in MIGRATION_OLD stage' => [
				'propertyTermsMigrationStage' => 0, // irrelevant
				'itemTermsMigrationStages' => $itemTermsMigrationStages,
				'entityId' => ItemId::newFromNumber( 7 ),
				'callsValidation' => false
			],

			'does not call validation when item id falls in MIGRATION_WRITE_BOTH stage' => [
				'propertyTermsMigrationStage' => 0, // irrelevant
				'itemTermsMigrationStages' => $itemTermsMigrationStages,
				'entityId' => ItemId::newFromNumber( 5 ),
				'callsValidation' => false
			],

			'calls validation when item id falls in MIGRATION_WRITE_NEW stage' => [
				'propertyTermsMigrationStage' => 0, // irrelevant
				'itemTermsMigrationStages' => $itemTermsMigrationStages,
				'entityId' => ItemId::newFromNumber( 3 ),
				'callsValidation' => true
			],

			'calls validation when item id falls in MIGRATION_NEW stage' => [
				'propertyTermsMigrationStage' => 0, // irrelevant
				'itemTermsMigrationStages' => $itemTermsMigrationStages,
				'entityId' => ItemId::newFromNumber( 1 ),
				'callsValidation' => true
			]

		];
	}

	/**
	 * @dataProvider provider
	 */
	public function testSubject(
		int $propertyTermsMigrationStage,
		array $itemTermsMigrationStages,
		EntityId $entityId,
		bool $callsValidation
	) {
		$value = $this->createMock( ChangeOpFingerprintResult::class );
		$value->method( 'getEntityId' )->willReturn( $entityId );

		$expectedResult = Result::newSuccess();
		if ( $callsValidation ) {
			$expectedResult = Result::newError( [] );
			$this->fingerprintUniquenessValidator->expects( $this->once() )->method( 'validate' )->willReturn(
				$expectedResult
			);
		} else {
			$this->fingerprintUniquenessValidator->expects( $this->never() )->method( 'validate' );
		}

		$actualResult = $this->getSubjectResult( $value, $itemTermsMigrationStages, $propertyTermsMigrationStage );

		$this->assertEquals( $expectedResult, $actualResult );
	}

	private function getSubjectResult( $valueToValidate, $itemTermsMigrationStages, $propertyTermsMigrationStage ) {
		$validator = new ByIdFingerprintUniquenessValidator(
			$itemTermsMigrationStages,
			$propertyTermsMigrationStage,
			$this->fingerprintUniquenessValidator
		);
		return $validator->validate( $valueToValidate );
	}
}
