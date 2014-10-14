<?php

namespace Wikibase\Test;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Term;
use Wikibase\Validators\UniquenessViolation;

/**
 * @covers Wikibase\LabelDescriptionDuplicateDetector
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LabelDescriptionDuplicateDetectorTest extends \PHPUnit_Framework_TestCase {

	private function getWorld() {
		$world = array();

		$world[] = new Term( array(
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'item label',
		) );

		$world[] = new Term( array(
			'termType' => Term::TYPE_DESCRIPTION,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'item description',
		) );

		$world[] = new Term( array(
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => 17,
			'entityType' => Property::ENTITY_TYPE,
			'termText' => 'property label',
		) );

		return $world;
	}

	public function provideDetectTermConflicts() {
		$world = $this->getWorld();

		$labelError = new UniquenessViolation(
			new ItemId( 'Q42' ),
			'Conflicting term!',
			'label-conflict',
			array(
				'item label',
				'en',
				new ItemId( 'Q42' )
			)
		);

		$descriptionError = new UniquenessViolation(
			new ItemId( 'Q42' ),
			'Conflicting term!',
			'label-with-description-conflict',
			array(
				'item label',
				'en',
				new ItemId( 'Q42' )
			)
		);

		return array(
			'no label conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'foo' ),
				null,
				null,
				array()
			),

			'label conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'item label' ),
				null,
				null,
				array( $labelError )
			),

			'other entity type' => array(
				$world,
				Property::ENTITY_TYPE,
				array( 'en' => 'item label' ),
				null,
				null,
				array()
			),

			'ignored label conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'item label' ),
				null,
				new ItemId( 'Q42' ),
				array()
			),

			'no label/description conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'item label' ),
				array(),
				null,
				array()
			),

			'label/description conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'item label' ),
				array( 'en' => 'item description' ),
				null,
				array( $descriptionError )
			),

			'ignored label/description conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'item label' ),
				array( 'en' => 'item description' ),
				new ItemId( 'Q42' ),
				array()
			),
		);
	}

	/**
	 * @dataProvider provideDetectTermConflicts
	 */
	public function testDetectTermConflicts( $world, $entityType, $labels, $descriptions, $ignore, $expectedErrors ) {
		$detector = new LabelDescriptionDuplicateDetector( new MockTermIndex( $world ) );

		$result = $detector->detectTermConflicts( $entityType, $labels, $descriptions, $ignore );

		$this->assertResult( $result, $expectedErrors );
	}

	/**
	 * @param Result $result
	 * @param Error[] $expectedErrors
	 */
	protected function assertResult( Result $result, $expectedErrors ) {
		$this->assertEquals( empty( $expectedErrors ), $result->isValid(), 'isValid()' );
		$errors = $result->getErrors();

		$this->assertSameSize( $expectedErrors, $errors, 'Number of errors:' );

		foreach ( $expectedErrors as $i => $expectedError ) {
			$error = $errors[$i];

			$this->assertEquals( $expectedError->getCode(), $error->getCode(), 'Error code:' );
			$this->assertEquals( $expectedError->getParameters(), $error->getParameters(), 'Error parameters:' );

			$this->assertInstanceOf( 'Wikibase\Validators\UniquenessViolation', $error );
			$this->assertEquals( $expectedError->getConflictingEntity(), $error->getConflictingEntity() );
		}
	}

}
