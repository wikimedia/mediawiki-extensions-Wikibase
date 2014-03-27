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
use Wikibase\Validators\TermChangeValidationHelper;

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
	 * invalid, the combination of the label "DUPE" and the description "DUPE" is considered
	 * a duplicate.
	 *
	 * @return TermChangeValidationHelper
	 */
	private function getMockTermChangeValidationHelper() {
		$mock = $this->getMockBuilder( 'Wikibase\Validators\TermChangeValidationHelper' )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->any() )
			->method( 'validateLanguage' )
			->will( $this->returnCallback( function( $lang ) {
				if ( $lang == 'INVALID' ) {
					$error = Error::newError( 'Invalid', '', 'test-invalid' );
					throw new ChangeOpValidationException( Result::newError( array( $error ) ) );
				}
			} ) );

		$mock->expects( $this->any() )
			->method( 'validateAlias' )
			->will( $this->returnCallback( function( $lang, $text ) {
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
		new ChangeOpAliases( $language, $aliases, $action, $this->getMockTermChangeValidationHelper() );
	}

	public function changeOpAliasesProvider() {
		$validation = $this->getMockTermChangeValidationHelper();

		$enAliases = array( 'en-alias1', 'en-alias2', 'en-alias3' );
		$existingEnAliases = array ( 'en-existingAlias1', 'en-existingAlias2' );
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();
		$entity->setAliases( 'en', $existingEnAliases );

		$args = array();
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $enAliases, 'add', $validation ), array_merge( $existingEnAliases, $enAliases ) );
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $enAliases, 'set', $validation ), $enAliases );
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $enAliases, '', $validation ), $enAliases );
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $existingEnAliases, 'remove', $validation ), array() );

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
		// "INVALID" is invalid, "DUPE" is a dupe
		$validation = $this->getMockTermChangeValidationHelper();

		$args = array();
		$args['set invalid alias'] = array ( new ChangeOpAliases( 'fr', array( 'INVALID' ), 'set', $validation ) );
		$args['add invalid alias'] = array ( new ChangeOpAliases( 'fr', array( 'INVALID' ), 'add', $validation ) );
		$args['set invalid language'] = array ( new ChangeOpAliases( 'INVALID', array( 'valid' ), 'set', $validation ) );
		$args['add invalid language'] = array ( new ChangeOpAliases( 'INVALID', array( 'valid' ), 'add', $validation ) );

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
		$validation = $this->getMockTermChangeValidationHelper();

		$changeOpAliases = new ChangeOpAliases( 'en', array( 'test' ), 'invalidAction', $validation );

		$this->setExpectedException( 'Wikibase\ChangeOp\ChangeOpException' );
		$changeOpAliases->apply( $entity );
	}

}
