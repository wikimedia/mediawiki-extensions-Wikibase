<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpDescription;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Summary;

/**
 * @covers Wikibase\ChangeOp\ChangeOpDescription
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpDescriptionTest extends \PHPUnit_Framework_TestCase {

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

		new ChangeOpDescription( 42, 'myNew', $validatorFactory );
	}

	public function changeOpDescriptionProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$args = array();
		$args['update'] = array ( new ChangeOpDescription( 'en', 'myNew', $validatorFactory ), 'myNew' );
		$args['set to null'] = array ( new ChangeOpDescription( 'en', null, $validatorFactory ), '' );

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

		$this->assertEquals( $expectedDescription, $entity->getDescription( 'en' ) );
	}

	public function validateProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$args = array();
		$args['valid description'] = array ( new ChangeOpDescription( 'fr', 'valid', $validatorFactory ), true );
		$args['invalid description'] = array ( new ChangeOpDescription( 'fr', 'INVALID', $validatorFactory ), false );
		$args['duplicate description'] = array ( new ChangeOpDescription( 'fr', 'DUPE', $validatorFactory ), false );
		$args['invalid language'] = array ( new ChangeOpDescription( 'INVALID', 'valid', $validatorFactory ), false );
		$args['set bad language to null'] = array ( new ChangeOpDescription( 'INVALID', null, $validatorFactory ), false );

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

		$oldLabels = $entity->getDescriptions();

		$result = $changeOp->validate( $entity );
		$this->assertEquals( $valid, $result->isValid(), 'isValid()' );

		// labels should not have changed during validation
		$this->assertEquals( $oldLabels, $entity->getDescriptions(), 'Descriptions modified by validation!' );
	}

	/**
	 * @return Entity
	 */
	protected function provideNewEntity() {
		$item = new Item( new ItemId( 'Q23' ) );
		$item->setLabel( 'en', 'DUPE' );
		$item->setLabel( 'fr', 'DUPE' );

		return $item;
	}

	public function changeOpSummaryProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$args = array();

		$entity = $this->provideNewEntity();
		$entity->setDescription( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', 'Zusammenfassung', $validatorFactory ), 'set', 'de' );

		$entity = $this->provideNewEntity();
		$entity->setDescription( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', null, $validatorFactory ), 'remove', 'de' );

		$entity = $this->provideNewEntity();
		$entity->removeDescription( 'de' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', 'Zusammenfassung', $validatorFactory ), 'add', 'de' );

		return $args;
	}

	/**
	 * @dataProvider changeOpSummaryProvider
	 */
	public function testUpdateSummary( $entity, ChangeOp $changeOp, $summaryExpectedAction, $summaryExpectedLanguage ) {
		$summary = new Summary();

		$changeOp->apply( $entity, $summary );

		$this->assertEquals( $summaryExpectedAction, $summary->getActionName() );
		$this->assertEquals( $summaryExpectedLanguage, $summary->getLanguageCode() );
	}

}
