<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
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

	public const MAX_LENGTH = 50;

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
	 * @dataProvider itemLabelsValidationErrorProvider
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

		yield 'same value for label and description' => [
			new ValidationError(
				ItemValidator::CODE_LABEL_DESCRIPTION_SAME_VALUE,
				[ ItemValidator::CONTEXT_FIELD_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
				"Label and description for language 'en' can not have the same value",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'label and description duplication' => [
			new ValidationError(
				ItemValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					ItemValidator::CONTEXT_FIELD_LANGUAGE => 'en',
					ItemValidator::CONTEXT_FIELD_LABEL => 'en-label',
					ItemValidator::CONTEXT_FIELD_DESCRIPTION => 'en-description',
					ItemValidator::CONTEXT_MATCHING_ITEM_ID => 'Q123',
				]
			),
			new UseCaseError(
				UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
				"Item 'Q123' already has label 'en-label' associated with language code 'en', using the same description text",
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_LABEL => 'en-label',
					UseCaseError::CONTEXT_DESCRIPTION => 'en-description',
					UseCaseError::CONTEXT_MATCHING_ITEM_ID => 'Q123',
				]
			),
		];
	}

	public function itemLabelsValidationErrorProvider(): Generator {
		yield 'empty label' => [
			new ValidationError(
				ItemLabelValidator::CODE_EMPTY,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::LABEL_EMPTY,
				'Label must not be empty',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'label too long' => [
			new ValidationError(
				ItemLabelValidator::CODE_TOO_LONG,
				[
					ItemLabelValidator::CONTEXT_LABEL => str_repeat( 'a', self::MAX_LENGTH + 1 ),
					ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
					ItemLabelValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				]
			),
			new UseCaseError(
				UseCaseError::LABEL_TOO_LONG,
				'Label must be no more than 50 characters long',
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_CHARACTER_LIMIT => 50,
				]
			),
		];

		yield 'invalid label deserialization' => [
			new ValidationError(
				ItemValidator::CODE_INVALID_LABEL,
				[
					ItemValidator::CONTEXT_FIELD_LABEL => 22,
					ItemValidator::CONTEXT_FIELD_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_LABEL,
				'Not a valid label: 22',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'invalid label' => [
			new ValidationError(
				ItemLabelValidator::CODE_INVALID,
				[
					ItemLabelValidator::CONTEXT_LABEL => "invalid \t",
					ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_LABEL,
				"Not a valid label: invalid \t",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'invalid label language code' => [
			new ValidationError(
				ItemValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					ItemValidator::CONTEXT_FIELD_NAME => ItemValidator::CONTEXT_FIELD_LABEL,
					ItemValidator::CONTEXT_FIELD_LANGUAGE => 'e2',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_LANGUAGE_CODE,
				'Not a valid language code: e2',
				[
					UseCaseError::CONTEXT_PATH => ItemValidator::CONTEXT_FIELD_LABEL,
					UseCaseError::CONTEXT_LANGUAGE => 'e2',
				]
			),
		];
	}

}
