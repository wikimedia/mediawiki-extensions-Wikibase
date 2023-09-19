<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemLabelEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemLabelEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\ItemLabelEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemLabelEditRequestValidatingDeserializerTest extends TestCase {

	public function testGivenValidRequest_returnsLabel(): void {
		$request = $this->createStub( ItemLabelEditRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getLabel' )->willReturn( 'potato' );

		$this->assertEquals(
			new Term( 'en', 'potato' ),
			( new ItemLabelEditRequestValidatingDeserializer( $this->createStub( ItemLabelValidator::class ) ) )
				->validateAndDeserialize( $request )
		);
	}

	/**
	 * @dataProvider invalidLabelProvider
	 */
	public function testWithInvalidLabel(
		ValidationError $validationError,
		string $expectedErrorCode,
		string $expectedErrorMessage,
		array $expectedContext = []
	): void {
		$request = $this->createStub( ItemLabelEditRequest::class );
		$request->method( 'getItemId' )->willReturn( 'Q123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getLabel' )->willReturn( 'my label' );

		$itemLabelValidator = $this->createStub( ItemLabelValidator::class );
		$itemLabelValidator->method( 'validate' )->willReturn( $validationError );

		try {
			( new ItemLabelEditRequestValidatingDeserializer( $itemLabelValidator ) )
				->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertSame( $expectedErrorCode, $error->getErrorCode() );
			$this->assertSame( $expectedErrorMessage, $error->getErrorMessage() );
			$this->assertSame( $expectedContext, $error->getErrorContext() );
		}
	}

	public static function invalidLabelProvider(): Generator {
		$label = "tab characters \t not allowed";
		yield 'invalid label' => [
			new ValidationError(
				ItemLabelValidator::CODE_INVALID,
				[ ItemLabelValidator::CONTEXT_VALUE => $label ],
			),
			UseCaseError::INVALID_LABEL,
			"Not a valid label: $label",
		];

		yield 'label empty' => [
			new ValidationError( ItemLabelValidator::CODE_EMPTY ),
			UseCaseError::LABEL_EMPTY,
			'Label must not be empty',
		];

		$label = 'This label is too long.';
		$limit = 250;
		yield 'label too long' => [
			new ValidationError( ItemLabelValidator::CODE_TOO_LONG, [
				ItemLabelValidator::CONTEXT_VALUE => $label,
				ItemLabelValidator::CONTEXT_LIMIT => $limit,
			] ),
			UseCaseError::LABEL_TOO_LONG,
			"Label must be no more than $limit characters long",
			[
				UseCaseError::CONTEXT_VALUE => $label,
				UseCaseError::CONTEXT_CHARACTER_LIMIT => $limit,
			],
		];

		$language = 'en';
		yield 'label equals description' => [
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => $language ]
			),
			UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
			"Label and description for language code '$language' can not have the same value.",
			[ UseCaseError::CONTEXT_LANGUAGE => $language ],
		];

		$language = 'en';
		$label = 'My Label';
		$description = 'My Description';
		$itemId = 'Q456';
		yield 'label/description not unique' => [
			new ValidationError( ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE, [
				ItemLabelValidator::CONTEXT_LANGUAGE => $language,
				ItemLabelValidator::CONTEXT_LABEL => $label,
				ItemLabelValidator::CONTEXT_DESCRIPTION => $description,
				ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID => $itemId,
			] ),
			UseCaseError::ITEM_LABEL_DESCRIPTION_DUPLICATE,
			"Item $itemId already has label '$label' associated with language code '$language', using the same description text.",
			[
				UseCaseError::CONTEXT_LANGUAGE => $language,
				UseCaseError::CONTEXT_LABEL => $label,
				UseCaseError::CONTEXT_DESCRIPTION => $description,
				UseCaseError::CONTEXT_MATCHING_ITEM_ID => $itemId,
			],
		];
	}

}
