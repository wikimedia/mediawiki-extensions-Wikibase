<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpLabel;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\Repo\Tests\ChangeOp\ChangeOpTestMockProvider;
use Wikibase\Summary;

/**
 * @covers Wikibase\ChangeOp\ChangeOpLabel
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpLabelTest extends \PHPUnit_Framework_TestCase {

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

		$args = array();
		$args['update'] = array( new ChangeOpLabel( 'en', 'myNew', $validatorFactory ), 'myNew' );
		$args['set to null'] = array( new ChangeOpLabel( 'en', null, $validatorFactory ), '' );

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
		$entity->getFingerprint()->setLabel( 'en', 'INVALID' );

		$changeOpLabel->apply( $entity );

		if ( $expectedLabel === '' ) {
			$this->assertFalse( $entity->getFingerprint()->hasLabel( 'en' ) );
		} else {
			$this->assertEquals( $expectedLabel, $entity->getFingerprint()->getLabel( 'en' )->getText() );
		}
	}

	public function validateProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$args = array();
		$args['valid label'] = array( new ChangeOpLabel( 'fr', 'valid', $validatorFactory ), true );
		$args['invalid label'] = array( new ChangeOpLabel( 'fr', 'INVALID', $validatorFactory ), false );
		$args['duplicate label'] = array( new ChangeOpLabel( 'fr', 'DUPE', $validatorFactory ), false );
		$args['invalid language'] = array( new ChangeOpLabel( 'INVALID', 'valid', $validatorFactory ), false );
		$args['set bad language to null'] = array( new ChangeOpLabel( 'INVALID', null, $validatorFactory ), false );

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

		$oldLabels = $entity->getFingerprint()->getLabels()->toTextArray();

		$result = $changeOp->validate( $entity );
		$this->assertEquals( $valid, $result->isValid(), 'isValid()' );

		// labels should not have changed during validation
		$newLabels = $entity->getFingerprint()->getLabels()->toTextArray();
		$this->assertEquals( $oldLabels, $newLabels, 'Labels modified by validation!' );
	}

	/**
	 * @return FingerprintProvider|EntityDocument
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

		$args = array();

		$entity = $this->provideNewEntity();
		$entity->getFingerprint()->setLabel( 'de', 'Test' );
		$args[] = array( $entity, new ChangeOpLabel( 'de', 'Zusammenfassung', $validatorFactory ), 'set', 'de' );

		$entity = $this->provideNewEntity();
		$entity->getFingerprint()->setLabel( 'de', 'Test' );
		$args[] = array( $entity, new ChangeOpLabel( 'de', null, $validatorFactory ), 'remove', 'de' );

		$entity = $this->provideNewEntity();
		$entity->getFingerprint()->removeLabel( 'de' );
		$args[] = array( $entity, new ChangeOpLabel( 'de', 'Zusammenfassung', $validatorFactory
		), 'add', 'de' );

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

		$this->assertEquals( $summaryExpectedAction, $summary->getActionName() );
		$this->assertEquals( $summaryExpectedLanguage, $summary->getLanguageCode() );
	}

	public function testApplyNoLabelsProvider() {
		$changeOp = new ChangeOpLabel( 'en', 'Foo', $this->getTermValidatorFactory() );
		$entity = $this->getMock( EntityDocument::class );

		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp->apply( $entity );
	}

}
