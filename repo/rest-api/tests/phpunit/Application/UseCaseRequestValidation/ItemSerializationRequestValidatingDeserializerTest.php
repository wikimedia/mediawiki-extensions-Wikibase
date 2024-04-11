<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemSerializationRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
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
	 * @dataProvider itemDescriptionsValidationErrorProvider
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
				ItemLabelValidator::CODE_INVALID,
				[
					ItemLabelValidator::CONTEXT_LABEL => 22,
					ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
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
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_PATH_VALUE => ItemValidator::CONTEXT_FIELD_LABELS,
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE => 'e2',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_LANGUAGE_CODE,
				'Not a valid language code: e2',
				[
					UseCaseError::CONTEXT_PATH => ItemValidator::CONTEXT_FIELD_LABELS,
					UseCaseError::CONTEXT_LANGUAGE => 'e2',
				]
			),
		];

		yield 'same value for label and description' => [
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_SAME_AS_DESCRIPTION,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
				"Label and description for language 'en' can not have the same value",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'label and description duplication' => [
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					ItemLabelValidator::CONTEXT_LANGUAGE => 'en',
					ItemLabelValidator::CONTEXT_LABEL => 'en-label',
					ItemLabelValidator::CONTEXT_DESCRIPTION => 'en-description',
					ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID => 'Q123',
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

	public function itemDescriptionsValidationErrorProvider(): Generator {
		yield 'empty description' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_EMPTY,
				[ ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::DESCRIPTION_EMPTY,
				'Description must not be empty',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];
		yield 'description too long' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_TOO_LONG,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => str_repeat( 'a', self::MAX_LENGTH + 1 ),
					ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
					ItemDescriptionValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				]
			),
			new UseCaseError(
				UseCaseError::DESCRIPTION_TOO_LONG,
				'Description must be no more than 50 characters long',
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_CHARACTER_LIMIT => 50,
				]
			),
		];
		yield 'invalid description deserialization' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_INVALID,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => 22,
					ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_DESCRIPTION,
				'Not a valid description: 22',
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];
		yield 'invalid description' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_INVALID,
				[
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => "invalid \t",
					ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_DESCRIPTION,
				"Not a valid description: invalid \t",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];
		yield 'invalid description language code' => [
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_PATH_VALUE => ItemValidator::CONTEXT_FIELD_DESCRIPTIONS,
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE => 'e2',
				]
			),
			new UseCaseError(
				UseCaseError::INVALID_LANGUAGE_CODE,
				'Not a valid language code: e2',
				[
					UseCaseError::CONTEXT_PATH => ItemValidator::CONTEXT_FIELD_DESCRIPTIONS,
					UseCaseError::CONTEXT_LANGUAGE => 'e2',
				]
			),
		];

		yield 'same value for description and label ' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_SAME_AS_LABEL,
				[ ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en' ]
			),
			new UseCaseError(
				UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
				"Label and description for language 'en' can not have the same value",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en' ]
			),
		];

		yield 'description and label duplication' => [
			new ValidationError(
				ItemDescriptionValidator::CODE_DESCRIPTION_LABEL_DUPLICATE,
				[
					ItemDescriptionValidator::CONTEXT_LANGUAGE => 'en',
					ItemDescriptionValidator::CONTEXT_LABEL => 'en-label',
					ItemDescriptionValidator::CONTEXT_DESCRIPTION => 'en-description',
					ItemDescriptionValidator::CONTEXT_MATCHING_ITEM_ID => 'Q123',
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

}
