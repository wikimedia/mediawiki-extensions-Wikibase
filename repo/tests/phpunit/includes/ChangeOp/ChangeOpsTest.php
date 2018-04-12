<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\StringValue;
use InvalidArgumentException;
use PHPUnit4And6Compat;
use Prophecy\Argument;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDescription;
use Wikibase\Repo\ChangeOp\ChangeOpLabel;
use Wikibase\Repo\ChangeOp\ChangeOpMainSnak;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Summary;

/**
 * @covers Wikibase\Repo\ChangeOp\ChangeOps
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Kreuz
 */
class ChangeOpsTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

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
		$ops[] = [ new ChangeOpLabel( 'en', 'myNewLabel', $validatorFactory ) ];
		$ops[] = [ new ChangeOpDescription( 'de', 'myNewDescription', $validatorFactory ) ];
		$ops[] = [ new ChangeOpLabel( 'en', null, $validatorFactory ) ];

		return $ops;
	}

	/**
	 * @dataProvider changeOpProvider
	 */
	public function testAdd( ChangeOp $changeOp ) {
		$changeOps = new ChangeOps();
		$changeOps->add( $changeOp );
		$this->assertEquals( [ $changeOp ], $changeOps->getChangeOps() );
	}

	public function changeOpArrayProvider() {
		$validatorFactory = $this->getTermValidatorFactory();

		$ops = [];
		$ops[] = [
					[
						new ChangeOpLabel( 'en', 'enLabel', $validatorFactory ),
						new ChangeOpLabel( 'de', 'deLabel', $validatorFactory ),
						new ChangeOpDescription( 'en', 'enDescr', $validatorFactory ),
					]
				];

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
		$ops[] = [ 1234 ];
		$ops[] = [ [ new ChangeOpLabel( 'en', 'test', $validatorFactory ), 123 ] ];

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
		$args[] = [ $changeOps, $language, 'newLabel', 'newDescription' ];

		return $args;
	}

	/**
	 * @dataProvider changeOpsProvider
	 */
	public function testApply( ChangeOps $changeOps, $language, $expectedLabel, $expectedDescription ) {
		$entity = new Item();

		$changeOps->apply( $entity );
		$this->assertEquals( $expectedLabel, $entity->getLabels()->getByLanguage( $language )->getText() );
		$this->assertEquals( $expectedDescription, $entity->getDescriptions()->getByLanguage( $language )->getText() );
	}

	public function testValidate() {
		$item = new Item();

		$guid = 'guid';
		$snak = new PropertyValueSnak( new PropertyId( 'P7' ), new StringValue( 'INVALID' ) );
		$guidGenerator = new GuidGenerator();

		$error = Error::newError( 'Testing', 'test', 'test-error', [] );
		$result = Result::newError( [ $error ] );

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

		$changeOp = $this->getMock( ChangeOp::class );
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

	public function testGetActions() {
		$editChangeOp = $this->getMock( ChangeOp::class );
		$editChangeOp->method( 'getActions' )->willReturn( [ EntityPermissionChecker::ACTION_EDIT ] );

		$editTermsChangeOp = $this->getMock( ChangeOp::class );
		$editTermsChangeOp->method( 'getActions' )->willReturn( [ EntityPermissionChecker::ACTION_EDIT_TERMS ] );

		$changeOps = new ChangeOps( [ $editChangeOp, $editTermsChangeOp ] );

		$actions = $changeOps->getActions();
		$this->assertCount( 2, $actions );
		$this->assertContains( EntityPermissionChecker::ACTION_EDIT_TERMS, $actions );
		$this->assertContains( EntityPermissionChecker::ACTION_EDIT, $actions );
	}

	public function testApply_HasTwoChangeOps_DoesNotPassSummaryObject() {
		$changeOp1 = $this->prophesize( ChangeOp::class );
		$changeOp2 = $this->prophesize( ChangeOp::class );

		$changeOps = new ChangeOps( [ $changeOp1->reveal(), $changeOp2->reveal() ] );
		$changeOps->apply(
			$this->prophesize( EntityDocument::class )->reveal(),
			$this->prophesize( Summary::class )->reveal()
		);

		$changeOp1->apply( Argument::any(), null )->shouldHaveBeenCalled();
		$changeOp2->apply( Argument::any(), null )->shouldHaveBeenCalled();
	}

	public function testApply_HasOneChangeOp_PassesSummaryObject() {
		$changeOp = $this->prophesize( ChangeOp::class );

		$changeOps = new ChangeOps( [ $changeOp->reveal() ] );
		$changeOps->apply(
			$this->prophesize( EntityDocument::class )->reveal(),
			$this->prophesize( Summary::class )->reveal()
		);

		$changeOp->apply( Argument::any(), Argument::type( Summary::class ) )
			->shouldHaveBeenCalled();
	}

	public function testApply_HasTwoChangeOps_SetsGenericSummaryMessage() {
		$changeOps = new ChangeOps( [
			$this->prophesize( ChangeOp::class )->reveal(),
			$this->prophesize( ChangeOp::class )->reveal()
		] );

		$summary = $this->prophesize( Summary::class );
		$changeOps->apply(
			$this->prophesize( EntityDocument::class )->reveal(),
			$summary->reveal()
		);

		$summary->setAction( 'update' )->shouldHaveBeenCalled();
	}

	public function testApply_HasZeroChangeOps_DoesNotUpdateSummaryAction() {
		$changeOps = new ChangeOps( [] );

		$summary = $this->prophesize( Summary::class );
		$changeOps->apply(
			$this->prophesize( EntityDocument::class )->reveal(),
			$summary->reveal()
		);

		$summary->setAction( Argument::any() )->shouldNotHaveBeenCalled();
	}

}
