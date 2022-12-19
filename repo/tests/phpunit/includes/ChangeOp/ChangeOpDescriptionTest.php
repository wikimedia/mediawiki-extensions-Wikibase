<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
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

	public function changeOpDescriptionProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$args = [];
		$args['update'] = [ new ChangeOpDescription( 'en', 'myNew', $validatorFactory ), 'myNew' ];
		$args['set to null'] = [ new ChangeOpDescription( 'en', null, $validatorFactory ), '' ];
		$args['noop'] = [ new ChangeOpDescription( 'en', 'DUPE', $validatorFactory ), 'DUPE' ];

		return $args;
	}

	/**
	 * @dataProvider changeOpDescriptionProvider
	 *
	 * @param ChangeOp $changeOpDescription
	 * @param string $expectedDescription
	 */
	public function testApply( ChangeOp $changeOpDescription, $expectedDescription ) {
		$entity = $this->provideNewEntity();
		$entity->setDescription( 'en', 'INVALID' );

		$changeOpDescription->apply( $entity );

		if ( $expectedDescription === '' ) {
			$this->assertFalse( $entity->getDescriptions()->hasTermForLanguage( 'en' ) );
		} else {
			$this->assertEquals( $expectedDescription, $entity->getDescriptions()->getByLanguage( 'en' )->getText() );
		}
	}

	public function changeOpDescriptionAndResultProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$item = new Item( new ItemId( 'Q23' ) );
		$args = [
			'set Description is a change' => [
				$item,
				new ChangeOpDescription( 'en', 'myNew', $validatorFactory ),
				new ChangeOpDescriptionResult( $item->getId(), 'en', null, 'myNew', true ),
			],
			'update Description is a change' => [
				$item,
				new ChangeOpDescription( 'en', 'DUPE', $validatorFactory ),
				new ChangeOpDescriptionResult( $item->getId(), 'en', 'myNew', 'DUPE', true ),
			],
			'update to existing Description is a no change' => [
				$item,
				new ChangeOpDescription( 'en', 'DUPE', $validatorFactory ),
				new ChangeOpDescriptionResult( $item->getId(), 'en', 'DUPE', 'DUPE', false ),

			],
			'set to null is a change' => [
				$item,
				new ChangeOpDescription( 'en', null, $validatorFactory ),
				new ChangeOpDescriptionResult( $item->getId(), 'en', 'DUPE', null, true ),

			],
			'set null to null is a no change' => [
				$item,
				new ChangeOpDescription( 'en', null, $validatorFactory ),
				new ChangeOpDescriptionResult( $item->getId(), 'en', null, null, false ),

			],
		];
		return $args;
	}

	/**
	 * @param Item $item
	 * @param ChangeOpDescription $changeOpLabel
	 * @param ChangeOpDescriptionResult $expectedChangeOpDescriptionResult
	 * @dataProvider changeOpDescriptionAndResultProvider
	 */
	public function testApplySetsIsEntityChangedCorrectlyOnResult( $item, $changeOpLabel, $expectedChangeOpDescriptionResult ) {
		$changeOpResult = $changeOpLabel->apply( $item );

		$this->assertEquals( $expectedChangeOpDescriptionResult->isEntityChanged(), $changeOpResult->isEntityChanged() );
	}

	/**
	 * @param Item $item
	 * @param ChangeOpDescription $changeOpLabel
	 * @param ChangeOpDescriptionResult $expectedChangeOpDescriptionResult
	 * @dataProvider changeOpDescriptionAndResultProvider
	 */
	public function testApplySetsDescriptionsCorrectlyOnResult( $item, $changeOpLabel, $expectedChangeOpDescriptionResult ) {
		$changeOpResult = $changeOpLabel->apply( $item );

		$this->assertEquals( $expectedChangeOpDescriptionResult->getNewDescription(), $changeOpResult->getNewDescription() );
		$this->assertEquals( $expectedChangeOpDescriptionResult->getOldDescription(), $changeOpResult->getOldDescription() );
	}

	public function validateProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$args = [];
		$args['valid description'] = [ new ChangeOpDescription( 'fr', 'valid', $validatorFactory ), true ];
		$args['invalid description'] = [ new ChangeOpDescription( 'fr', 'INVALID', $validatorFactory ), false ];
		$args['duplicate description'] = [ new ChangeOpDescription( 'fr', 'DUPE', $validatorFactory ), false ];
		$args['invalid language'] = [ new ChangeOpDescription( 'INVALID', 'valid', $validatorFactory ), false ];
		$args['set bad language to null'] = [ new ChangeOpDescription( 'INVALID', null, $validatorFactory ), false ];

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

		$oldDescriptions = $entity->getDescriptions()->toTextArray();

		$result = $changeOp->validate( $entity );
		$this->assertEquals( $valid, $result->isValid(), 'isValid()' );

		// descriptions should not have changed during validation
		$newDescriptions = $entity->getDescriptions()->toTextArray();
		$this->assertEquals( $oldDescriptions, $newDescriptions, 'Descriptions modified by validation!' );
	}

	/**
	 * @return Item
	 */
	private function provideNewEntity() {
		$item = new Item( new ItemId( 'Q23' ) );
		$item->setLabel( 'en', 'DUPE' );
		$item->setDescription( 'en', 'DUPE' );
		$item->setLabel( 'fr', 'DUPE' );

		return $item;
	}

	public function changeOpSummaryProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$args = [];

		$entity = $this->provideNewEntity();
		$entity->setDescription( 'de', 'Test' );
		$args[] = [ $entity, new ChangeOpDescription( 'de', 'Zusammenfassung', $validatorFactory ), 'set', 'de' ];

		$entity = $this->provideNewEntity();
		$entity->setDescription( 'de', 'Test' );
		$args[] = [ $entity, new ChangeOpDescription( 'de', null, $validatorFactory ), 'remove', 'de' ];

		$entity = $this->provideNewEntity();
		$args[] = [ $entity, new ChangeOpDescription( 'de', 'Zusammenfassung', $validatorFactory ), 'add', 'de' ];

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
