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

		$error = Error::newError(
			'Conflicting term!',
			'label',
			'label-conflict',
			array(
				'item label',
				'en',
				new ItemId( 'Q42' )
			)
		);

		return array(
			'empty' => array( $world, $empty, null, array() ),
			'different label' => array( $world, $differentLabel, null, array() ),
			'different language' => array( $world, $differentLanguage, null, array() ),
			'different type' => array( $world, $differentType, null, array() ),
			'label conflict' => array( $world, $conflict, null, array( $error ) ),
			'same id' => array( $world, $sameId, null, array() ),
			'ignored conflict' => array( $world, $conflict, new ItemId( 'Q42' ), array() ),
		);
	}

	/**
	 * @dataProvider provideLabelConflictsForEntity
	 *
	 * @param Term[] $world The world to check conflicts against
	 * @param Entity $entity The entity to check for conflicts
	 * @param EntityId $ignoreId Id of an entity to ignore conflicts with
	 * @param Error[] $expectedErrors The expected conflicts
	 */
	public function testDetectLabelConflictsForEntity( $world, $entity, $ignoreId, $expectedErrors ) {
		$detector = new LabelDescriptionDuplicateDetector( new MockTermIndex( $world ) );

		$result = $detector->detectLabelConflictsForEntity( $entity, $ignoreId );

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

		$error = Error::newError(
			'Conflicting term!',
			'label',
			'label-with-description-conflict',
			array(
				'item label',
				'en',
				new ItemId( 'Q42' )
			)
		);

		return array(
			'empty' => array( $world, $empty, null, array() ),
			'no description' => array( $world, $noDescription, null, array() ),
			'different label' => array( $world, $differentLabel, null, array() ),
			'different description' => array( $world, $differentDescription, null, array() ),
			'different language' => array( $world, $differentLanguage, null, array() ),
			'label conflict' => array( $world, $conflict, null, array( $error ) ),
			'same id' => array( $world, $sameId, null, array() ),
			'ignored id' => array( $world, $conflict, new ItemId( 'Q42' ), array() ),
		);
	}

	/**
	 * @dataProvider provideLabelDescriptionConflictsForEntity
	 *
	 * @param Term[] $world The world to check conflicts against
	 * @param Entity $entity The entity to check for conflicts
	 * @param EntityId $ignoreId Id of an entity to ignore conflicts with
	 * @param Error[] $expectedErrors The expected conflicts
	 */
	public function testLabelDescriptionConflictsForEntity( $world, $entity, $ignoreId, $expectedErrors ) {
		$detector = new LabelDescriptionDuplicateDetector( new MockTermIndex( $world ) );

		$result = $detector->detectLabelDescriptionConflictsForEntity( $entity, $ignoreId );

		$this->assertResult( $result, $expectedErrors );
	}


	public function provideDetectTermConflicts() {
		$world = $this->getWorld();

		$labelError = Error::newError(
			'Conflicting term!',
			'label',
			'label-conflict',
			array(
				'item label',
				'en',
				new ItemId( 'Q42' )
			)
		);

		$descriptionError = Error::newError(
			'Conflicting term!',
			'label',
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
				array(),
				array()
			),

			'label conflict' => array(
				$world,
				array( 'en' => 'item label' ),
				null,
				array(),
				array( $labelError )
			),

			'ignored label conflict' => array(
				$world,
				array( 'en' => 'item label' ),
				null,
				array( new ItemId( 'Q42' ) ),
				array()
			),

			'no label/description conflict' => array(
				$world,
				array( 'en' => 'item label' ),
				array(),
				array(),
				array()
			),

			'label/description conflict' => array(
				$world,
				array( 'en' => 'item label' ),
				array( 'en' => 'item description' ),
				array(),
				array( $descriptionError )
			),

			'ignored label/description conflict' => array(
				$world,
				array( 'en' => 'item label' ),
				array( 'en' => 'item description' ),
				//NOTE: check that it works with the second ID too!
				array( new ItemId( 'Q23' ), new ItemId( 'Q42' ) ),
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

			$this->assertEquals( $expectedError->getProperty(), $error->getProperty(), 'Error property:' );
			$this->assertEquals( $expectedError->getCode(), $error->getCode(), 'Error code:' );
			$this->assertEquals( $expectedError->getParameters(), $error->getParameters(), 'Error parameters:' );
		}
	}

}
