<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Application\UseCases\EditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemDescriptionEditRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ItemLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCases\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ItemDescriptionEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ItemIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ItemLabelEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\LanguageCodeRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\MappedRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\PatchRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\StatementIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\StatementSerializationRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestFieldDeserializerFactory;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\StatementSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Domain\Model\UserProvidedEditMetadata;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer
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

		$this->assertEquals(
			[ ItemIdRequest::class => new ItemId( 'Q123' ) ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidPropertyIdRequest_returnsDeserializedPropertyId(): void {
		$request = $this->createStub( PropertyIdUseCaseRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );

		$this->assertEquals(
			[ PropertyIdRequest::class => new NumericPropertyId( 'P123' ) ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidLanguageCodeRequest_returnsLanguageCode(): void {
		$request = $this->createStub( LanguageCodeUseCaseRequest::class );
		$request->method( 'getLanguageCode' )->willReturn( self::VALID_LANGUAGE_CODE );

		$this->assertEquals(
			[ LanguageCodeRequest::class => self::VALID_LANGUAGE_CODE ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidStatementIdRequest_returnsDeserializedStatementId(): void {
		$statementId = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$request = $this->createStub( StatementIdUseCaseRequest::class );
		$request->method( 'getStatementId' )->willReturn( "$statementId" );

		$this->assertEquals(
			[ StatementIdRequest::class => $statementId ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidPropertyIdFilterRequest_returnsDeserializedPropertyId(): void {
		$request = $this->createStub( PropertyIdFilterUseCaseRequest::class );
		$request->method( 'getPropertyIdFilter' )->willReturn( 'P123' );

		$this->assertEquals(
			[ PropertyIdFilterRequest::class => new NumericPropertyId( 'P123' ) ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidItemFieldsRequest_returnsDeserializedItemFields(): void {
		$fields = [ 'labels', 'descriptions' ];
		$request = $this->createStub( ItemFieldsUseCaseRequest::class );
		$request->method( 'getItemFields' )->willReturn( $fields );

		$this->assertEquals(
			[ ItemFieldsRequest::class => $fields ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidStatementSerializationRequest_returnsStatement(): void {
		$request = $this->createStub( StatementSerializationUseCaseRequest::class );
		$request->method( 'getStatement' )->willReturn( [
			'property' => [ 'id' => self::EXISTING_PROPERTY ],
			'value' => [ 'type' => 'novalue' ],
		] );

		$this->assertEquals(
			[ StatementSerializationRequest::class => NewStatement::noValueFor( 'P123' )->build() ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
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

		$this->assertEquals(
			[ EditMetadataRequest::class => new UserProvidedEditMetadata( User::withUsername( $user ), $isBot, $comment, $editTags ) ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidPatchRequest_returnsPatch(): void {
		$patch = [ [ 'op' => 'test', 'path' => '/some/path', 'value' => 'abc' ] ];
		$request = $this->createStub( PatchUseCaseRequest::class );
		$request->method( 'getPatch' )->willReturn( $patch );

		$this->assertEquals(
			[ PatchRequest::class => $patch ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidItemLabelEditRequest_returnsLabel(): void {
		$request = $this->createStub( ItemLabelEditUseCaseRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getLabel' )->willReturn( 'potato' );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );

		$this->assertArrayHasKey( ItemLabelEditRequest::class, $result );
		$this->assertEquals( $result[ItemLabelEditRequest::class], new Term( 'en', 'potato' ) );
	}

	public function testGivenValidItemDescriptionEditRequest_returnsDescription(): void {
		$request = $this->createStub( ItemDescriptionEditUseCaseRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getDescription' )->willReturn( 'root vegetable' );

		$result = $this->newRequestDeserializer()->validateAndDeserialize( $request );

		$this->assertArrayHasKey( ItemDescriptionEditRequest::class, $result );
		$this->assertEquals( $result[ItemDescriptionEditRequest::class], new Term( 'en', 'root vegetable' ) );
	}

	/**
	 * @dataProvider invalidRequestProvider
	 */
	public function testGivenInvalidRequest_throws( string $requestClass, string $validatorClass, string $factoryMethod ): void {
		$expectedError = $this->createStub( UseCaseError::class );
		$validator = $this->createStub( $validatorClass );
		$validator->method( 'validateAndDeserialize' )->willThrowException( $expectedError );
		$factory = $this->createStub( ValidatingRequestFieldDeserializerFactory::class );
		$factory->method( $factoryMethod )->willReturn( $validator );

		$request = $this->createStub( $requestClass );

		try {
			$this->newRequestDeserializer( $factory )->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function invalidRequestProvider(): Generator {
		yield [
			ItemIdUseCaseRequest::class,
			ItemIdRequestValidatingDeserializer::class,
			'newItemIdRequestValidatingDeserializer',
		];
		yield [
			PropertyIdUseCaseRequest::class,
			MappedRequestValidatingDeserializer::class,
			'newPropertyIdRequestValidatingDeserializer',
		];
		yield [
			StatementIdUseCaseRequest::class,
			StatementIdRequestValidatingDeserializer::class,
			'newStatementIdRequestValidatingDeserializer',
		];
		yield [
			PropertyIdFilterUseCaseRequest::class,
			MappedRequestValidatingDeserializer::class,
			'newPropertyIdFilterRequestValidatingDeserializer',
		];
		yield [
			LanguageCodeUseCaseRequest::class,
			LanguageCodeRequestValidatingDeserializer::class,
			'newLanguageCodeRequestValidatingDeserializer',
		];
		yield [
			ItemFieldsUseCaseRequest::class,
			MappedRequestValidatingDeserializer::class,
			'newItemFieldsRequestValidatingDeserializer',
		];
		yield [
			StatementSerializationUseCaseRequest::class,
			StatementSerializationRequestValidatingDeserializer::class,
			'newStatementSerializationRequestValidatingDeserializer',
		];
		yield [
			EditMetadataUseCaseRequest::class,
			EditMetadataRequestValidatingDeserializer::class,
			'newEditMetadataRequestValidatingDeserializer',
		];
		yield [
			PatchUseCaseRequest::class,
			PatchRequestValidatingDeserializer::class,
			'newPatchRequestValidatingDeserializer',
		];
		yield [
			ItemLabelEditUseCaseRequest::class,
			ItemLabelEditRequestValidatingDeserializer::class,
			'newItemLabelEditRequestValidatingDeserializer',
		];
		yield [
			ItemDescriptionEditUseCaseRequest::class,
			ItemDescriptionEditRequestValidatingDeserializer::class,
			'newItemDescriptionEditRequestValidatingDeserializer',
		];
	}

	private function newRequestDeserializer( ValidatingRequestFieldDeserializerFactory $factory = null ): ValidatingRequestDeserializer {
		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( self::EXISTING_PROPERTY ), 'string' );

		return new ValidatingRequestDeserializer(
			$factory ?? TestValidatingRequestFieldDeserializerFactory::newFactory( $dataTypeLookup )
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
interface ItemDescriptionEditUseCaseRequest extends UseCaseRequest, ItemDescriptionEditRequest {}
// @codingStandardsIgnoreEnd
