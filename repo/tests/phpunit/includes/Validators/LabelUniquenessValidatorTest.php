<?php

namespace Wikibase\Test\Validators;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\Validators\LabelUniquenessValidator;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\LabelDescriptionDuplicateDetector;

/**
 * @covers Wikibase\Validators\LabelUniquenessValidator
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LabelUniquenessValidatorTest extends \PHPUnit_Framework_TestCase {

	public function detectLabelConflictsForEntity( Entity $entity ) {
		foreach ( $entity->getLabels() as $lang => $label ) {
			if ( $label === 'DUPE' ) {
				return Result::newError( array(
					Error::newError(
						'found conflicting terms',
						'label',
						'label-conflict',
						array(
							'label',
							$lang,
							$label,
							'P666'
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
			->method( 'detectLabelConflictsForEntity' )
			->will( $this->returnCallback( array( $this, 'detectLabelConflictsForEntity' ) ) );

		return $dupeDetector;
	}

	public function validEntityProvider() {
		$goodEntity = Property::newFromType( 'string' );
		$goodEntity->setLabel( 'de', 'Foo' );
		$goodEntity->setDescription( 'de', 'DUPE' );
		$goodEntity->setId( new PropertyId( 'P5' ) );

		return array(
			array( $goodEntity ),
		);
	}

	public function invalidEntityProvider() {
		$badEntity = Property::newFromType( 'string' );
		$badEntity->setLabel( 'de', 'DUPE' );
		$badEntity->setId( new PropertyId( 'P7' ) );

		return array(
			array( $badEntity, 'label-conflict' ),
		);
	}

	/**
	 * @dataProvider validEntityProvider
	 *
	 * @param Entity $entity
	 */
	public function testValidateEntity( Entity $entity ) {
		$dupeDetector = $this->getMockDupeDetector();
		$validator = new LabelUniquenessValidator( $dupeDetector );

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
		$validator = new LabelUniquenessValidator( $dupeDetector );

		$result = $validator->validateEntity( $entity );

		$this->assertFalse( $result->isValid(), 'isValid' );

		$errors = $result->getErrors();
		$this->assertEquals( $error, $errors[0]->getCode() );
	}

}
