<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemSerializationRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsItem(): void {
		$request = $this->createStub( ItemSerializationRequest::class );
		$request->method( 'getItem' )->willReturn( [ 'labels' => [ 'en' => 'English label' ] ] );
		$expectedItem = NewItem::withLabel( 'en', 'English label' )->build();
		$itemValidator = $this->createStub( ItemValidator::class );
		$itemValidator->method( 'getValidatedItem' )->willReturn( $expectedItem );

		$this->assertEquals(
			$expectedItem,
			( new ItemSerializationRequestValidatingDeserializer( $itemValidator ) )->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider itemValidationErrorProvider
	 */
	public function testGivenInvalidRequest_throws( ValidationError $validationError, UseCaseError $expectedError ): void {
		$itemSerialization = [ 'item serialization stub' ];
		$request = $this->createStub( ItemSerializationRequest::class );
		$request->method( 'getItem' )->willReturn( $itemSerialization );

		$itemValidator = $this->createMock( ItemValidator::class );
		$itemValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $itemSerialization )
			->willReturn( $validationError );

		try {
			( new ItemSerializationRequestValidatingDeserializer( $itemValidator ) )->validateAndDeserialize( $request );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertEquals( $expectedError, $useCaseEx );
		}
	}

	public function itemValidationErrorProvider(): Generator {
		yield 'invalid field' => [
			new ValidationError(
				ItemValidator::CODE_INVALID_FIELD,
				[
					ItemValidator::CONTEXT_FIELD_NAME => 'some-field',
					ItemValidator::CONTEXT_FIELD_VALUE => 'some-value',
				]
			),
			new UseCaseError(
				UseCaseError::ITEM_DATA_INVALID_FIELD,
				"Invalid input for 'some-field'",
				[
					UseCaseError::CONTEXT_PATH => 'some-field',
					UseCaseError::CONTEXT_VALUE => 'some-value',
				]
			),
		];

		yield 'unexpected field' => [
			new ValidationError(
				ItemValidator::CODE_UNEXPECTED_FIELD,
				[ ItemValidator::CONTEXT_FIELD_NAME => 'some-field' ]
			),
			new UseCaseError(
				UseCaseError::ITEM_DATA_UNEXPECTED_FIELD,
				'The request body contains an unexpected field',
				[ UseCaseError::CONTEXT_FIELD => 'some-field' ]
			),
		];

		yield 'missing labels and descriptions' => [
			new ValidationError( ItemValidator::CODE_MISSING_LABELS_AND_DESCRIPTIONS ),
			new UseCaseError(
				UseCaseError::MISSING_LABELS_AND_DESCRIPTIONS,
				'Item requires at least a label or a description in a language'
			),
		];
	}

}
