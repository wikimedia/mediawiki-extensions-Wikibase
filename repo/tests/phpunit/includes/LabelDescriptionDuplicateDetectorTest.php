<?php

namespace Wikibase\Test;

use ValueValidators\Error;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Term;
use Wikibase\LabelDescriptionDuplicateDetector;

/**
 * @covers Wikibase\LabelDescriptionDuplicateDetectorTest
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LabelDescriptionDuplicateDetectorTest extends \PHPUnit_Framework_TestCase {

	public function provideLabelConflictsForEntity() {
		$world = array();

		$world[] = new Term( array(
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'item label',
		) );

		$world[] = new Term( array(
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => 17,
			'entityType' => Property::ENTITY_TYPE,
			'termText' => 'property label',
		) );

		$empty = Item::newEmpty();
		$empty->setId( new ItemId( 'Q23' ) );

		$differentLabel = Item::newEmpty();
		$differentLabel->setId( new ItemId( 'Q23' ) );
		$differentLabel->setLabel( 'en', 'another item label' );

		$differentLanguage = Item::newEmpty();
		$differentLanguage->setId( new ItemId( 'Q23' ) );
		$differentLanguage->setLabel( 'fr', 'item label' );

		$differentType = Item::newEmpty();
		$differentType->setId( new ItemId( 'Q23' ) );
		$differentType->setLabel( 'en', 'property label' );

		$conflict = Item::newEmpty();
		$conflict->setId( new ItemId( 'Q23' ) );
		$conflict->setLabel( 'en', 'item label' );

		$sameId = Item::newEmpty();
		$sameId->setId( new ItemId( 'Q42' ) );
		$sameId->setLabel( 'en', 'item label' );

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

	public function provideLabelDescriptionConflictsForEntity() {
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

		$empty = Item::newEmpty();
		$empty->setId( new ItemId( 'Q23' ) );

		$noDescription = Item::newEmpty();
		$noDescription->setId( new ItemId( 'Q23' ) );
		$noDescription->setLabel( 'en', 'property label' );

		$differentLabel = Item::newEmpty();
		$differentLabel->setId( new ItemId( 'Q23' ) );
		$differentLabel->setLabel( 'en', 'another item label' );
		$differentLabel->setDescription( 'en', 'item description' );

		$differentDescription = Item::newEmpty();
		$differentDescription->setId( new ItemId( 'Q23' ) );
		$differentDescription->setLabel( 'en', 'item label' );
		$differentDescription->setDescription( 'en', 'another item description' );

		$differentLanguage = Item::newEmpty();
		$differentLanguage->setId( new ItemId( 'Q23' ) );
		$differentLanguage->setLabel( 'fr', 'item label' );
		$differentLanguage->setDescription( 'fr', 'item description' );

		$conflict = Item::newEmpty();
		$conflict->setId( new ItemId( 'Q23' ) );
		$conflict->setLabel( 'en', 'item label' );
		$conflict->setDescription( 'en', 'item description' );

		$sameId = Item::newEmpty();
		$sameId->setId( new ItemId( 'Q42' ) );
		$sameId->setLabel( 'en', 'item label' );
		$sameId->setDescription( 'en', 'item description' );

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
