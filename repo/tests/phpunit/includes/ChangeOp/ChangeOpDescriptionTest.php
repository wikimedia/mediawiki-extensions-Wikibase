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
		$validatorFactory = $this->getTermValidatorFactory();

		$this->expectException( InvalidArgumentException::class );
		new ChangeOpDescription( 42, 'myNew', $validatorFactory );
	}

	public static function changeOpDescriptionProvider(): iterable {
		yield 'update' => [ [ 'en', 'myNew' ], 'myNew' ];
		yield 'set to null' => [ [ 'en', null ], '' ];
		yield 'noop' => [ [ 'en', 'DUPE' ], 'DUPE' ];
		yield 'remove invalid' => [ [ 'INVALID', null ], '' ];
	}

	/**
	 * @dataProvider changeOpDescriptionProvider
	 */
	public function testApply( array $changeOpDescriptionParams, string $expectedDescription ) {
		$entity = $this->provideNewEntity();
		$entity->setDescription( 'en', 'INVALID' );
		$entity->setDescription( 'INVALID', 'INVALID' );
		$expectedLanguageCode = $changeOpDescriptionParams[0];

		$changeOpDescriptionParams[] = $this->getTermValidatorFactory();

		$changeOpDescription = new ChangeOpDescription( ...$changeOpDescriptionParams );
		$changeOpDescription->apply( $entity );

		if ( $expectedDescription === '' ) {
			$this->assertFalse( $entity->getDescriptions()->hasTermForLanguage( $expectedLanguageCode ) );
		} else {
			$this->assertEquals( $expectedDescription, $entity->getDescriptions()->getByLanguage( $expectedLanguageCode )->getText() );
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
		$changeOpLabelParams[] = $this->getTermValidatorFactory();

		$changeOpLabel = new ChangeOpDescription( ...$changeOpLabelParams );
		$changeOpResult = $changeOpLabel->apply( $item );

		$this->assertEquals( $expectedChangeOpDescriptionResult->getNewDescription(), $changeOpResult->getNewDescription() );
		$this->assertEquals( $expectedChangeOpDescriptionResult->getOldDescription(), $changeOpResult->getOldDescription() );
	}

	public static function validateProvider(): iterable {
		yield 'valid description' => [ [ 'fr', 'valid' ], true ];
		yield 'remove invalid language' => [ [ 'INVALID', null ], true ];
		yield 'invalid description' => [ [ 'fr', 'INVALID' ], false ];
		yield 'duplicate description' => [ [ 'fr', 'DUPE' ], false ];
		yield 'invalid language' => [ [ 'INVALID', 'valid' ], false ];
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( array $changeOpParams, bool $valid ) {
		$entity = $this->provideNewEntity();

		$oldDescriptions = $entity->getDescriptions()->toTextArray();

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
