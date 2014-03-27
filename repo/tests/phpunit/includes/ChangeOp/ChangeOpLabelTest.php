<?php

namespace Wikibase\Test;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Summary;
use Wikibase\ChangeOp\ChangeOpLabel;
use InvalidArgumentException;
use ValueValidators\ValueValidator;

/**
 * @covers Wikibase\ChangeOp\ChangeOpLabel
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpLabelTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Returns a mock validator. The term and the language "INVALID" is considered to be
	 * invalid.
	 *
	 * @return ValueValidator
	 */
	private function getMockValidator() {
		$mock = $this->getMockBuilder( 'ValueValidators\ValueValidator' )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->any() )
			->method( 'validate' )
			->will( $this->returnCallback( function( $text ) {
				if ( $text === 'INVALID' ) {
					$error = Error::newError( 'Invalid', '', 'test-invalid' );
					throw new ChangeOpValidationException( Result::newError( array( $error ) ) );
				}
			} ) );

		return $mock;
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct() {
		// "INVALID" is invalid
		$validator = $this->getMockValidator();

		new ChangeOpLabel( 42, 'myNew', $validator, $validator );
	}

	public function changeOpLabelProvider() {
		// "INVALID" is invalid
		$validator = $this->getMockValidator();

		$args = array();
		$args['update'] = array ( new ChangeOpLabel( 'en', 'myNew', $validator, $validator ), 'myNew' );
		$args['set to null'] = array ( new ChangeOpLabel( 'en', null, $validator, $validator ), '' );

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

		$this->assertEquals( $expectedLabel, $entity->getLabel( 'en' ) );
	}

	public function invalidChangeOpLabelProvider() {
		// "INVALID" is invalid
		$validator = $this->getMockValidator();

		$args = array();
		$args['invalid label'] = array ( new ChangeOpLabel( 'fr', 'INVALID', $validator, $validator ) );
		$args['invalid language'] = array ( new ChangeOpLabel( 'INVALID', 'valid', $validator, $validator ) );

		return $args;
	}

	/**
	 * @dataProvider invalidChangeOpLabelProvider
	 *
	 * @param ChangeOp $changeOpLabel
	 */
	public function testApplyInvalid( ChangeOp $changeOpLabel ) {
		$entity = $this->provideNewEntity();

		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpValidationException' );
		$changeOpLabel->apply( $entity );
	}

	/**
	 * @return Entity
	 */
	protected function provideNewEntity() {
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q23' ) );
		return $item;
	}

	public function changeOpSummaryProvider() {
		// "INVALID" is invalid
		$validator = $this->getMockValidator();

		$args = array();

		$entity = $this->provideNewEntity();
		$entity->setLabel( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpLabel( 'de', 'Zusammenfassung', $validator, $validator ), 'set', 'de' );

		$entity = $this->provideNewEntity();
		$entity->setLabel( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpLabel( 'de', null, $validator, $validator ), 'remove', 'de' );

		$entity = $this->provideNewEntity();
		$entity->removeLabel( 'de' );
		$args[] = array ( $entity, new ChangeOpLabel( 'de', 'Zusammenfassung', $validator, $validator ), 'add', 'de' );

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
