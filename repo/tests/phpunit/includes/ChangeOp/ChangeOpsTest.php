<?php

namespace Wikibase\Test;

use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpLabel;
use Wikibase\ChangeOp\ChangeOpDescription;
use Wikibase\ChangeOp\ChangeOpAliases;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\ItemContent;
use Wikibase\Validators\TermChangeValidationHelper;

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

	/**
	 * @return TermChangeValidationHelper
	 */
	private function getMockTermChangeValidationHelper() {
		$mock = $this->getMockBuilder( 'Wikibase\Validators\TermChangeValidationHelper' )
			->disableOriginalConstructor()
			->getMock();

		return $mock;
	}

	/**
	 * @return ChangeOp[]
	 */
	public function changeOpProvider() {
		$termValidation = $this->getMockTermChangeValidationHelper();

		$ops = array();
		$ops[] = array ( new ChangeOpLabel( 'en', 'myNewLabel', $termValidation ) );
		$ops[] = array ( new ChangeOpDescription( 'de', 'myNewDescription', $termValidation ) );
		$ops[] = array ( new ChangeOpLabel( 'en', null, $termValidation ) );

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
		$termValidation = $this->getMockTermChangeValidationHelper();

		$ops = array();
		$ops[] = array (
					array(
						new ChangeOpLabel( 'en', 'enLabel', $termValidation ),
						new ChangeOpLabel( 'de', 'deLabel', $termValidation ),
						new ChangeOpDescription( 'en', 'enDescr', $termValidation ),
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
		$termValidation = $this->getMockTermChangeValidationHelper();

		$ops = array();
		$ops[] = array ( 1234 );
		$ops[] = array ( array( new ChangeOpLabel( 'en', 'test', $termValidation ), 123 ) );

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
		$termValidation = $this->getMockTermChangeValidationHelper();

		$args = array();

		$language = 'en';
		$changeOps = new ChangeOps();
		$changeOps->add( new ChangeOpLabel( $language, 'newLabel', $termValidation ) );
		$changeOps->add( new ChangeOpDescription( $language, 'newDescription', $termValidation ) );
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
		$termValidation = $this->getMockTermChangeValidationHelper();

		$item = ItemContent::newEmpty();

		$changeOps = new ChangeOps();
		$changeOps->add( new ChangeOpLabel( 'en', 'newLabel', $termValidation ) );
		$changeOps->add( new ChangeOpAliases( 'en', array( 'test' ), 'invalidAction', $termValidation ) );

		$changeOps->apply( $item->getEntity() );
	}

}
