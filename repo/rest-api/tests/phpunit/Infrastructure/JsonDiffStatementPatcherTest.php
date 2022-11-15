<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use Exception;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swaggest\JsonDiff\JsonDiff;
use ValueValidators\Error;
use ValueValidators\Result;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedSerializationException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedStatementException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedStatementValueTypeException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Domain\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffStatementPatcher;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\JsonDiffStatementPatcher
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class JsonDiffStatementPatcherTest extends TestCase {

	/**
	 * @var MockObject|SnakValidator
	 */
	private $snakValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->snakValidator = $this->createStub( SnakValidator::class );
		$this->snakValidator->method( 'validate' )->willReturn( Result::newSuccess() );

		if ( !class_exists( JsonDiff::class ) ) {
			$this->markTestSkipped( 'Skipping while swaggest/json-diff has not made it to mediawiki/vendor yet (T316245).' );
		}
	}

	/**
	 * @dataProvider patchStatementProvider
	 */
	public function testPatchStatement( Statement $originalStatement, array $patch, Statement $patchedStatement ): void {
		$result = $this->newStatementPatcher()->patch( $originalStatement, $patch );

		$this->assertTrue( $result->equals( $patchedStatement ) );
	}

	public function patchStatementProvider(): Generator {
		yield 'change string value to "patched"' => [
			NewStatement::forProperty( 'P1' )
				->withValue( 'unpatched' )
				->build(),
			[
				[
					'op' => 'replace',
					'path' => '/mainsnak/datavalue/value',
					'value' => 'patched',
				],
			],
			NewStatement::forProperty( 'P1' )
				->withValue( 'patched' )
				->build(),
		];

		yield 'remove qualifier' => [
			NewStatement::noValueFor( 'P1' )
				->withQualifier( 'P2', 'abc' )
				->build(),
			[
				[
					'op' => 'remove',
					'path' => '/qualifiers',
				],
			],
			NewStatement::noValueFor( 'P1' )
				->build(),
		];
	}

	/**
	 * @dataProvider invalidPatchProvider
	 */
	public function testGivenInvalidPatch_throwsException( array $patch ): void {
		$this->expectException( InvalidArgumentException::class );

		$this->newStatementPatcher()->patch(
			NewStatement::noValueFor( 'P123' )->build(),
			$patch
		);
	}

	public function testGivenPatchResultIsInvalidStatementSerialization_throwsException(): void {
		$this->expectException( InvalidPatchedSerializationException::class );

		$this->newStatementPatcher()->patch(
			NewStatement::forProperty( 'P1' )
				->withValue( 'abc' )
				->build(),
			[
				[
					'op' => 'remove',
					'path' => '/mainsnak',
				],
			]
		);
	}

	public function testGivenPatchResultChangesValueType_throwsException(): void {
		$this->snakValidator = $this->createStub( SnakValidator::class );
		$this->snakValidator
			->method( 'validate' )
			->willReturn( Result::newError( [ Error::newError( '', null, 'bad-value-type' ), ] ) );

		$propertyId = 'P1';
		$originalStatement = NewStatement::forProperty( $propertyId )
			->withValue( 'unpatched' )
			->build();
		$patchOperation = [
			'op' => 'replace',
			'path' => '/mainsnak/datavalue',
			'value' => [
				'type' => 'globecoordinate',
				'value' => [ 'latitude' => 100, 'longitude' => 100 ]
			]
		];
		$expectedPatchedStatement = NewStatement::forProperty( $propertyId )
			->withValue( new GlobeCoordinateValue( new LatLongValue( 100, 100 ) ) )
			->build();

		try {
			$this->newStatementPatcher()->patch( $originalStatement, [ $patchOperation ] );
			$this->fail( "Exception not thrown" );
		} catch ( Exception $e ) {
			$this->assertInstanceOf( InvalidPatchedStatementValueTypeException::class, $e );
			$this->assertEquals( $propertyId, $e->getPropertyId()->getSerialization() );
			$this->assertEquals( $expectedPatchedStatement, $e->getPatchedStatement() );
		}
	}

	public function testGivenPatchTestConditionFailed_throwsException(): void {
		$testOperation = [
			'op' => 'test',
			'path' => '/mainsnak/snaktype',
			'value' => 'value',
		];

		try {
			$this->newStatementPatcher()->patch(
				NewStatement::noValueFor( 'P1' )->build(),
				[ $testOperation ]
			);
		} catch ( PatchTestConditionFailedException $exception ) {
			$this->assertEquals( $testOperation, $exception->getOperation() );
			$this->assertEquals( 'novalue', $exception->getActualValue() );
		}
	}

	public function testGivenPatchCannotBeApplied_throwsException(): void {
		$problematicPathField = 'path';
		$operation = [
			'op' => 'remove',
			'path' => '/field/does/not/exist',
		];

		try {
			$this->newStatementPatcher()->patch(
				NewStatement::noValueFor( 'P1' )->build(),
				[ $operation ]
			);
		} catch ( \Exception $exception ) {
			$this->assertInstanceOf( PatchPathException::class, $exception );
			$this->assertSame( $problematicPathField, $exception->getField() );
			$this->assertSame( $operation, $exception->getOperation() );
		}
	}

	public function testGivenPatchResultsIsInvalidStatement_throwsException(): void {
		$this->snakValidator = $this->createMock( SnakValidator::class );
		$this->snakValidator
			->method( 'validate' )
			->willReturn( Result::newError( [ Error::newError() ] ) );

		$this->expectException( InvalidPatchedStatementException::class );

		$this->newStatementPatcher()->patch(
			NewStatement::forProperty( 'P1' )
				->withValue( 'abc' )
				->build(),
			[
				[
					'op' => 'replace',
					'path' => '/mainsnak/datavalue/type',
					'value' => 'wikibase-entityid'
				]
			]
		);
	}

	public function invalidPatchProvider(): Generator {
		yield 'patch operation is not an array' => [
			[ 'potato' ],
		];
		yield 'invalid patch op type' => [
			[
				'op' => 'boil',
				'path' => '/potato',
			],
		];
	}

	private function newStatementPatcher(): JsonDiffStatementPatcher {
		return new JsonDiffStatementPatcher(
			WikibaseRepo::getBaseDataModelSerializerFactory()
				->newStatementSerializer(),
			new StatementDeserializer(
				WikibaseRepo::getBaseDataModelDeserializerFactory()->newStatementDeserializer()
			),
			$this->snakValidator
		);
	}

}
