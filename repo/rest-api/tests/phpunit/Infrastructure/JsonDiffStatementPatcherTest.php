<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Swaggest\JsonDiff\JsonDiff;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedSerializationException;
use Wikibase\Repo\RestApi\Domain\Exceptions\InvalidPatchedStatementException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchPathException;
use Wikibase\Repo\RestApi\Domain\Exceptions\PatchTestConditionFailedException;
use Wikibase\Repo\RestApi\Infrastructure\JsonDiffStatementPatcher;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairDeserializer;
use Wikibase\Repo\RestApi\Serialization\PropertyValuePairSerializer;
use Wikibase\Repo\RestApi\Serialization\ReferenceDeserializer;
use Wikibase\Repo\RestApi\Serialization\ReferenceSerializer;
use Wikibase\Repo\RestApi\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Serialization\StatementSerializer;
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
	 * @var MockObject|PropertyDataTypeLookup
	 */
	private $propertyDataTypeLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyDataTypeLookup = $this->createStub( PropertyDataTypeLookup::class );
		$this->propertyDataTypeLookup->method( 'getDataTypeIdForProperty' )->willReturn( 'string' );

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
					'path' => '/value/content',
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
					'path' => '/value',
				],
			]
		);
	}

	public function testGivenPatchResultHasInvalidValue_throwsException(): void {
		$this->expectException( InvalidPatchedSerializationException::class );

		$this->newStatementPatcher()->patch(
			NewStatement::forProperty( 'P1' )->withValue( 'unpatched' )->build(),
			[
				[
					'op' => 'replace',
					'path' => '/value/content',
					// valid 'globecoordinate' value but invalid for a 'string' Property
					'value' => [ 'latitude' => 100, 'longitude' => 100 ]
				]
			]
		);
	}

	public function testGivenPatchTestConditionFailed_throwsException(): void {
		$testOperation = [
			'op' => 'test',
			'path' => '/value/type',
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

	public function testGivenDeserializedPatchResultIsInvalidStatement_throwsException(): void {
		$this->propertyDataTypeLookup = $this->createStub( PropertyDataTypeLookup::class );
		$this->propertyDataTypeLookup->method( 'getDataTypeIdForProperty' )->willReturn( 'url' );

		$this->expectException( InvalidPatchedStatementException::class );

		$this->newStatementPatcher()->patch(
			NewStatement::forProperty( 'P1' )
				->withValue( 'https://example.org' )
				->build(),
			[
				[
					'op' => 'replace',
					'path' => '/value/content',
					'value' => "valid 'string' datavalue but not a valid 'url' datatype"
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
		$propertyValuePairSerializer = new PropertyValuePairSerializer(
			$this->propertyDataTypeLookup
		);
		$statementSerializer = new StatementSerializer(
			$propertyValuePairSerializer,
			new ReferenceSerializer( $propertyValuePairSerializer )
		);

		$propertyValuePairDeserializer = new PropertyValuePairDeserializer(
			WikibaseRepo::getEntityIdParser(),
			$this->propertyDataTypeLookup,
			WikibaseRepo::getDataTypeDefinitions()->getValueTypes(),
			WikibaseRepo::getDataValueDeserializer()
		);
		$statementDeserializer = new StatementDeserializer(
			$propertyValuePairDeserializer,
			new ReferenceDeserializer( $propertyValuePairDeserializer )
		);

		return new JsonDiffStatementPatcher(
			$statementSerializer,
			$statementDeserializer,
			new SnakValidator(
				$this->propertyDataTypeLookup,
				WikibaseRepo::getDataTypeFactory(),
				WikibaseRepo::getDataTypeValidatorFactory()
			)
		);
	}

}
