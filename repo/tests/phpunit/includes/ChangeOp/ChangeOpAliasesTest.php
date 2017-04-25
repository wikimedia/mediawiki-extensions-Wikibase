<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\Content\EntityInstanceHolder;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\ChangeOp\ChangeOpAliases
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpAliasesTest extends \PHPUnit_Framework_TestCase {

	private function getTermValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockTermValidatorFactory();
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
	public function testInvalidConstruct( $language, array $aliases, $action ) {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		new ChangeOpAliases( $language, $aliases, $action, $validatorFactory );
	}

	public function changeOpAliasesProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		$enAliases = array( 'en-alias1', 'en-alias2', 'en-alias3' );
		$existingEnAliases = array( 'en-existingAlias1', 'en-existingAlias2' );
		$itemContent = new ItemContent( new EntityInstanceHolder( new Item() ) );
		$item = $itemContent->getEntity();
		$item->setAliases( 'en', $existingEnAliases );

		return array(
			'add' => array(
				$item->copy(),
				new ChangeOpAliases( 'en', $enAliases, 'add', $validatorFactory ),
				array_merge( $existingEnAliases, $enAliases )
			),
			'set' => array(
				$item->copy(),
				new ChangeOpAliases( 'en', $enAliases, 'set', $validatorFactory ),
				$enAliases
			),
			'set (default)' => array(
				$item->copy(),
				new ChangeOpAliases( 'en', $enAliases, '', $validatorFactory ),
				$enAliases
			),
			'remove' => array(
				$item->copy(),
				new ChangeOpAliases( 'en', $existingEnAliases, 'remove', $validatorFactory ),
				null
			),
		);
	}

	/**
	 * @dataProvider changeOpAliasesProvider
	 */
	public function testApply(
		Item $item,
		ChangeOpAliases $changeOpAliases,
		array $expectedAliases = null
	) {
		$changeOpAliases->apply( $item );
		$fingerprint = $item->getFingerprint();

		if ( $expectedAliases === null ) {
			$this->assertFalse( $fingerprint->hasAliasGroup( 'en' ) );
		} else {
			$this->assertEquals( $expectedAliases, $fingerprint->getAliasGroup( 'en' )->getAliases() );
		}
	}

	public function validateProvider() {
		// "INVALID" is invalid
		$validatorFactory = $this->getTermValidatorFactory();

		return array(
			'set invalid alias' => array(
				new ChangeOpAliases( 'fr', array( 'INVALID' ), 'set', $validatorFactory )
			),
			'add invalid alias' => array(
				new ChangeOpAliases( 'fr', array( 'INVALID' ), 'add', $validatorFactory )
			),
			'set invalid language' => array(
				new ChangeOpAliases( 'INVALID', array( 'valid' ), 'set', $validatorFactory )
			),
			'add invalid language' => array(
				new ChangeOpAliases( 'INVALID', array( 'valid' ), 'add', $validatorFactory )
			),
			'remove invalid language' => array(
				new ChangeOpAliases( 'INVALID', array( 'valid' ), 'remove', $validatorFactory )
			),
		);
	}

	/**
	 * @dataProvider validateProvider
	 *
	 * @param ChangeOpAliases $changeOpAliases
	 */
	public function testValidate( ChangeOpAliases $changeOpAliases ) {
		$entity = new Item();

		$result = $changeOpAliases->validate( $entity );
		$this->assertFalse( $result->isValid() );
	}

	public function testValidateLeavesEntityUntouched() {
		$entity = new Item();
		$validatorFactory = $this->getTermValidatorFactory();
		$changeOpAliases = new ChangeOpAliases( 'de', array( 'test' ), 'set', $validatorFactory );
		$changeOpAliases->validate( $entity );
		$this->assertTrue( $entity->equals( new Item() ) );
	}

	public function testApplyWithInvalidAction() {
		$entity = new Item();
		$validatorFactory = $this->getTermValidatorFactory();

		$changeOpAliases = new ChangeOpAliases( 'en', array( 'test' ), 'invalidAction', $validatorFactory );

		$this->setExpectedException( ChangeOpException::class );
		$changeOpAliases->apply( $entity );
	}

	public function testApplyNoAliasesProvider() {
		$changeOp = new ChangeOpAliases( 'en', array( 'Foo' ), 'set', $this->getTermValidatorFactory() );
		$entity = $this->getMock( EntityDocument::class );

		$this->setExpectedException( InvalidArgumentException::class );
		$changeOp->apply( $entity );
	}

}
