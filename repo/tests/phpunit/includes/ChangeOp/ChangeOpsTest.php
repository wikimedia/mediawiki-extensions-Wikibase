<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\StringValue;
use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Summary;
use Wikibase\Repo\ChangeOp\ChangeOp;
use Wikibase\Repo\ChangeOp\ChangeOpDescription;
use Wikibase\Repo\ChangeOp\ChangeOpLabel;
use Wikibase\Repo\ChangeOp\ChangeOpMainSnak;
use Wikibase\Repo\ChangeOp\ChangeOps;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Validators\SnakValidator;

/**
 * @covers \Wikibase\Repo\ChangeOp\ChangeOps
 *
 * @group Wikibase
 * @group ChangeOp
 *
 * @license GPL-2.0-or-later
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Thiemo Kreuz
 */
class ChangeOpsTest extends \PHPUnit\Framework\TestCase {

	public function testEmptyChangeOps() {
		$changeOps = new ChangeOps();
		$this->assertSame( [], $changeOps->getChangeOps() );
	}

	private function getTermValidatorFactory() {
		$mockProvider = new ChangeOpTestMockProvider( $this );
		return $mockProvider->getMockTermValidatorFactory();
	}

	public static function changeOpProvider(): iterable {
		$ops = [];
		$ops[] = [ ChangeOpLabel::class, [ 'en', 'myNewLabel' ] ];
		$ops[] = [ ChangeOpDescription::class, [ 'de', 'myNewDescription' ] ];
		$ops[] = [ ChangeOpLabel::class, [ 'en', null ] ];

		return $ops;
	}

	/**
	 * @dataProvider changeOpProvider
	 */
	public function testAdd( string $changeOpClassName, array $changeOpParams ) {
		$changeOpParams[] = $this->getTermValidatorFactory();
		$changeOp = new $changeOpClassName( ...$changeOpParams );

		$changeOps = new ChangeOps();
		$changeOps->add( $changeOp );
		$this->assertEquals( [ $changeOp ], $changeOps->getChangeOps() );
	}

	public static function changeOpArrayProvider(): iterable {
		$ops = [];
		$ops[] = [
					[
						[ ChangeOpLabel::class, [ 'en', 'enLabel' ] ],
						[ ChangeOpLabel::class, [ 'de', 'deLabel' ] ],
						[ ChangeOpDescription::class, [ 'en', 'enDescr' ] ],
					],
				];

		return $ops;
	}

	/**
	 * @dataProvider changeOpArrayProvider
	 */
	public function testAddArray( array $changeOpParamsArray ) {
		$changeOpArray = array_map( function ( $changeOpParams ) {
			$changeOpParams[1][] = $this->getTermValidatorFactory();
			return new $changeOpParams[0]( ...$changeOpParams[1] );
		}, $changeOpParamsArray );

		$changeOps = new ChangeOps();
		$changeOps->add( $changeOpArray );
		$this->assertEquals( $changeOpArray, $changeOps->getChangeOps() );
	}

	public static function invalidChangeOpProvider() {
		yield [ fn () => 1234 ];
		yield [
			fn ( self $self ) => [
				new ChangeOpLabel( 'en', 'test', $self->getTermValidatorFactory() ),
				123,
			],
		];
	}

	/**
	 * @dataProvider invalidChangeOpProvider
	 */
	public function testInvalidAdd( $invalidChangeOpFactory ) {
		$invalidChangeOp = $invalidChangeOpFactory( $this );
		$changeOps = new ChangeOps();
		$this->expectException( InvalidArgumentException::class );
		$changeOps->add( $invalidChangeOp );
	}

	public static function changeOpsProvider() {
		$language = 'en';
		$newLabel = 'newLabel';
		$newDescription = 'newDescription';

		$changeOpsFactory = function ( self $self ) use ( $language, $newLabel, $newDescription ): ChangeOps {
			$validatorFactory = $self->getTermValidatorFactory();
			$changeOps = new ChangeOps();
			$changeOps->add( new ChangeOpLabel( $language, $newLabel, $validatorFactory ) );
			$changeOps->add( new ChangeOpDescription( $language, $newDescription, $validatorFactory ) );

			return $changeOps;
		};

		yield [ $changeOpsFactory, $language, $newLabel, $newDescription ];
	}

	/**
	 * @dataProvider changeOpsProvider
	 */
	public function testApply( callable $changeOpsFactory, $language, $expectedLabel, $expectedDescription ) {
		$changeOps = $changeOpsFactory( $this );
		$entity = new Item();

		$changeOps->apply( $entity );
		$this->assertEquals( $expectedLabel, $entity->getLabels()->getByLanguage( $language )->getText() );
		$this->assertEquals( $expectedDescription, $entity->getDescriptions()->getByLanguage( $language )->getText() );
	}

	public function testValidate() {
		$item = new Item();

		$guid = 'guid';
		$snak = new PropertyValueSnak( new NumericPropertyId( 'P7' ), new StringValue( 'INVALID' ) );
		$guidGenerator = new GuidGenerator();

		$error = Error::newError( 'Testing', 'test', 'test-error', [] );
		$result = Result::newError( [ $error ] );

		$snakValidator = $this->createMock( SnakValidator::class );

		$snakValidator->method( 'validate' )
			->willReturn( $result );

		$changeOps = new ChangeOps();
		$changeOps->add( new ChangeOpMainSnak( $guid, $snak, $guidGenerator, $snakValidator ) );

		$result = $changeOps->validate( $item );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

	public function testApplyReturnsCorrectChangeOpResult() {
		$validatorFactory = $this->getTermValidatorFactory();

		$item = new Item();
		$changeOps = new ChangeOps();
		$changeOpsArray = [
			new ChangeOpLabel( 'en', 'newLabel', $validatorFactory ),
			new ChangeOpDescription( 'en', 'newDescription', $validatorFactory ),
		];
		$changeOps->add( $changeOpsArray );
		$changeOpsResult = $changeOps->apply( $item->copy() );

		$changeOpsResultsArray = [
			$changeOpsArray[0]->apply( $item->copy() ),
			$changeOpsArray[1]->apply( $item->copy() ),
		];

		$actualChangeOpsResult = [
			$changeOpsResult->isEntityChanged(),
			$changeOpsResult->getChangeOpsResults(),
			$changeOpsResult->getEntityId(),
		];
		$expectedChangeOpsResult = [ true, $changeOpsResultsArray, $item->getId() ];

		$this->assertEquals( $expectedChangeOpsResult, $actualChangeOpsResult );
	}

	public function testValidate_() {
		$item = new Item();

		$changeOp = $this->createMock( ChangeOp::class );
		$changeOp->method( 'validate' )
			->willReturnCallback( function( Item $item ) {
				// Fail when the label is already set (by a previous apply call).
				return $item->getFingerprint()->hasLabel( 'en' )
					? Result::newError( [] )
					: Result::newSuccess();
			} );
		$changeOp->method( 'apply' )
			->willReturnCallback( function( Item $item ) {
				$item->setLabel( 'en', 'Label' );
			} );

		$changeOps = new ChangeOps();
		$changeOps->add( $changeOp );
		$changeOps->add( $changeOp );
		$result = $changeOps->validate( $item );

		$this->assertFalse( $result->isValid(), 'Validate must fail with this mock' );
		$this->assertTrue( $item->isEmpty(), 'Item must still be empty' );
	}

	public function testGetActions() {
		$editChangeOp = $this->createMock( ChangeOp::class );
		$editChangeOp->method( 'getActions' )->willReturn( [ EntityPermissionChecker::ACTION_EDIT ] );

		$editTermsChangeOp = $this->createMock( ChangeOp::class );
		$editTermsChangeOp->method( 'getActions' )->willReturn( [ EntityPermissionChecker::ACTION_EDIT_TERMS ] );

		$changeOps = new ChangeOps( [ $editChangeOp, $editTermsChangeOp ] );

		$actions = $changeOps->getActions();
		$this->assertCount( 2, $actions );
		$this->assertContains( EntityPermissionChecker::ACTION_EDIT_TERMS, $actions );
		$this->assertContains( EntityPermissionChecker::ACTION_EDIT, $actions );
	}

	public function testApply_HasTwoChangeOps_DoesNotPassSummaryObject() {
		$changeOp1 = $this->createMock( ChangeOp::class );
		$changeOp2 = $this->createMock( ChangeOp::class );

		$changeOps = new ChangeOps( [ $changeOp1, $changeOp2 ] );

		$changeOp1->expects( $this->once() )->method( 'apply' )->with( $this->anything(), null );
		$changeOp2->expects( $this->once() )->method( 'apply' )->with( $this->anything(), null );

		$changeOps->apply(
			$this->createMock( EntityDocument::class ),
			$this->createMock( Summary::class )
		);
	}

	public function testApply_HasOneChangeOp_PassesSummaryObject() {
		$changeOp = $this->createMock( ChangeOp::class );

		$changeOps = new ChangeOps( [ $changeOp ] );

		$changeOp->expects( $this->once() )
			->method( 'apply' )
			->with( $this->anything(), $this->isInstanceOf( Summary::class ) );

		$changeOps->apply(
			$this->createMock( EntityDocument::class ),
			$this->createMock( Summary::class )
		);
	}

	public function testApply_HasTwoChangeOps_SetsGenericSummaryMessage() {
		$changeOps = new ChangeOps( [
			$this->createMock( ChangeOp::class ),
			$this->createMock( ChangeOp::class ),
		] );

		$summary = $this->createMock( Summary::class );

		$summary->expects( $this->once() )->method( 'setAction' )->with( 'update' );
		$changeOps->apply(
			$this->createMock( EntityDocument::class ),
			$summary
		);
	}

	public function testApply_HasZeroChangeOps_DoesNotUpdateSummaryAction() {
		$changeOps = new ChangeOps( [] );

		$summary = $this->createMock( Summary::class );
		$changeOps->apply(
			$this->createMock( EntityDocument::class ),
			$summary
		);

		$summary->expects( $this->never() )->method( 'setAction' );
	}

}
