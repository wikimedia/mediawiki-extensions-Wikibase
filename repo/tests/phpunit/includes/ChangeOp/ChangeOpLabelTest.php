<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
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

	public function changeOpLabelProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$args = [];
		$args['update'] = [ new ChangeOpLabel( 'en', 'myNew', $validatorFactory ), 'myNew' ];
		$args['set to null'] = [ new ChangeOpLabel( 'en', null, $validatorFactory ), '' ];
		$args['noop'] = [ new ChangeOpLabel( 'en', 'DUPE', $validatorFactory ), 'DUPE' ];

		return $args;
	}

	/**
	 * @dataProvider changeOpLabelProvider
	 *
	 * @param ChangeOp $changeOpLabel
	 * @param string $expectedLabel
	 */
	public function testApply( ChangeOp $changeOpLabel, $expectedLabel ) {
		$entity = $this->provideNewEntity();
		$entity->setLabel( 'en', 'INVALID' );

		$changeOpLabel->apply( $entity );

		if ( $expectedLabel === '' ) {
			$this->assertFalse( $entity->getLabels()->hasTermForLanguage( 'en' ) );
		} else {
			$this->assertEquals( $expectedLabel, $entity->getLabels()->getByLanguage( 'en' )->getText() );
		}
	}

	public function changeOpLabelAndResultProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$item = new Item( new ItemId( 'Q23' ) );

		$args = [
			'add Label is a change' => [
				$item,
				new ChangeOpLabel( 'en', 'myNew', $validatorFactory ),
				new ChangeOpLabelResult( $item->getId(), 'en', null, 'myNew', true ),
			],
			'update Label is a change' => [
				$item,
				new ChangeOpLabel( 'en', 'DUPE', $validatorFactory ),
				new ChangeOpLabelResult( $item->getId(), 'en', 'myNew', 'DUPE', true ),

			],
			'add existing label is a no change' => [
				$item,
				new ChangeOpLabel( 'en', 'DUPE', $validatorFactory ),
				new ChangeOpLabelResult( $item->getId(), 'en', 'DUPE', 'DUPE', false ),

			],
			'set Label to null is a change' => [
				$item,
				new ChangeOpLabel( 'en', null, $validatorFactory ),
				new ChangeOpLabelResult( $item->getId(), 'en', 'DUPE', null, true ),
			],
			'set null Label to null is a no change' => [
				$item,
				new ChangeOpLabel( 'en', null, $validatorFactory ),
				new ChangeOpLabelResult( $item->getId(), 'en', null, null, false ),
			],
		];

		return $args;
	}

	/**
	 * @param Item $item
	 * @param ChangeOpLabel $changeOpLabel
	 * @param ChangeOpLabelResult $expectedChangeOpLabelResult
	 * @dataProvider changeOpLabelAndResultProvider
	 */
	public function testApplySetsIsEntityChangedCorrectlyOnResult( $item, $changeOpLabel, $expectedChangeOpLabelResult ) {
		$changeOpResult = $changeOpLabel->apply( $item );

		$this->assertEquals( $expectedChangeOpLabelResult->isEntityChanged(), $changeOpResult->isEntityChanged() );
	}

	/**
	 * @param Item $item
	 * @param ChangeOpLabel $changeOpLabel
	 * @param ChangeOpLabelResult $expectedChangeOpLabelResult
	 * @dataProvider changeOpLabelAndResultProvider
	 */
	public function testApplySetsLabelsCorrectlyOnResult( $item, $changeOpLabel, $expectedChangeOpLabelResult ) {
		$changeOpResult = $changeOpLabel->apply( $item );

		$this->assertEquals( $expectedChangeOpLabelResult->getNewLabel(), $changeOpResult->getNewLabel() );
		$this->assertEquals( $expectedChangeOpLabelResult->getOldLabel(), $changeOpResult->getOldLabel() );
	}

	public function validateProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$args = [];
		$args['valid label'] = [ new ChangeOpLabel( 'fr', 'valid', $validatorFactory ), true ];
		$args['invalid label'] = [ new ChangeOpLabel( 'fr', 'INVALID', $validatorFactory ), false ];
		$args['duplicate label'] = [ new ChangeOpLabel( 'fr', 'DUPE', $validatorFactory ), false ];
		$args['invalid language'] = [ new ChangeOpLabel( 'INVALID', 'valid', $validatorFactory ), false ];
		$args['set bad language to null'] = [ new ChangeOpLabel( 'INVALID', null, $validatorFactory ), false ];

		return $args;
	}

	/**
	 * @dataProvider validateProvider
	 *
	 * @param ChangeOp $changeOp
	 * @param bool $valid
	 */
	public function testValidate( ChangeOp $changeOp, $valid ) {
		$entity = $this->provideNewEntity();

		$oldLabels = $entity->getLabels()->toTextArray();

		$result = $changeOp->validate( $entity );
		$this->assertEquals( $valid, $result->isValid(), 'isValid()' );

		// labels should not have changed during validation
		$newLabels = $entity->getLabels()->toTextArray();
		$this->assertEquals( $oldLabels, $newLabels, 'Labels modified by validation!' );
	}

	/**
	 * @return Item
	 */
	private function provideNewEntity() {
		$item = new Item( new ItemId( 'Q23' ) );
		$item->setDescription( 'en', 'DUPE' );
		$item->setLabel( 'en', 'DUPE' );
		$item->setDescription( 'fr', 'DUPE' );

		return $item;
	}

	public function changeOpSummaryProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$args = [];

		$entity = $this->provideNewEntity();
		$entity->setLabel( 'de', 'Test' );
		$args[] = [ $entity, new ChangeOpLabel( 'de', 'Zusammenfassung', $validatorFactory ), 'set', 'de' ];

		$entity = $this->provideNewEntity();
		$entity->setLabel( 'de', 'Test' );
		$args[] = [ $entity, new ChangeOpLabel( 'de', null, $validatorFactory ), 'remove', 'de' ];

		$entity = $this->provideNewEntity();
		$entity->getLabels()->removeByLanguage( 'de' );
		$args[] = [ $entity, new ChangeOpLabel( 'de', 'Zusammenfassung', $validatorFactory
		), 'add', 'de' ];

		return $args;
	}

	/**
	 * @dataProvider changeOpSummaryProvider
	 */
	public function testUpdateSummary(
		EntityDocument $entity,
		ChangeOp $changeOp,
		$summaryExpectedAction,
		$summaryExpectedLanguage
	) {
		$summary = new Summary();

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
