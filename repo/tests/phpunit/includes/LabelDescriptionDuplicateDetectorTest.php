<?php

namespace Wikibase\Test;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\Tests\Store\MockTermIndex;
use Wikibase\Repo\Validators\UniquenessViolation;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\LabelDescriptionDuplicateDetector
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class LabelDescriptionDuplicateDetectorTest extends \PHPUnit_Framework_TestCase {

	private function getWorld() {
		$world = array();

		$world[] = new TermIndexEntry( array(
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'item label',
		) );

		$world[] = new TermIndexEntry( array(
			'termType' => TermIndexEntry::TYPE_DESCRIPTION,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'item description',
		) );

		$world[] = new TermIndexEntry( array(
			'termType' => TermIndexEntry::TYPE_ALIAS,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'item alias',
		) );

		$world[] = new TermIndexEntry( array(
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => 17,
			'entityType' => Property::ENTITY_TYPE,
			'termText' => 'property label',
		) );

		return $world;
	}

	public function provideDetectLabelDescriptionConflicts() {
		$world = $this->getWorld();

		$labelError = new UniquenessViolation(
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
				array( $labelError )
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
	 * @dataProvider provideDetectLabelDescriptionConflicts
	 */
	public function testDetectLabelDescriptionConflicts(
		array $world,
		$entityType,
		array $labels,
		array $descriptions,
		$ignore,
		array $expectedErrors
	) {
		$detector = new LabelDescriptionDuplicateDetector( new MockTermIndex( $world ) );

		$result = $detector->detectLabelDescriptionConflicts( $entityType, $labels, $descriptions, $ignore );

		$this->assertResult( $result, $expectedErrors );
	}

	public function provideDetectLabelConflicts() {
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

		$aliasError = new UniquenessViolation(
			new ItemId( 'Q42' ),
			'Conflicting term!',
			'label-conflict',
			array(
				'item alias',
				'en',
				new ItemId( 'Q42' )
			)
		);

		return array(
			'labels only: no conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'item alias' ),
				null,
				null,
				array()
			),

			'labels only: conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'item label' ),
				null,
				new ItemId( 'Q55' ), // ignores Q55, but conflict is with Q42
				array( $labelError )
			),

			'labels only: other entity type' => array(
				$world,
				Property::ENTITY_TYPE,
				array( 'en' => 'item label' ),
				null,
				null,
				array()
			),

			'labels only: ignored conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'item label' ),
				null,
				new ItemId( 'Q42' ),
				array()
			),

			'labels+aliases: no conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'foo' ),
				array( 'en' => array( 'bar' ) ),
				null,
				array()
			),

			'labels+aliases: empty' => array(
				$world,
				Item::ENTITY_TYPE,
				array(),
				array(),
				null,
				array()
			),

			'labels+aliases: label conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'item label' ),
				array(),
				null,
				array( $labelError )
			),

			'labels+aliases: alias conflict' => array(
				$world,
				Item::ENTITY_TYPE,
				array(),
				array( 'en' => array( 'item alias' ) ),
				new ItemId( 'Q55' ), // ignores Q55, but conflict is with Q42
				array( $aliasError )
			),

			'labels+aliases: label conflicts with alias' => array(
				$world,
				Item::ENTITY_TYPE,
				array( 'en' => 'item alias' ),
				array(), // aliases must be enabled
				null,
				array( $aliasError )
			),

			'labels+aliases: alias conflicts with label' => array(
				$world,
				Item::ENTITY_TYPE,
				array(),
				array( 'en' => array( 'item label' ) ),
				null,
				array( $labelError )
			),

			'labels+aliases: other entity type' => array(
				$world,
				Property::ENTITY_TYPE,
				array( 'en' => 'item label' ),
				array( 'en' => array( 'item alias' ) ),
				null,
				array()
			),
		);
	}

	/**
	 * @dataProvider provideDetectLabelConflicts
	 */
	public function testDetectLabelConflicts(
		array $world,
		$entityType,
		array $labels,
		array $aliases = null,
		$ignore,
		array $expectedErrors
	) {
		$detector = new LabelDescriptionDuplicateDetector( new MockTermIndex( $world ) );

		$result = $detector->detectLabelConflicts( $entityType, $labels, $aliases, $ignore );

		$this->assertResult( $result, $expectedErrors );
	}

	/**
	 * @param Result $result
	 * @param UniquenessViolation[] $expectedErrors
	 */
	protected function assertResult( Result $result, array $expectedErrors ) {
		$this->assertEquals( empty( $expectedErrors ), $result->isValid(), 'isValid()' );
		$errors = $result->getErrors();

		$this->assertSameSize( $expectedErrors, $errors, 'Number of errors:' );

		foreach ( $expectedErrors as $i => $expectedError ) {
			$error = $errors[$i];

			$this->assertEquals( $expectedError->getCode(), $error->getCode(), 'Error code:' );
			$this->assertEquals( $expectedError->getParameters(), $error->getParameters(), 'Error parameters:' );

			$this->assertInstanceOf( UniquenessViolation::class, $error );
			/** @var UniquenessViolation $error */
			$this->assertEquals( $expectedError->getConflictingEntity(), $error->getConflictingEntity() );
		}
	}

}
