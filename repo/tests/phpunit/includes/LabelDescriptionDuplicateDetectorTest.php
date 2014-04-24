<?php

namespace Wikibase\Test;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Term;
use Wikibase\LabelDescriptionDuplicateDetector;
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

	private function makeItem( $id, $lang = null, $label = null, $description = null ) {
		$item = Item::newEmpty();
		$item->setId( new ItemId( $id ) );

		if ( $label !== null ) {
			$item->setLabel( $lang, $label );
		}

		if ( $description !== null ) {
			$item->setDescription( $lang, $description );
		}

		return $item;
	}

	public function provideLabelConflictsForEntity() {
		$world = $this->getWorld();

		$empty = $this->makeItem( 'Q23' );
		$differentLabel = $this->makeItem( 'Q23', 'en', 'another item label' );
		$differentLanguage = $this->makeItem( 'Q23', 'fr', 'item label' );
		$differentType = $this->makeItem( 'Q23', 'en', 'property label' );

		$conflict = $this->makeItem( 'Q23', 'en', 'item label' );
		$sameId = $this->makeItem( 'Q42', 'en', 'item label' );

		$error = new UniquenessViolation(
			new ItemId( 'Q42' ),
			'Conflicting term!',
			'label-conflict',
			array(
				'item label',
				'en',
				new ItemId( 'Q42' )
			)
		);

		return array(
			'empty' => array( $world, $empty, array() ),
			'different label' => array( $world, $differentLabel, array() ),
			'different language' => array( $world, $differentLanguage, array() ),
			'different type' => array( $world, $differentType, array() ),
			'label conflict' => array( $world, $conflict, array( $error ) ),
			'same id' => array( $world, $sameId, array() ),
		);
	}

	/**
	 * @dataProvider provideLabelConflictsForEntity
	 *
	 * @param Term[] $world The world to check conflicts against
	 * @param Entity $entity The entity to check for conflicts
	 * @param Error[] $expectedErrors The expected conflicts
	 */
	public function testDetectLabelConflictsForEntity( $world, $entity, $expectedErrors ) {
		$detector = new LabelDescriptionDuplicateDetector( new MockTermIndex( $world ) );

		$result = $detector->detectLabelConflictsForEntity( $entity );

		$this->assertResult( $result, $expectedErrors );
	}

	public function provideLabelDescriptionConflictsForEntity() {
		$world = $this->getWorld();

		$empty = $this->makeItem( 'Q23' );
		$noDescription = $this->makeItem( 'Q23', 'en', 'item label' );
		$differentLabel = $this->makeItem( 'Q23', 'en', 'another item label', 'item description' );
		$differentDescription = $this->makeItem( 'Q23', 'en', 'item label', 'another item description' );
		$differentLanguage = $this->makeItem( 'Q23', 'fr', 'item label', 'item description' );

		$conflict = $this->makeItem( 'Q23', 'en', 'item label', 'item description' );
		$sameId = $this->makeItem( 'Q42', 'en', 'item label', 'item description' );

		$error = new UniquenessViolation(
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
			'empty' => array( $world, $empty, array() ),
			'no description' => array( $world, $noDescription, array() ),
			'different label' => array( $world, $differentLabel, array() ),
			'different description' => array( $world, $differentDescription, array() ),
			'different language' => array( $world, $differentLanguage, array() ),
			'label conflict' => array( $world, $conflict, array( $error ) ),
			'same id' => array( $world, $sameId, array() ),
		);
	}

	/**
	 * @dataProvider provideLabelDescriptionConflictsForEntity
	 *
	 * @param Term[] $world The world to check conflicts against
	 * @param Entity $entity The entity to check for conflicts
	 * @param Error[] $expectedErrors The expected conflicts
	 */
	public function testLabelDescriptionConflictsForEntity( $world, $entity, $expectedErrors ) {
		$detector = new LabelDescriptionDuplicateDetector( new MockTermIndex( $world ) );

		$result = $detector->detectLabelDescriptionConflictsForEntity( $entity );

		$this->assertResult( $result, $expectedErrors );
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
				array( 'en' => 'foo' ),
				null,
				null,
				array()
			),

			'label conflict' => array(
				$world,
				array( 'en' => 'item label' ),
				null,
				null,
				array( $labelError )
			),

			'ignored label conflict' => array(
				$world,
				array( 'en' => 'item label' ),
				null,
				new ItemId( 'Q42' ),
				array()
			),

			'no label/description conflict' => array(
				$world,
				array( 'en' => 'item label' ),
				array(),
				null,
				array()
			),

			'label/description conflict' => array(
				$world,
				array( 'en' => 'item label' ),
				array( 'en' => 'item description' ),
				null,
				array( $descriptionError )
			),

			'ignored label/description conflict' => array(
				$world,
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
	public function testDetectTermConflicts( $world, $labels, $descriptions, $ignore, $expectedErrors ) {
		$detector = new LabelDescriptionDuplicateDetector( new MockTermIndex( $world ) );

		$result = $detector->detectTermConflicts( $labels, $descriptions, $ignore );

		$this->assertResult( $result, $expectedErrors );
	}

	/**
	 * @param Result $result
	 * @param Error[] $expectedErrors
	 */
	protected function assertResult( Result $result, $expectedErrors ) {
		$this->assertEquals( empty( $expectedErrors ), $result->isValid(), 'isValid()' );
		$errors = $result->getErrors();

		$this->assertEquals( count( $expectedErrors ), count( $errors ), 'Number of errors:' );

		foreach ( $expectedErrors as $i => $expectedError ) {
			$error = $errors[$i];

			$this->assertEquals( $expectedError->getCode(), $error->getCode(), 'Error code:' );
			$this->assertEquals( $expectedError->getParameters(), $error->getParameters(), 'Error parameters:' );

			$this->assertInstanceOf( 'Wikibase\Validators\UniquenessViolation', $error );
			$this->assertEquals( $expectedError->getConflictingEntity(), $error->getConflictingEntity() );
		}
	}

}
