<?php

namespace Wikibase\Test;

use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\Entity;
use Wikibase\ItemContent;
use InvalidArgumentException;

/**
 * @covers Wikibase\ChangeOp\ChangeOpAliases
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpAliasesTest extends \PHPUnit_Framework_TestCase {

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
		$changeOpLabel = new ChangeOpAliases( $language, $aliases, $action );
	}

	public function changeOpAliasesProvider() {
		$enAliases = array( 'en-alias1', 'en-alias2', 'en-alias3' );
		$existingEnAliases = array ( 'en-existingAlias1', 'en-existingAlias2' );
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();
		$entity->setAliases( 'en', $existingEnAliases );

		$args = array();
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $enAliases, 'add' ), array_merge( $existingEnAliases, $enAliases ) );
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $enAliases, 'set' ), $enAliases );
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $enAliases, '' ), $enAliases );
		$args[] = array ( clone $entity, new ChangeOpAliases( 'en', $existingEnAliases, 'remove' ), array() );

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

	/**
	 * @expectedException \Wikibase\ChangeOp\ChangeOpException
	 */
	public function testApplyWithInvalidAction() {
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();
		$changeOpAliases = new ChangeOpAliases( 'en', array( 'test' ), 'invalidAction' );
		$changeOpAliases->apply( $entity );
	}

}
