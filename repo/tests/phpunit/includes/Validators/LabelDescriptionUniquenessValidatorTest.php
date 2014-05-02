<?php

namespace Wikibase\Test\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Validators\LabelDescriptionUniquenessValidator;

/**
 * @covers Wikibase\Validators\LabelDescriptionUniquenessValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LabelDescriptionUniquenessValidatorTest extends \PHPUnit_Framework_TestCase {

	public function detectLabelDescriptionConflictsForEntity( Entity $entity ) {
		foreach ( $entity->getLabels() as $lang => $label ) {
			$description = $entity->getDescription( $lang );

			if ( $description === null ) {
				continue;
			}

			if ( $label === 'DUPE' && $description === 'DUPE' ) {
				return Result::newError( array(
					Error::newError(
						'found conflicting terms',
						'label',
						'label-with-description-conflict',
						array(
							'label',
							$lang,
							$label,
							'Q666'
						)
					)
				) );
			}
		}

		return Result::newSuccess();
	}

	/**
	 * @return LabelDescriptionDuplicateDetector
	 */
	private function getMockDupeDetector() {
		$dupeDetector = $this->getMockBuilder( 'Wikibase\LabelDescriptionDuplicateDetector' )
			->disableOriginalConstructor()
			->getMock();

		$dupeDetector->expects( $this->any() )
			->method( 'detectLabelDescriptionConflictsForEntity' )
			->will( $this->returnCallback( array( $this, 'detectLabelDescriptionConflictsForEntity' ) ) );

		return $dupeDetector;
	}

	public function validEntityProvider() {
		$goodEntity1 = Item::newEmpty();
		$goodEntity1->setLabel( 'de', 'DUPE' );
		$goodEntity1->setId( new ItemId( 'Q5' ) );

		$goodEntity2 = $goodEntity1->copy();
		$goodEntity2->setDescription( 'de', 'Foo' );

		return array(
			array( $goodEntity1 ),
			array( $goodEntity2 ),
		);
	}

	public function invalidEntityProvider() {
		$badEntity = Item::newEmpty();
		$badEntity->setLabel( 'de', 'DUPE' );
		$badEntity->setDescription( 'de', 'DUPE' );
		$badEntity->setId( new ItemId( 'Q7' ) );

		return array(
			array( $badEntity, 'label-with-description-conflict' ),
		);
	}

	/**
	 * @dataProvider validEntityProvider
	 *
	 * @param Entity $entity
	 */
	public function testValidateEntity( Entity $entity ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelDescriptionUniquenessValidator( $dupeDetector );

		$result = $validator->validateEntity( $entity );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	/**
	 * @dataProvider invalidEntityProvider
	 *
	 * @param Entity $entity
	 * @param string|null $error
	 */
	public function testValidateEntity_failure( Entity $entity, $error ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelDescriptionUniquenessValidator( $dupeDetector );

		$result = $validator->validateEntity( $entity );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( $error, $errors[0]->getCode() );
	}

}
