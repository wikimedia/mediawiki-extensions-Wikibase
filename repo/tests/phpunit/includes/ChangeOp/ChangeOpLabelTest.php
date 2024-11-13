<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOpLabel;
use Wikibase\Repo\ChangeOp\ChangeOpLabelResult;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOpLabel
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpLabelTest extends \PHPUnit\Framework\TestCase {

	private function getTermValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockTermValidatorFactory();
	}

	public function testInvalidConstruct() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$this->expectException( InvalidArgumentException::class );
		new ChangeOpLabel( 42, 'myNew', $validatorFactory );
	}

	public static function changeOpLabelProvider(): iterable {
		$args = [];
		$args['update'] = [ [ 'en', 'myNew' ], 'myNew' ];
		$args['set to null'] = [ [ 'en', null ], '' ];
		$args['noop'] = [ [ 'en', 'DUPE' ], 'DUPE' ];

		return $args;
	}

	/**
	 * @dataProvider changeOpLabelProvider
	 */
	public function testApply( array $changeOpLabelParams, string $expectedLabel ) {
		$entity = $this->provideNewEntity();
		$entity->setLabel( 'en', 'INVALID' );

		// "INVALID" is invalid
		$changeOpLabelParams[] = $this->getTermValidatorFactory();
		$changeOpLabel = new ChangeOpLabel( ...$changeOpLabelParams );
		$changeOpLabel->apply( $entity );

		if ( $expectedLabel === '' ) {
			$this->assertFalse( $entity->getLabels()->hasTermForLanguage( 'en' ) );
		} else {
			$this->assertEquals( $expectedLabel, $entity->getLabels()->getByLanguage( 'en' )->getText() );
		}
	}

	public static function changeOpLabelAndResultProvider() {
		$item = new Item( new ItemId( 'Q23' ) );

		return [
			'add Label is a change' => [
				$item,
				[ 'en', 'myNew' ],
				new ChangeOpLabelResult( $item->getId(), 'en', null, 'myNew', true ),
			],
			'update Label is a change' => [
				$item,
				[ 'en', 'DUPE' ],
				new ChangeOpLabelResult( $item->getId(), 'en', 'myNew', 'DUPE', true ),

			],
			'add existing label is a no change' => [
				$item,
				[ 'en', 'DUPE' ],
				new ChangeOpLabelResult( $item->getId(), 'en', 'DUPE', 'DUPE', false ),

			],
			'set Label to null is a change' => [
				$item,
				[ 'en', null ],
				new ChangeOpLabelResult( $item->getId(), 'en', 'DUPE', null, true ),
			],
			'set null Label to null is a no change' => [
				$item,
				[ 'en', null ],
				new ChangeOpLabelResult( $item->getId(), 'en', null, null, false ),
			],
		];
	}

	/**
	 * @dataProvider changeOpLabelAndResultProvider
	 */
	public function testApplySetsIsEntityChangedCorrectlyOnResult(
		Item $item,
		array $changeOpLabelParams,
		ChangeOpLabelResult $expectedChangeOpLabelResult
	) {
		// "INVALID" is invalid
		$changeOpLabelParams[] = $this->getTermValidatorFactory();

		$changeOpLabel = new ChangeOpLabel( ...$changeOpLabelParams );
		$changeOpResult = $changeOpLabel->apply( $item );

		$this->assertEquals( $expectedChangeOpLabelResult->isEntityChanged(), $changeOpResult->isEntityChanged() );
	}

	/**
	 * @dataProvider changeOpLabelAndResultProvider
	 */
	public function testApplySetsLabelsCorrectlyOnResult(
		Item $item,
		array $changeOpLabelParams,
		ChangeOpLabelResult $expectedChangeOpLabelResult
	) {
		// "INVALID" is invalid
		$changeOpLabelParams[] = $this->getTermValidatorFactory();

		$changeOpLabel = new ChangeOpLabel( ...$changeOpLabelParams );
		$changeOpResult = $changeOpLabel->apply( $item );

		$this->assertEquals( $expectedChangeOpLabelResult->getNewLabel(), $changeOpResult->getNewLabel() );
		$this->assertEquals( $expectedChangeOpLabelResult->getOldLabel(), $changeOpResult->getOldLabel() );
	}

	public static function validateProvider(): iterable {
		$args = [];
		$args['valid label'] = [ [ 'fr', 'valid' ], true ];
		$args['invalid label'] = [ [ 'fr', 'INVALID' ], false ];
		$args['duplicate label'] = [ [ 'fr', 'DUPE' ], false ];
		$args['invalid language'] = [ [ 'INVALID', 'valid' ], false ];
		$args['set bad language to null'] = [ [ 'INVALID', null ], false ];

		return $args;
	}

	/**
	 * @dataProvider validateProvider
	 */
	public function testValidate( array $changeOpParams, bool $valid ) {
		$entity = $this->provideNewEntity();

		$oldLabels = $entity->getLabels()->toTextArray();

		// "INVALID" is invalid
		$changeOpParams[] = $this->getTermValidatorFactory();

		$changeOp = new ChangeOpLabel( ...$changeOpParams );
		$result = $changeOp->validate( $entity );
		$this->assertEquals( $valid, $result->isValid(), 'isValid()' );

		// labels should not have changed during validation
		$newLabels = $entity->getLabels()->toTextArray();
		$this->assertEquals( $oldLabels, $newLabels, 'Labels modified by validation!' );
	}

	/**
	 * @return Item
	 */
	private static function provideNewEntity() {
		$item = new Item( new ItemId( 'Q23' ) );
		$item->setDescription( 'en', 'DUPE' );
		$item->setLabel( 'en', 'DUPE' );
		$item->setDescription( 'fr', 'DUPE' );

		return $item;
	}

	public static function changeOpSummaryProvider(): iterable {
		$entity = self::provideNewEntity();
		$entity->setLabel( 'de', 'Test' );
		yield [ $entity, [ 'de', 'Zusammenfassung' ], 'set', 'de' ];

		$entity = self::provideNewEntity();
		$entity->setLabel( 'de', 'Test' );
		yield [ $entity, [ 'de', null ], 'remove', 'de' ];

		$entity = self::provideNewEntity();
		$entity->getLabels()->removeByLanguage( 'de' );
		yield [ $entity, [ 'de', 'Zusammenfassung' ], 'add', 'de' ];
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

		$changeOp = new ChangeOpLabel( ...$changeOpParams );
		$changeOp->apply( $entity, $summary );

		$this->assertSame( $summaryExpectedAction, $summary->getMessageKey() );
		$this->assertEquals( $summaryExpectedLanguage, $summary->getLanguageCode() );
	}

	public function testApplyNoLabelsProvider() {
		$changeOp = new ChangeOpLabel( 'en', 'Foo', $this->getTermValidatorFactory() );
		$entity = $this->createMock( EntityDocument::class );

		$this->expectException( InvalidArgumentException::class );
		$changeOp->apply( $entity );
	}

	public function testGetActions() {
		$changeOp = new ChangeOpLabel( 'en', 'Foo', $this->getTermValidatorFactory() );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT_TERMS ], $changeOp->getActions() );
	}

}
