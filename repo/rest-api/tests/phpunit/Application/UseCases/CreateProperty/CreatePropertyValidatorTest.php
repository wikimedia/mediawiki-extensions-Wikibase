<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\CreateProperty;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyRequest;
use Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\CreateProperty\CreatePropertyValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class CreatePropertyValidatorTest extends TestCase {

	private PropertyDeserializer $propertyDeserializer;
	private EditMetadataRequestValidatingDeserializer $editMetadataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyDeserializer = $this->createStub( PropertyDeserializer::class );
		$this->editMetadataValidator = $this->createStub( EditMetadataRequestValidatingDeserializer::class );
	}

	public function testGivenValidRequest_returnsDeserializedRequest(): void {
		$propertySerialization = [ 'data_type' => 'string' ];
		$request = new CreatePropertyRequest( $propertySerialization, [], false, null, null );

		$expectedProperty = $this->createStub( Property::class );
		$this->propertyDeserializer = $this->createMock( PropertyDeserializer::class );
		$this->propertyDeserializer->expects( $this->once() )
			->method( 'deserialize' )
			->with( $propertySerialization )
			->willReturn( $expectedProperty );

		$this->assertSame( $expectedProperty, $this->newValidator()->validateAndDeserialize( $request )->getProperty() );
	}

	/**
	 * @dataProvider invalidPropertyProvider
	 */
	public function testGivenInvalidPropertySerialization_throws( array $serialization, UseCaseError $expectedError ): void {
		$request = new CreatePropertyRequest( $serialization, [], false, null, null );

		try {
			$this->newValidator()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function invalidPropertyProvider(): Generator {
		yield 'missing data type' => [
			[],
			UseCaseError::newMissingField( '/property', 'data_type' ),
		];
		yield 'invalid data_type field type' => [
			[ 'data_type' => 123 ],
			UseCaseError::newInvalidValue( '/property/data_type' ),
		];
		yield 'invalid labels field type' => [
			[ 'data_type' => 'string', 'labels' => 'not an array' ],
			UseCaseError::newInvalidValue( '/property/labels' ),
		];
		yield 'invalid descriptions field type' => [
			[ 'data_type' => 'string', 'descriptions' => 'not an array' ],
			UseCaseError::newInvalidValue( '/property/descriptions' ),
		];
		yield 'invalid aliases field type' => [
			[ 'data_type' => 'string', 'aliases' => 'not an array' ],
			UseCaseError::newInvalidValue( '/property/aliases' ),
		];
		yield 'invalid statements field type' => [
			[ 'data_type' => 'string', 'statements' => 'not an array' ],
			UseCaseError::newInvalidValue( '/property/statements' ),
		];
	}

	public function testGivenInvalidEditMetadata_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );
		$request = new CreatePropertyRequest( [ 'data_type' => 'string' ], [ 'tag1', 'tag2' ], false, 'edit comment', 'SomeUser' );

		$this->editMetadataValidator = $this->createMock( EditMetadataRequestValidatingDeserializer::class );
		$this->editMetadataValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $request )
			->willThrowException( $expectedException );

		try {
			$this->newValidator()->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	private function newValidator(): CreatePropertyValidator {
		return new CreatePropertyValidator( $this->propertyDeserializer, $this->editMetadataValidator );
	}
}
