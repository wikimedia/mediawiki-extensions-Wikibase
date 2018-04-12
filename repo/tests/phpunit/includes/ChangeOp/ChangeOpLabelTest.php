<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use PHPUnit4And6Compat;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpLabel;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Summary;

/**
 * @covers Wikibase\Repo\ChangeOp\ChangeOpLabel
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpLabelTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	private function getTermValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockTermValidatorFactory();
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		new ChangeOpLabel( 42, 'myNew', $validatorFactory );
	}

	public function changeOpLabelProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$args = [];
		$args['update'] = [ new ChangeOpLabel( 'en', 'myNew', $validatorFactory ), 'myNew' ];
		$args['set to null'] = [ new ChangeOpLabel( 'en', null, $validatorFactory ), '' ];

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
		$entity = $this->getMock( EntityDocument::class );

		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp->apply( $entity );
	}

	public function testGetActions() {
		$changeOp = new ChangeOpLabel( 'en', 'Foo', $this->getTermValidatorFactory() );

		$this->assertEquals( [ EntityPermissionChecker::ACTION_EDIT_TERMS ], $changeOp->getActions() );
	}

}
