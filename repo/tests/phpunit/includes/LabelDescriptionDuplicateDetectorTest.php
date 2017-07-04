<?php

namespace Wikibase\Repo\Tests;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\LabelDescriptionDuplicateDetector;
use Wikibase\Lib\Tests\Store\MockTermIndex;
use Wikibase\Repo\Validators\UniquenessViolation;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\LabelDescriptionDuplicateDetector
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class LabelDescriptionDuplicateDetectorTest extends \PHPUnit_Framework_TestCase {

	private function getWorld() {
		$world = [];

		$world[] = new TermIndexEntry( [
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => new ItemId( 'Q42' ),
			'termText' => 'item label',
		] );

		$world[] = new TermIndexEntry( [
			'termType' => TermIndexEntry::TYPE_DESCRIPTION,
			'termLanguage' => 'en',
			'entityId' => new ItemId( 'Q42' ),
			'termText' => 'item description',
		] );

		$world[] = new TermIndexEntry( [
			'termType' => TermIndexEntry::TYPE_ALIAS,
			'termLanguage' => 'en',
			'entityId' => new ItemId( 'Q42' ),
			'termText' => 'item alias',
		] );

		$world[] = new TermIndexEntry( [
			'termType' => TermIndexEntry::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => new PropertyId( 'P42' ),
			'termText' => 'property label',
		] );

		return $world;
	}

	public function provideDetectLabelDescriptionConflicts() {
		$world = $this->getWorld();

		$labelError = new UniquenessViolation(
			new ItemId( 'Q42' ),
			'Conflicting term!',
			'label-with-description-conflict',
			[
				'item label',
				'en',
				new ItemId( 'Q42' )
			]
		);

		return [
			'no label/description conflict' => [
				$world,
				Item::ENTITY_TYPE,
				[ 'en' => 'item label' ],
				[],
				null,
				[]
			],

			'label/description conflict' => [
				$world,
				Item::ENTITY_TYPE,
				[ 'en' => 'item label' ],
				[ 'en' => 'item description' ],
				null,
				[ $labelError ]
			],

			'ignored label/description conflict' => [
				$world,
				Item::ENTITY_TYPE,
				[ 'en' => 'item label' ],
				[ 'en' => 'item description' ],
				new ItemId( 'Q42' ),
				[]
			],
		];
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
			[
				'item label',
				'en',
				new ItemId( 'Q42' )
			]
		);

		$aliasError = new UniquenessViolation(
			new ItemId( 'Q42' ),
			'Conflicting term!',
			'label-conflict',
			[
				'item alias',
				'en',
				new ItemId( 'Q42' )
			]
		);

		return [
			'labels only: no conflict' => [
				$world,
				Item::ENTITY_TYPE,
				[ 'en' => 'item alias' ],
				null,
				null,
				[]
			],

			'labels only: conflict' => [
				$world,
				Item::ENTITY_TYPE,
				[ 'en' => 'item label' ],
				null,
				new ItemId( 'Q55' ), // ignores Q55, but conflict is with Q42
				[ $labelError ]
			],

			'labels only: other entity type' => [
				$world,
				Property::ENTITY_TYPE,
				[ 'en' => 'item label' ],
				null,
				null,
				[]
			],

			'labels only: ignored conflict' => [
				$world,
				Item::ENTITY_TYPE,
				[ 'en' => 'item label' ],
				null,
				new ItemId( 'Q42' ),
				[]
			],

			'labels+aliases: no conflict' => [
				$world,
				Item::ENTITY_TYPE,
				[ 'en' => 'foo' ],
				[ 'en' => [ 'bar' ] ],
				null,
				[]
			],

			'labels+aliases: empty' => [
				$world,
				Item::ENTITY_TYPE,
				[],
				[],
				null,
				[]
			],

			'labels+aliases: label conflict' => [
				$world,
				Item::ENTITY_TYPE,
				[ 'en' => 'item label' ],
				[],
				null,
				[ $labelError ]
			],

			'labels+aliases: alias conflict' => [
				$world,
				Item::ENTITY_TYPE,
				[],
				[ 'en' => [ 'item alias' ] ],
				new ItemId( 'Q55' ), // ignores Q55, but conflict is with Q42
				[ $aliasError ]
			],

			'labels+aliases: label conflicts with alias' => [
				$world,
				Item::ENTITY_TYPE,
				[ 'en' => 'item alias' ],
				[], // aliases must be enabled
				null,
				[ $aliasError ]
			],

			'labels+aliases: alias conflicts with label' => [
				$world,
				Item::ENTITY_TYPE,
				[],
				[ 'en' => [ 'item label' ] ],
				null,
				[ $labelError ]
			],

			'labels+aliases: other entity type' => [
				$world,
				Property::ENTITY_TYPE,
				[ 'en' => 'item label' ],
				[ 'en' => [ 'item alias' ] ],
				null,
				[]
			],
		];
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
