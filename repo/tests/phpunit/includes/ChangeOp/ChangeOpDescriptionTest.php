<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpDescription;
use Wikibase\Repo\ChangeOp\ChangeOpDescriptionResult;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpDescription
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpDescriptionTest extends \PHPUnit\Framework\TestCase {

	private function getTermValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockTermValidatorFactory();
	}

	public function testInvalidConstruct() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$this->expectException( InvalidArgumentException::class );
		new ChangeOpDescription( 42, 'myNew', $validatorFactory );
	}

	public static function changeOpDescriptionProvider(): iterable {
		$args = [];
		$args['update'] = [ [ 'en', 'myNew' ], 'myNew' ];
		$args['set to null'] = [ [ 'en', null ], '' ];
		$args['noop'] = [ [ 'en', 'DUPE' ], 'DUPE' ];

		return $args;
	}

	/**
	 * @dataProvider changeOpDescriptionProvider
	 */
	public function testApply( array $changeOpDescriptionParams, string $expectedDescription ) {
		$entity = $this->provideNewEntity();
		$entity->setDescription( 'en', 'INVALID' );

		// "INVALID" is invalid
		$changeOpDescriptionParams[] = $this->getTermValidatorFactory();

		$changeOpDescription = new ChangeOpDescription( ...$changeOpDescriptionParams );
		$changeOpDescription->apply( $entity );

		if ( $expectedDescription === '' ) {
			$this->assertFalse( $entity->getDescriptions()->hasTermForLanguage( 'en' ) );
		} else {
			$this->assertEquals( $expectedDescription, $entity->getDescriptions()->getByLanguage( 'en' )->getText() );
		}
	}

	public static function changeOpDescriptionAndResultProvider(): iterable {
		$item = new Item( new ItemId( 'Q23' ) );
		return [
			'set Description is a change' => [
				$item,
				[ 'en', 'myNew' ],
				new ChangeOpDescriptionResult( $item->getId(), 'en', null, 'myNew', true ),
			],
			'update Description is a change' => [
				$item,
				[ 'en', 'DUPE' ],
				new ChangeOpDescriptionResult( $item->getId(), 'en', 'myNew', 'DUPE', true ),
			],
			'update to existing Description is a no change' => [
				$item,
				[ 'en', 'DUPE' ],
				new ChangeOpDescriptionResult( $item->getId(), 'en', 'DUPE', 'DUPE', false ),

			],
			'set to null is a change' => [
				$item,
				[ 'en', null ],
				new ChangeOpDescriptionResult( $item->getId(), 'en', 'DUPE', null, true ),

			],
			'set null to null is a no change' => [
				$item,
				[ 'en', null ],
				new ChangeOpDescriptionResult( $item->getId(), 'en', null, null, false ),

			],
		];
	}

	/**
	 * @dataProvider changeOpDescriptionAndResultProvider
	 */
	public function testApplySetsIsEntityChangedCorrectlyOnResult(
		Item $item,
		array $changeOpLabelParams,
		ChangeOpDescriptionResult $expectedChangeOpDescriptionResult
	) {
		// "INVALID" is invalid
		$changeOpLabelParams[] = $this->getTermValidatorFactory();

		$changeOpLabel = new ChangeOpDescription( ...$changeOpLabelParams );
		$changeOpResult = $changeOpLabel->apply( $item );

		$this->assertEquals( $expectedChangeOpDescriptionResult->isEntityChanged(), $changeOpResult->isEntityChanged() );
	}

	/**
	 * @dataProvider changeOpDescriptionAndResultProvider
	 */
	public function testApplySetsDescriptionsCorrectlyOnResult(
		Item $item,
		array $changeOpLabelParams,
		ChangeOpDescriptionResult $expectedChangeOpDescriptionResult
	) {
		// "INVALID" is invalid
		$changeOpLabelParams[] = $this->getTermValidatorFactory();

		$changeOpLabel = new ChangeOpDescription( ...$changeOpLabelParams );
		$changeOpResult = $changeOpLabel->apply( $item );

		$this->assertEquals( $expectedChangeOpDescriptionResult->getNewDescription(), $changeOpResult->getNewDescription() );
		$this->assertEquals( $expectedChangeOpDescriptionResult->getOldDescription(), $changeOpResult->getOldDescription() );
	}

	public static function validateProvider(): iterable {
		$args = [];
		$args['valid description'] = [ [ 'fr', 'valid' ], true ];
		$args['invalid description'] = [ [ 'fr', 'INVALID' ], false ];
		$args['duplicate description'] = [ [ 'fr', 'DUPE' ], false ];
		$args['invalid language'] = [ [ 'INVALID', 'valid' ], false ];
		$args['set bad language to null'] = [ [ 'INVALID', null ], false ];

		return $args;
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( array $changeOpParams, bool $valid ) {
		$entity = $this->provideNewEntity();

		$oldDescriptions = $entity->getDescriptions()->toTextArray();

		// "INVALID" is invalid
		$changeOpParams[] = $this->getTermValidatorFactory();

		$changeOp = new ChangeOpDescription( ...$changeOpParams );
		$result = $changeOp->validate( $entity );
		$this->assertEquals( $valid, $result->isValid(), 'isValid()' );

		// descriptions should not have changed during validation
		$newDescriptions = $entity->getDescriptions()->toTextArray();
		$this->assertEquals( $oldDescriptions, $newDescriptions, 'Descriptions modified by validation!' );
	}

	/**
	 * @return Item
	 */
	private static function provideNewEntity() {
		$item = new Item( new ItemId( 'Q23' ) );
		$item->setLabel( 'en', 'DUPE' );
		$item->setDescription( 'en', 'DUPE' );
		$item->setLabel( 'fr', 'DUPE' );

		return $item;
	}

	public static function changeOpSummaryProvider() {
		$args = [];

		$entity = self::provideNewEntity();
		$entity->setDescription( 'de', 'Test' );
		$args[] = [ $entity, [ 'de', 'Zusammenfassung' ], 'set', 'de' ];

		$entity = self::provideNewEntity();
		$entity->setDescription( 'de', 'Test' );
		$args[] = [ $entity, [ 'de', null ], 'remove', 'de' ];

		$entity = self::provideNewEntity();
		$args[] = [ $entity, [ 'de', 'Zusammenfassung' ], 'add', 'de' ];

		return $args;
	}

	/**
	 * @dataProvider changeOpSummaryProvider
	 */
	public function testUpdateSummary(
		EntityDocument $entity,
		array $changeOpParams,
		$summaryExpectedAction,
		$summaryExpectedLanguage
	) {
		$summary = new Summary();

		// "INVALID" is invalid
		$changeOpParams[] = $this->getTermValidatorFactory();

		$changeOp = new ChangeOpDescription( ...$changeOpParams );
		$changeOp->apply( $entity, $summary );

		$this->assertSame( $summaryExpectedAction, $summary->getMessageKey() );
		$this->assertEquals( $summaryExpectedLanguage, $summary->getLanguageCode() );
	}

	public function testApplyNoDescriptionsProvider() {
		$changeOp = new ChangeOpDescription( 'en', 'Foo', $this->getTermValidatorFactory() );
		$entity = $this->createMock( EntityDocument::class );

		$this->expectException( InvalidArgumentException::class );
		$changeOp->apply( $entity );
	}

	public function testGetActions() {
		$changeOp = new ChangeOpDescription( 'en', 'Foo', $this->getTermValidatorFactory() );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT_TERMS ], $changeOp->getActions() );
	}

}
