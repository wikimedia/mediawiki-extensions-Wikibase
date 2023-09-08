<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ItemIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\PropertyIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\StatementIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestFieldDeserializerFactory;
use Wikibase\Repo\RestApi\Application\UseCases\StatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ValidatingRequestDeserializerTest extends TestCase {

	public function testGivenValidItemIdRequest_returnsDeserializedItemId(): void {
		$request = $this->createStub( ItemIdUseCaseRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );

		$this->assertEquals(
			[ ItemIdRequestValidatingDeserializer::DESERIALIZED_VALUE => new ItemId( 'Q123' ) ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenInvalidItemIdRequest_throws(): void {
		$expectedError = $this->createStub( UseCaseError::class );
		$factory = $this->newFactoryWithThrowingValidator( ItemIdRequestValidatingDeserializer::class, $expectedError );
		$request = $this->createStub( ItemIdUseCaseRequest::class );
		$request->method( 'getItemId' )->willReturn( 'P123' );

		try {
			$this->newRequestDeserializer( $factory )->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	public function testGivenValidPropertyIdRequest_returnsDeserializedPropertyId(): void {
		$request = $this->createStub( PropertyIdUseCaseRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );

		$this->assertEquals(
			[ PropertyIdRequestValidatingDeserializer::DESERIALIZED_VALUE => new NumericPropertyId( 'P123' ) ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenInvalidPropertyIdRequest_throws(): void {
		$expectedError = $this->createStub( UseCaseError::class );
		$factory = $this->newFactoryWithThrowingValidator( PropertyIdRequestValidatingDeserializer::class, $expectedError );
		$request = $this->createStub( PropertyIdUseCaseRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'Q123' );

		try {
			$this->newRequestDeserializer( $factory )->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
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

	public function testGivenInvalidStatementIdRequest_throws(): void {
		$expectedError = $this->createStub( UseCaseError::class );
		$factory = $this->newFactoryWithThrowingValidator( StatementIdRequestValidatingDeserializer::class, $expectedError );
		$request = $this->createStub( StatementIdUseCaseRequest::class );
		$request->method( 'getStatementId' )->willReturn( 'Q123$invalid' );

		try {
			$this->newRequestDeserializer( $factory )->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	private function newRequestDeserializer( ValidatingRequestFieldDeserializerFactory $factory = null ): ValidatingRequestDeserializer {
		$factory ??= new ValidatingRequestFieldDeserializerFactory();
		return new ValidatingRequestDeserializer( $factory );
	}

	private function newFactoryWithThrowingValidator(
		string $validatorClass,
		UseCaseError $expectedError
	): ValidatingRequestFieldDeserializerFactory {
		$validator = $this->createStub( $validatorClass );
		$validator->method( 'validateAndDeserialize' )->willThrowException( $expectedError );
		$factory = $this->createStub( ValidatingRequestFieldDeserializerFactory::class );
		$factory->method( [
			ItemIdRequestValidatingDeserializer::class => 'newItemIdRequestValidatingDeserializer',
			PropertyIdRequestValidatingDeserializer::class => 'newPropertyIdRequestValidatingDeserializer',
			StatementIdRequestValidatingDeserializer::class => 'newStatementIdRequestValidatingDeserializer',
		][$validatorClass] )->willReturn( $validator );

		return $factory;
	}

}

// @codingStandardsIgnoreStart Various rules are unhappy about these interface one-liners, but there isn't much that can go wrong...
// We're creating some combined interfaces here because PHPUnit 9 does not support stubbing multiple interfaces
interface ItemIdUseCaseRequest extends UseCaseRequest, ItemIdRequest {}
interface PropertyIdUseCaseRequest extends UseCaseRequest, PropertyIdRequest {}
interface StatementIdUseCaseRequest extends UseCaseRequest, StatementIdRequest {}
// @codingStandardsIgnoreEnd
