<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemDescriptionEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemLabelEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\LanguageCodeRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\MappedRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PatchRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PatchRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyDescriptionEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyLabelEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\StatementSerializationRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\UseCaseRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Model\UserProvidedEditMetadata;
use Wikibase\Repo\RestApi\Infrastructure\ValidatingRequestDeserializer;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializerServiceContainer;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\ValidatingRequestDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ValidatingRequestDeserializerTest extends TestCase {

	private const VALID_LANGUAGE_CODE = 'en';
	private const EXISTING_PROPERTY = 'P123';

	public function testGivenValidItemIdRequest_returnsDeserializedItemId(): void {
		$request = $this->createStub( ItemIdUseCaseRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals( new ItemId( 'Q123' ), $result->getItemId() );
	}

	public function testGivenValidPropertyIdRequest_returnsDeserializedPropertyId(): void {
		$request = $this->createStub( PropertyIdUseCaseRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals( new NumericPropertyId( 'P123' ), $result->getPropertyId() );
	}

	public function testGivenValidLanguageCodeRequest_returnsLanguageCode(): void {
		$request = $this->createStub( LanguageCodeUseCaseRequest::class );
		$request->method( 'getLanguageCode' )->willReturn( self::VALID_LANGUAGE_CODE );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals( self::VALID_LANGUAGE_CODE, $result->getLanguageCode() );
	}

	public function testGivenValidStatementIdRequest_returnsDeserializedStatementId(): void {
		$statementId = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$request = $this->createStub( StatementIdUseCaseRequest::class );
		$request->method( 'getStatementId' )->willReturn( "$statementId" );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals( $statementId, $result->getStatementId() );
	}

	public function testGivenValidPropertyIdFilterRequest_returnsDeserializedPropertyId(): void {
		$request = $this->createStub( PropertyIdFilterUseCaseRequest::class );
		$request->method( 'getPropertyIdFilter' )->willReturn( 'P123' );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals( new NumericPropertyId( 'P123' ), $result->getPropertyIdFilter() );
	}

	public function testGivenValidItemFieldsRequest_returnsDeserializedItemFields(): void {
		$fields = [ 'labels', 'descriptions' ];
		$request = $this->createStub( ItemFieldsUseCaseRequest::class );
		$request->method( 'getItemFields' )->willReturn( $fields );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals( $fields, $result->getItemFields() );
	}

	public function testGivenValidPropertyFieldsRequest_returnsDeserializedPropertyFields(): void {
		$fields = [ 'labels', 'descriptions' ];
		$request = $this->createStub( PropertyFieldsUseCaseRequest::class );
		$request->method( 'getPropertyFields' )->willReturn( $fields );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals( $fields, $result->getPropertyFields() );
	}

	public function testGivenValidStatementSerializationRequest_returnsStatement(): void {
		$request = $this->createStub( StatementSerializationUseCaseRequest::class );
		$request->method( 'getStatement' )->willReturn( [
			'property' => [ 'id' => self::EXISTING_PROPERTY ],
			'value' => [ 'type' => 'novalue' ],
		] );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals( NewStatement::noValueFor( 'P123' )->build(), $result->getStatement() );
	}

	public function testGivenValidEditMetadataRequest_returnsEditMetadata(): void {
		$user = 'potato';
		$isBot = false;
		$editTags = [ 'allowed' ];
		$comment = 'edit comment';
		$request = $this->createStub( EditMetadataUseCaseRequest::class );
		$request->method( 'getUsername' )->willReturn( $user );
		$request->method( 'isBot' )->willReturn( $isBot );
		$request->method( 'getComment' )->willReturn( $comment );
		$request->method( 'getEditTags' )->willReturn( $editTags );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals(
			new UserProvidedEditMetadata( User::withUsername( $user ), $isBot, $comment, $editTags ),
			$result->getEditMetadata()
		);
	}

	public function testGivenValidPatchRequest_returnsPatch(): void {
		$patch = [ [ 'op' => 'test', 'path' => '/some/path', 'value' => 'abc' ] ];
		$request = $this->createStub( PatchUseCaseRequest::class );
		$request->method( 'getPatch' )->willReturn( $patch );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals( $patch, $result->getPatch() );
	}

	public function testGivenValidItemLabelEditRequest_returnsLabel(): void {
		$request = $this->createStub( ItemLabelEditUseCaseRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getLabel' )->willReturn( 'potato' );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals( new Term( 'en', 'potato' ), $result->getItemLabel() );
	}

	public function testGivenValidPropertyLabelEditRequest_returnsLabel(): void {
		$request = $this->createStub( PropertyLabelEditUseCaseRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getLabel' )->willReturn( 'instance of' );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );
		$this->assertEquals( new Term( 'en', 'instance of' ), $result->getPropertyLabel() );
	}

	public function testGivenValidItemDescriptionEditRequest_returnsDescription(): void {
		$request = $this->createStub( ItemDescriptionEditUseCaseRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'root vegetable' );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );

		$this->assertEquals( new Term( 'en', 'root vegetable' ), $result->getItemDescription() );
	}

	public function testGivenValidPropertyDescriptionEditRequest_returnsDescription(): void {
		$request = $this->createStub( PropertyDescriptionEditUseCaseRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'that class of which this subject is a particular example and member' );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );

		$this->assertEquals(
			new Term( 'en', 'that class of which this subject is a particular example and member' ),
			$result->getPropertyDescription()
		);
	}

	public function testGivenRepeatedValidRequests_returnsTheSameResultAndValidatesOnlyOnce(): void {
		$stubStatementSerialization = [ 'statement' => 'serialization' ];
		$request = $this->createStub( StatementSerializationUseCaseRequest::class );
		$request->method( 'getStatement' )->willReturn( $stubStatementSerialization );

		$statementValidator = $this->createMock( StatementSerializationRequestValidatingDeserializer::class );
		$statementValidator->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $request )
			->willReturnCallback( fn() => $this->createStub( Statement::class ) );

		$serviceContainer = $this->createMock( ContainerInterface::class );
		$serviceContainer->expects( $this->once() )
			->method( 'get' )
			->with( ValidatingRequestDeserializer::STATEMENT_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER )
			->willReturn( $statementValidator );

		$validatingDeserializer = new ValidatingRequestDeserializer( $serviceContainer );

		$this->assertSame(
			$validatingDeserializer->validateAndDeserialize( $request ),
			$validatingDeserializer->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider invalidRequestProvider
	 */
	public function testGivenInvalidRequest_throws( string $requestClass, string $validatorClass, string $stubbedServiceName ): void {
		$expectedError = $this->createStub( UseCaseError::class );
		$validator = $this->createStub( $validatorClass );
		$validator->method( 'validateAndDeserialize' )->willThrowException( $expectedError );
		$serviceContainer = $this->createMock( ContainerInterface::class );
		$serviceContainer->expects( $this->atLeastOnce() )
			->method( 'get' )
			->willReturnCallback(
				fn( string $serviceName ) => $serviceName === $stubbedServiceName ? $validator : new NullValidator()
			);

		$request = $this->createStub( $requestClass );

		try {
			$this->newRequestDeserializer( $serviceContainer )->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function invalidRequestProvider(): Generator {
		yield [
			ItemIdUseCaseRequest::class,
			ItemIdRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::ITEM_ID_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			PropertyIdUseCaseRequest::class,
			MappedRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::PROPERTY_ID_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			StatementIdUseCaseRequest::class,
			StatementIdRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::STATEMENT_ID_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			PropertyIdFilterUseCaseRequest::class,
			MappedRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::PROPERTY_ID_FILTER_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			LanguageCodeUseCaseRequest::class,
			LanguageCodeRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::LANGUAGE_CODE_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			ItemFieldsUseCaseRequest::class,
			MappedRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::ITEM_FIELDS_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			PropertyFieldsUseCaseRequest::class,
			MappedRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::PROPERTY_FIELDS_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			StatementSerializationUseCaseRequest::class,
			StatementSerializationRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::STATEMENT_SERIALIZATION_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			EditMetadataUseCaseRequest::class,
			EditMetadataRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::EDIT_METADATA_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			PatchUseCaseRequest::class,
			PatchRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::PATCH_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			ItemLabelEditUseCaseRequest::class,
			ItemLabelEditRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::ITEM_LABEL_EDIT_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			PropertyLabelEditUseCaseRequest::class,
			PropertyLabelEditRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::PROPERTY_LABEL_EDIT_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			ItemDescriptionEditUseCaseRequest::class,
			ItemDescriptionEditRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::ITEM_DESCRIPTION_EDIT_REQUEST_VALIDATING_DESERIALIZER,
		];
		yield [
			PropertyDescriptionEditUseCaseRequest::class,
			PropertyDescriptionEditRequestValidatingDeserializer::class,
			ValidatingRequestDeserializer::PROPERTY_DESCRIPTION_EDIT_REQUEST_VALIDATING_DESERIALIZER,
		];
	}

	private function newRequestDeserializer( ContainerInterface $serviceContainer = null ): ValidatingRequestDeserializer {
		return new ValidatingRequestDeserializer(
			$serviceContainer ?? new TestValidatingRequestDeserializerServiceContainer()
		);
	}

}

// @codingStandardsIgnoreStart Various rules are unhappy about these interface one-liners, but there isn't much that can go wrong...
// We're creating some combined interfaces here because PHPUnit 9 does not support stubbing multiple interfaces
interface ItemIdUseCaseRequest extends UseCaseRequest, ItemIdRequest {}
interface PropertyIdUseCaseRequest extends UseCaseRequest, PropertyIdRequest {}
interface StatementIdUseCaseRequest extends UseCaseRequest, StatementIdRequest {}
interface PropertyIdFilterUseCaseRequest extends UseCaseRequest, PropertyIdFilterRequest {}
interface LanguageCodeUseCaseRequest extends UseCaseRequest, LanguageCodeRequest {}
interface ItemFieldsUseCaseRequest extends UseCaseRequest, ItemFieldsRequest {}
interface StatementSerializationUseCaseRequest extends UseCaseRequest, StatementSerializationRequest {}
interface EditMetadataUseCaseRequest extends UseCaseRequest, EditMetadataRequest {}
interface PatchUseCaseRequest extends UseCaseRequest, PatchRequest {}
interface ItemLabelEditUseCaseRequest extends UseCaseRequest, ItemLabelEditRequest {}
interface PropertyLabelEditUseCaseRequest extends UseCaseRequest, PropertyLabelEditRequest {}
interface ItemDescriptionEditUseCaseRequest extends UseCaseRequest, ItemDescriptionEditRequest {}
interface PropertyDescriptionEditUseCaseRequest extends UseCaseRequest, PropertyDescriptionEditRequest {}
interface PropertyFieldsUseCaseRequest extends UseCaseRequest, PropertyFieldsRequest {}
class NullValidator {
	public function validateAndDeserialize() {
		return null;
	}
}
// @codingStandardsIgnoreEnd
