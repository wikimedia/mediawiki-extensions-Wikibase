<?php

namespace Wikibase\Test;

use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\ItemContent;
use InvalidArgumentException;
use ValueValidators\ValueValidator;

/**
 * @covers Wikibase\ChangeOp\ChangeOpAliases
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpAliasesTest extends \PHPUnit_Framework_TestCase {


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

	public function invalidConstructorProvider() {
		$args = array();
		$args[] = array( 42, array( 'myNewAlias' ), 'add' );
		$args[] = array( 'en', array( 'myNewAlias' ), 1234 );

		return $args;
	}

	/**
	 * @dataProvider invalidConstructorProvider
	 * @expectedException InvalidArgumentException
	 *
	 * @param string $language
	 * @param string[] $aliases
	 * @param string $action
	 */
	public function testInvalidConstruct( $language, $aliases, $action ) {
		// INVALID is invalid!
		$validator = $this->getMockValidator();

		new ChangeOpAliases( $language, $aliases, $action, $validator, $validator );
	}

	public function changeOpAliasesProvider() {
		// INVALID is invalid!
		$validator = $this->getMockValidator();

		$enAliases = array( 'en-alias1', 'en-alias2', 'en-alias3' );
		$existingEnAliases = array ( 'en-existingAlias1', 'en-existingAlias2' );
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();
		$entity->setAliases( 'en', $existingEnAliases );

		$args = array();
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $enAliases, 'add', $validator, $validator ), array_merge( $existingEnAliases, $enAliases ) );
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $enAliases, 'set', $validator, $validator ), $enAliases );
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $enAliases, '', $validator, $validator ), $enAliases );
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $existingEnAliases, 'remove', $validator, $validator ), array() );

		return $args;
	}

	/**
	 * @dataProvider changeOpAliasesProvider
	 *
	 * @param Entity $entity
	 * @param ChangeOpAliases $changeOpAliases
	 * @param string $expectedAliases
	 */
	public function testApply( $entity, $changeOpAliases, $expectedAliases ) {
		$changeOpAliases->apply( $entity );
		$this->assertEquals( $expectedAliases, $entity->getAliases( 'en' ) );
	}

	public function invalidChangeOpAliasProvider() {
		// INVALID is invalid!
		$validator = $this->getMockValidator();

		$args = array();
		$args['set invalid alias'] = array ( new ChangeOpAliases( 'fr', array( 'INVALID' ), 'set', $validator, $validator ) );
		$args['add invalid alias'] = array ( new ChangeOpAliases( 'fr', array( 'INVALID' ), 'add', $validator, $validator ) );
		$args['set invalid language'] = array ( new ChangeOpAliases( 'INVALID', array( 'valid' ), 'set', $validator, $validator ) );
		$args['add invalid language'] = array ( new ChangeOpAliases( 'INVALID', array( 'valid' ), 'add', $validator, $validator ) );

		return $args;
	}

	/**
	 * @dataProvider invalidChangeOpAliasProvider
	 *
	 * @param ChangeOp $changeOpDescription
	 */
	public function testApplyInvalid( ChangeOp $changeOpDescription ) {
		$entity = Item::newEmpty();
		$entity->setLabel( 'fr', 'DUPE' );

		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpException' );
		$changeOpDescription->apply( $entity );
	}

	public function testApplyWithInvalidAction() {
		$entity = Item::newEmpty();
		$validator = $this->getMockValidator();

		$changeOpAliases = new ChangeOpAliases( 'en', array( 'test' ), 'invalidAction', $validator, $validator );

		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpException' );
		$changeOpAliases->apply( $entity );
	}

}
