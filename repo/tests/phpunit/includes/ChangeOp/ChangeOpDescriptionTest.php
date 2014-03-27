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
use Wikibase\ChangeOp\ChangeOpDescription;
use InvalidArgumentException;
use ValueValidators\ValueValidator;

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

		new ChangeOpDescription( 42, 'myNew', $validator, $validator );
	}

	public function changeOpDescriptionProvider() {
		// "INVALID" is invalid
		$validator = $this->getMockValidator();

		$args = array();
		$args['update'] = array ( new ChangeOpDescription( 'en', 'myNew', $validator, $validator ), 'myNew' );
		$args['set to null'] = array ( new ChangeOpDescription( 'en', null, $validator, $validator ), '' );

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

	public function invalidChangeOpDescriptionProvider() {
		// "INVALID" is invalid
		$validator = $this->getMockValidator();

		$args = array();
		$args['invalid description'] = array ( new ChangeOpDescription( 'fr', 'INVALID', $validator, $validator ) );
		$args['invalid language'] = array ( new ChangeOpDescription( 'INVALID', 'valid', $validator, $validator ) );

		return $args;
	}

	/**
	 * @dataProvider invalidChangeOpDescriptionProvider
	 *
	 * @param ChangeOp $changeOpDescription
	 */
	public function testApplyInvalid( ChangeOp $changeOpDescription ) {
		$entity = $this->provideNewEntity();

		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpValidationException' );
		$changeOpDescription->apply( $entity );
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
		$entity->setDescription( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', 'Zusammenfassung', $validator, $validator ), 'set', 'de' );

		$entity = $this->provideNewEntity();
		$entity->setDescription( 'de', 'Test' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', null, $validator, $validator ), 'remove', 'de' );

		$entity = $this->provideNewEntity();
		$entity->removeDescription( 'de' );
		$args[] = array ( $entity, new ChangeOpDescription( 'de', 'Zusammenfassung', $validator, $validator ), 'add', 'de' );

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
