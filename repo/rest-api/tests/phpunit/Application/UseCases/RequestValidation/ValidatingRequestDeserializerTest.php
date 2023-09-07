<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ItemIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\LanguageCodeRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\PropertyIdFilterRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\PropertyIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\StatementIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestFieldDeserializerFactory;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ValidatingRequestDeserializerTest extends TestCase {

	private const VALID_LANGUAGE_CODE = 'en';

	public function testGivenValidItemIdRequest_returnsDeserializedItemId(): void {
		$request = $this->createStub( ItemIdUseCaseRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );

		$this->assertEquals(
			[ ItemIdRequestValidatingDeserializer::DESERIALIZED_VALUE => new ItemId( 'Q123' ) ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidPropertyIdRequest_returnsDeserializedPropertyId(): void {
		$request = $this->createStub( PropertyIdUseCaseRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );

		$this->assertEquals(
			[ PropertyIdRequestValidatingDeserializer::DESERIALIZED_VALUE => new NumericPropertyId( 'P123' ) ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidLanguageCodeRequest_returnsLanguageCode(): void {
		$request = $this->createStub( LanguageCodeUseCaseRequest::class );
		$request->method( 'getLanguageCode' )->willReturn( self::VALID_LANGUAGE_CODE );

		$this->assertEquals(
			[ LanguageCodeRequestValidatingDeserializer::DESERIALIZED_VALUE => self::VALID_LANGUAGE_CODE ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidStatementIdRequest_returnsDeserializedStatementId(): void {
		$statementId = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );
		$request = $this->createStub( StatementIdUseCaseRequest::class );
		$request->method( 'getStatementId' )->willReturn( "$statementId" );
		$statementId = new StatementGuid( new ItemId( 'Q123' ), 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' );

		$this->assertEquals(
			[ StatementIdRequestValidatingDeserializer::DESERIALIZED_VALUE => $statementId ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenValidPropertyIdFilterRequest_returnsDeserializedPropertyId(): void {
		$request = $this->createStub( PropertyIdFilterUseCaseRequest::class );
		$request->method( 'getPropertyIdFilter' )->willReturn( 'P123' );

		$this->assertEquals(
			[ PropertyIdFilterRequestValidatingDeserializer::DESERIALIZED_VALUE => new NumericPropertyId( 'P123' ) ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
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
			PropertyIdRequestValidatingDeserializer::class,
			'newPropertyIdRequestValidatingDeserializer',
		];
		yield [
			StatementIdUseCaseRequest::class,
			StatementIdRequestValidatingDeserializer::class,
			'newStatementIdRequestValidatingDeserializer',
		];
		yield [
			PropertyIdFilterUseCaseRequest::class,
			PropertyIdFilterRequestValidatingDeserializer::class,
			'newPropertyIdFilterRequestValidatingDeserializer',
		];
		yield [
			LanguageCodeUseCaseRequest::class,
			LanguageCodeRequestValidatingDeserializer::class,
			'newLanguageCodeRequestValidatingDeserializer',
		];
	}

	private function newRequestDeserializer( ValidatingRequestFieldDeserializerFactory $factory = null ): ValidatingRequestDeserializer {
		$factory ??= new ValidatingRequestFieldDeserializerFactory( new LanguageCodeValidator( [ self::VALID_LANGUAGE_CODE ] ) );
		return new ValidatingRequestDeserializer( $factory );
	}

}

// @codingStandardsIgnoreStart Various rules are unhappy about these interface one-liners, but there isn't much that can go wrong...
// We're creating some combined interfaces here because PHPUnit 9 does not support stubbing multiple interfaces
interface ItemIdUseCaseRequest extends UseCaseRequest, ItemIdRequest {}
interface PropertyIdUseCaseRequest extends UseCaseRequest, PropertyIdRequest {}
interface StatementIdUseCaseRequest extends UseCaseRequest, StatementIdRequest {}
interface PropertyIdFilterUseCaseRequest extends UseCaseRequest, PropertyIdFilterRequest {}
interface LanguageCodeUseCaseRequest extends UseCaseRequest, LanguageCodeRequest {}
// @codingStandardsIgnoreEnd
