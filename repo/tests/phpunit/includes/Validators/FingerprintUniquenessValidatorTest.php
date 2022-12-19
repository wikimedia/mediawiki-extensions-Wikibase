<?php

namespace Wikibase\Repo\Tests\Validators;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Repo\ChangeOp\ChangeOpDescriptionResult;
use Wikibase\Repo\ChangeOp\ChangeOpFingerprintResult;
use Wikibase\Repo\ChangeOp\ChangeOpLabelResult;
use Wikibase\Repo\ChangeOp\ChangeOpsResult;
use Wikibase\Repo\ChangeOp\DummyChangeOpResult;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\FingerprintUniquenessValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Validators\UniquenessViolation;

/**
 * @covers \Wikibase\Repo\Validators\FingerprintUniquenessValidator
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0-or-later
 */
class FingerprintUniquenessValidatorTest extends TestCase {

	/** @var TermsCollisionDetector */
	private $termsCollisionDetector;

	/** @var TermLookup */
	private $termLookup;

	protected function setUp(): void {
		$this->termsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$this->termLookup = $this->createMock( TermLookup::class );
	}

	public function testSubject_givenUnsupportedValueType_throws() {
		$this->expectException( InvalidArgumentException::class );
		$this->getSubjectResult( new DummyChangeOpResult() );
	}

	public function itemUniquenessValidationProvider() {
		$entityId = new ItemId( 'Q123' );
		$collidingEntityId = new ItemId( 'Q321' );

		return [
			'label and description in ChangeOpResult - no collisions detected' => [
				'getLabelMock' => null,
				'getDescriptionMock' => null,
				'detectLabelAndDescriptionCollisionMock' => function ( $lang, $label, $description ) {
					$this->assertEquals( 'en', $lang );
					$this->assertEquals( 'new label', $label );
					$this->assertEquals( 'old description', $description );
					return null;
				},
				'valueToValidate' => new ChangeOpFingerprintResult(
					new ChangeOpsResult( $entityId, [
						new ChangeOpLabelResult( $entityId, 'en', '', 'new label', true ),
						new ChangeOpDescriptionResult( $entityId, 'en', 'old description', '', false ),
					] ),
					$this->createMock( TermValidatorFactory::class )
				),
				'expectedResult' => Result::newSuccess(),
			],

			'old label not in ChangeOpResult - no collisions detected' => [
				'getLabelMock' => function () {
					return 'old label';
				},
				'getDescriptionMock' => null,
				'detectLabelAndDescriptionCollisionMock' => function ( $lang, $label, $description ) {
					$this->assertEquals( 'en', $lang );
					$this->assertEquals( 'old label', $label );
					$this->assertEquals( 'new description', $description );
					return null;
				},
				'valueToValidate' => new ChangeOpFingerprintResult(
					new ChangeOpsResult( $entityId, [
						new ChangeOpDescriptionResult( $entityId, 'en', '', 'new description', true ),
					] ),
					$this->createMock( TermValidatorFactory::class )
				),
				'expectedResult' => Result::newSuccess(),
			],

			'old description not in ChangeOpResult - no collisions detected' => [
				'getLabelMock' => null,
				'getDescriptionMock' => function () {
					return 'old description';
				},
				'detectLabelAndDescriptionCollisionMock' => function ( $lang, $label, $description ) {
					$this->assertEquals( 'en', $lang );
					$this->assertEquals( 'new label', $label );
					$this->assertEquals( 'old description', $description );
					return null;
				},
				'valueToValidate' => new ChangeOpFingerprintResult(
					new ChangeOpsResult( $entityId, [
						new ChangeOpLabelResult( $entityId, 'en', '', 'new label', true ),
					] ),
					$this->createMock( TermValidatorFactory::class )
				),
				'expectedResult' => Result::newSuccess(),
			],

			'collision detected' => [
				'getLabelMock' => null,
				'getDescriptionMock' => null,
				'detectLabelAndDescriptionCollisionMock' => function ( $lang, $label, $description ) use ( $collidingEntityId ) {
					$this->assertEquals( 'en', $lang );
					$this->assertEquals( 'new label', $label );
					$this->assertEquals( 'old description', $description );
					return $collidingEntityId;
				},
				'valueToValidate' => new ChangeOpFingerprintResult(
					new ChangeOpsResult( $entityId, [
						new ChangeOpLabelResult( $entityId, 'en', '', 'new label', true ),
						new ChangeOpDescriptionResult( $entityId, 'en', 'old description', '', false ),
					] ),
					$this->createMock( TermValidatorFactory::class )
				),
				'expectedResult' => Result::newError( [
					new UniquenessViolation(
						$collidingEntityId,
						'found conflicting terms',
						'label-with-description-conflict',
						[
							'new label',
							'en',
							$collidingEntityId,
						]
					),
				] ),
			],
		];
	}

	/**
	 * @dataProvider itemUniquenessValidationProvider
	 */
	public function testSubject_givenItemEntityType(
		?callable $getLabelMock,
		?callable $getDescriptionMock,
		?callable $detectLabelAndDescriptionCollisionMock,
		$valueToValidate,
		Result $expectedResult
	) {
		if ( $getLabelMock ) {
			$this->termLookup->method( 'getLabel' )->willReturnCallback( $getLabelMock );
		}
		if ( $getDescriptionMock ) {
			$this->termLookup->method( 'getDescription' )->willReturnCallback( $getDescriptionMock );
		}
		if ( $detectLabelAndDescriptionCollisionMock ) {
			$this->termsCollisionDetector->method( 'detectLabelAndDescriptionCollision' )
				->willReturnCallback( $detectLabelAndDescriptionCollisionMock );
		}

		$actualResult = $this->getSubjectResult( $valueToValidate );

		$this->assertEquals( $expectedResult, $actualResult );
	}

	private function getSubjectResult( $valueToValidate ) {
		$validator = new FingerprintUniquenessValidator(
			$this->termsCollisionDetector,
			$this->termLookup
		);

		return $validator->validate( $valueToValidate );
	}

}
