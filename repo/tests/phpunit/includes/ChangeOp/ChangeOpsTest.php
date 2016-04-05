<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpDescription;
use Wikibase\ChangeOp\ChangeOpLabel;
use Wikibase\ChangeOp\ChangeOpMainSnak;
use Wikibase\ChangeOp\ChangeOps;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\Validators\SnakValidator;

/**
 * @covers Wikibase\ChangeOp\ChangeOps
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Mättig
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

		$ops = [];
		$ops[] = array( new ChangeOpLabel( 'en', 'myNewLabel', $validatorFactory ) );
		$ops[] = array( new ChangeOpDescription( 'de', 'myNewDescription', $validatorFactory ) );
		$ops[] = array( new ChangeOpLabel( 'en', null, $validatorFactory ) );

		return $ops;
	}

	/**
	 * @dataProvider changeOpProvider
	 */
	public function testAdd( ChangeOp $changeOp ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $changeOp );
		$this->assertEquals( array( $changeOp ), $changeOps->getChangeOps() );
	}

	public function changeOpArrayProvider() {
		$validatorFactory = $this->getTermValidatorFactory();

		$ops = [];
		$ops[] = array(
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
	 */
	public function testAddArray( array $changeOpArray ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $changeOpArray );
		$this->assertEquals( $changeOpArray, $changeOps->getChangeOps() );
	}

	public function invalidChangeOpProvider() {
		$validatorFactory = $this->getTermValidatorFactory();

		$ops = [];
		$ops[] = array( 1234 );
		$ops[] = array( array( new ChangeOpLabel( 'en', 'test', $validatorFactory ), 123 ) );

		return $ops;
	}

	/**
	 * @dataProvider invalidChangeOpProvider
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidAdd( $invalidChangeOp ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $invalidChangeOp );
	}

	public function changeOpsProvider() {
		$validatorFactory = $this->getTermValidatorFactory();

		$args = [];

		$language = 'en';
		$changeOps = new ChangeOps();
		$changeOps->add( new ChangeOpLabel( $language, 'newLabel', $validatorFactory ) );
		$changeOps->add( new ChangeOpDescription( $language, 'newDescription', $validatorFactory ) );
		$args[] = array( $changeOps, $language, 'newLabel', 'newDescription' );

		return $args;
	}

	/**
	 * @dataProvider changeOpsProvider
	 */
	public function testApply( ChangeOps $changeOps, $language, $expectedLabel, $expectedDescription ) {
		$entity = new Item();

		$changeOps->apply( $entity );
		$this->assertEquals( $expectedLabel, $entity->getFingerprint()->getLabel( $language )->getText() );
		$this->assertEquals( $expectedDescription, $entity->getFingerprint()->getDescription( $language )->getText() );
	}

	public function testValidate() {
		$item = new Item();

		$guid = 'guid';
		$snak = new PropertyValueSnak( new PropertyId( 'P7' ), new StringValue( 'INVALID' ) );
		$guidGenerator = new GuidGenerator();

		$error = Error::newError( 'Testing', 'test', 'test-error', [] );
		$result = Result::newError( array( $error ) );

		$snakValidator = $this->getMockBuilder( SnakValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$snakValidator->expects( $this->any() )
			->method( 'validate' )
			->will( $this->returnValue( $result ) );

		$changeOps = new ChangeOps();
		$changeOps->add( new ChangeOpMainSnak( $guid, $snak, $guidGenerator, $snakValidator ) );

		$result = $changeOps->validate( $item );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

	public function testValidate_() {
		$item = new Item();

		$changeOp = $this->getMockBuilder( ChangeOp::class )
			->disableOriginalConstructor()
			->getMock();
		$changeOp->expects( $this->any() )
			->method( 'validate' )
			->will( $this->returnCallback( function( Item $item ) {
				// Fail when the label is already set (by a previous apply call).
				return $item->getFingerprint()->hasLabel( 'en' )
					? Result::newError( [] )
					: Result::newSuccess();
			} ) );
		$changeOp->expects( $this->any() )
			->method( 'apply' )
			->will( $this->returnCallback( function( Item $item ) {
				$item->setLabel( 'en', 'Label' );
			} ) );

		$changeOps = new ChangeOps();
		$changeOps->add( $changeOp );
		$changeOps->add( $changeOp );
		$result = $changeOps->validate( $item );

		$this->assertFalse( $result->isValid(), 'Validate must fail with this mock' );
		$this->assertTrue( $item->isEmpty(), 'Item must still be empty' );
	}

}
