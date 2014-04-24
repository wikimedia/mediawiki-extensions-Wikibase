<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\ChangeOp\ChangeOpDescription;
use Wikibase\ChangeOp\ChangeOpLabel;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\ChangeOp\ChangeOps
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpsTest extends \PHPUnit_Framework_TestCase {

	public function testEmptyChangeOps() {
		$changeOps = new ChangeOps();
		$this->assertEmpty( $changeOps->getChangeOps() );
	}

	private function getTermValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockTermValidatorFactory();
	}

	/**
	 * @return ChangeOp[]
	 */
	public function changeOpProvider() {
		$validatorFactory = $this->getTermValidatorFactory();

		$ops = array();
		$ops[] = array ( new ChangeOpLabel( 'en', 'myNewLabel', $validatorFactory ) );
		$ops[] = array ( new ChangeOpDescription( 'de', 'myNewDescription', $validatorFactory ) );
		$ops[] = array ( new ChangeOpLabel( 'en', null, $validatorFactory ) );

		return $ops;
	}

	/**
	 * @dataProvider changeOpProvider
	 *
	 * @param ChangeOp $changeOp
	 */
	public function testAdd( $changeOp ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $changeOp );
		$this->assertEquals( array( $changeOp ), $changeOps->getChangeOps() );
	}

	public function changeOpArrayProvider() {
		$validatorFactory = $this->getTermValidatorFactory();

		$ops = array();
		$ops[] = array (
					array(
						new ChangeOpLabel( 'en', 'enLabel', $validatorFactory ),
						new ChangeOpLabel( 'de', 'deLabel', $validatorFactory ),
						new ChangeOpDescription( 'en', 'enDescr', $validatorFactory ),
					)
				);

		return $ops;
	}

	/**
	 * @dataProvider changeOpArrayProvider
	 *
	 * @param $changeOpArray
	 */
	public function testAddArray( $changeOpArray ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $changeOpArray );
		$this->assertEquals( $changeOpArray, $changeOps->getChangeOps() );
	}

	public function invalidChangeOpProvider() {
		$validatorFactory = $this->getTermValidatorFactory();

		$ops = array();
		$ops[] = array ( 1234 );
		$ops[] = array ( array( new ChangeOpLabel( 'en', 'test', $validatorFactory ), 123 ) );

		return $ops;
	}

	/**
	 * @dataProvider invalidChangeOpProvider
	 * @expectedException InvalidArgumentException
	 *
	 * @param $invalidChangeOp
	 */
	public function testInvalidAdd( $invalidChangeOp ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $invalidChangeOp );
	}

	public function changeOpsProvider() {
		$validatorFactory = $this->getTermValidatorFactory();

		$args = array();

		$language = 'en';
		$changeOps = new ChangeOps();
		$changeOps->add( new ChangeOpLabel( $language, 'newLabel', $validatorFactory ) );
		$changeOps->add( new ChangeOpDescription( $language, 'newDescription', $validatorFactory ) );
		$args[] = array( $changeOps, $language, 'newLabel', 'newDescription' );

		return $args;
	}

	/**
	 * @dataProvider changeOpsProvider
	 *
	 * @param ChangeOps $changeOps
	 * @param string $language
	 * @param string $expectedLabel
	 * @param string $expectedDescription
	 */
	public function testApply( $changeOps, $language, $expectedLabel, $expectedDescription ) {
		$item = ItemContent::newEmpty();
		$entity = $item->getEntity();

		$changeOps->apply( $entity );
		$this->assertEquals( $expectedLabel, $entity->getLabel( $language ) );
		$this->assertEquals( $expectedDescription, $entity->getDescription( $language ) );
	}

	/**
	 * @expectedException \Wikibase\ChangeOp\ChangeOpException
	 */
	public function testInvalidApply() {
		$validatorFactory = $this->getTermValidatorFactory();

		$item = ItemContent::newEmpty();

		$changeOps = new ChangeOps();
		$changeOps->add( new ChangeOpLabel( 'en', 'newLabel', $validatorFactory ) );
		$changeOps->add( new ChangeOpAliases( 'en', array( 'test' ), 'invalidAction', $validatorFactory ) );

		$changeOps->apply( $item->getEntity() );
	}

}
