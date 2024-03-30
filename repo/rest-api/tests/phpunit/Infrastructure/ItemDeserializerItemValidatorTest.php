<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SerializationException;
use Wikibase\Repo\RestApi\Application\Serialization\UnexpectedFieldException;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\ItemDeserializerItemValidator;
use Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryLabelTextValidator;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\ItemDeserializerItemValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemDeserializerItemValidatorTest extends TestCase {

	public const MAX_LENGTH = 50;
	private ItemDeserializer $deserializer;
	private LanguageCodeValidator $languageCodeValidator;
	private TermValidatorFactoryLabelTextValidator $labelTextValidator;
	private TermsCollisionDetector $termsCollisionDetector;

	protected function setUp(): void {
		parent::setUp();
		$this->deserializer = $this->createStub( ItemDeserializer::class );
		$this->languageCodeValidator = $this->createStub( LanguageCodeValidator::class );
		$this->labelTextValidator = new TermValidatorFactoryLabelTextValidator( WikibaseRepo::getTermValidatorFactory() );
		$this->termsCollisionDetector = $this->createStub( TermsCollisionDetector::class );
	}

	/**
	 * @dataProvider deserializationErrorProvider
	 * @dataProvider labelsDeserializationErrorProvider
	 */
	public function testGivenInvalidItemSerialization_validateReturnsValidationError(
		SerializationException $exception,
		string $expectedErrorCode,
		array $expectedContext
	): void {
		$this->deserializer->method( 'deserialize' )->willThrowException( $exception );

		$error = $this->newValidator()->validate( [ 'invalid' => 'serialization' ] );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame( $expectedErrorCode, $error->getCode() );
		$this->assertSame( $expectedContext, $error->getContext() );
	}

	public static function deserializationErrorProvider(): Generator {
		yield 'invalid field exception' => [
			new InvalidFieldException( 'some-field', 'some-value' ),
			ItemValidator::CODE_INVALID_FIELD,
			[ 'field' => 'some-field', 'value' => 'some-value' ],
		];

		yield 'unexpected field exception' => [
			new UnexpectedFieldException( 'foo' ),
			ItemValidator::CODE_UNEXPECTED_FIELD,
			[ 'field' => 'foo' ],
		];
	}

	public function labelsDeserializationErrorProvider(): Generator {
		yield 'empty label' => [
			new EmptyLabelException( 'en', '' ),
			ItemValidator::CODE_EMPTY_LABEL,
			[ 'language' => 'en' ],
		];

		yield 'invalid label' => [
			new InvalidLabelException( 'en', 123 ),
			ItemValidator::CODE_INVALID_LABEL,
			[ 'language' => 'en', 'label' => 123 ],
		];
	}

	/**
	 * @dataProvider invalidLanguageCodeProvider
	 */
	public function testGivenInvalidLanguageCode_validateReturnsValidationError(
		string $invalidLanguageCode,
		array $itemSerialization,
		Item $itemDeserialization,
		string $errorContextField
	): void {
		$this->deserializer = $this->createStub( ItemDeserializer::class );
		$this->deserializer->method( 'deserialize' )->willReturn( $itemDeserialization );

		$this->languageCodeValidator = $this->createMock( LanguageCodeValidator::class );
		$this->languageCodeValidator->method( 'validate' )->with( $invalidLanguageCode )->willReturn(
			$this->createStub( ValidationError::class )
		);

		$expectedValidationError = new ValidationError(
			ItemValidator::CODE_INVALID_LANGUAGE_CODE,
			[
				ItemValidator::CONTEXT_FIELD_LANGUAGE => $invalidLanguageCode,
				ItemValidator::CONTEXT_FIELD_NAME => $errorContextField,
			]
		);

		$this->assertEquals( $expectedValidationError, $this->newValidator()->validate( $itemSerialization ) );
	}

	public function invalidLanguageCodeProvider(): Generator {
		$invalidLanguageCode = 'xyz123';
		yield 'invalid language code for labels' => [
			$invalidLanguageCode,
			[ 'labels' => [ $invalidLanguageCode => 'label' ] ],
			NewItem::withLabel( $invalidLanguageCode, 'label' )->build(),
			ItemValidator::CONTEXT_FIELD_LABEL,
		];
	}

	public function testGivenInvalidLabelText_validateReturnsValidationError(): void {
		$invalidLabel = 'invalid \t label';
		$language = 'en';

		$this->deserializer = $this->createStub( ItemDeserializer::class );
		$this->deserializer->method( 'deserialize' )->willReturn(
			NewItem::withLabel( $language, $invalidLabel )->build()
		);

		$this->labelTextValidator = $this->createMock( TermValidatorFactoryLabelTextValidator::class );
		$this->labelTextValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $invalidLabel, $language )
			->willReturn( new ValidationError( ItemLabelValidator::CODE_INVALID ) );

		$error = $this->newValidator()->validate( [ 'labels' => [ $language => $invalidLabel ] ] );

		$this->assertInstanceOf( ValidationError::class, $error );
	}

	public function testGivenDescriptionSameAsLabel_validateReturnsValidationError(): void {
		$itemLabel = 'Item Label';
		$language = 'en';

		$this->deserializer = $this->createStub( ItemDeserializer::class );
		$this->deserializer->method( 'deserialize' )->willReturn(
			NewItem::withLabel( $language, $itemLabel )->andDescription( $language, $itemLabel )->build()
		);

		$this->assertEquals(
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ ItemLabelValidator::CONTEXT_LANGUAGE => $language ]
			),
			$this->newValidator()->validate( [ 'labels' => [ $language => $itemLabel ], 'descriptions' => [ $language => $itemLabel ] ] )
		);
	}

	public function testGivenLabelDescriptionCollision_validateReturnsValidationError(): void {
		$language = 'en';
		$label = 'Item Label';
		$description = 'Item Description';
		$matchingItemId = 'Q789';

		$this->deserializer = $this->createStub( ItemDeserializer::class );
		$this->deserializer->method( 'deserialize' )->willReturn(
			NewItem::withLabel( $language, $label )->andDescription( $language, $description )->build()
		);

		$this->termsCollisionDetector = $this->createMock( TermsCollisionDetector::class );
		$this->termsCollisionDetector
			->expects( $this->once() )
			->method( 'detectLabelAndDescriptionCollision' )
			->with( $language, $label, $description )
			->willReturn( new ItemId( $matchingItemId ) );

		$this->assertEquals(
			new ValidationError(
				ItemLabelValidator::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					ItemLabelValidator::CONTEXT_LANGUAGE => $language,
					ItemLabelValidator::CONTEXT_LABEL => $label,
					ItemLabelValidator::CONTEXT_DESCRIPTION => $description,
					ItemLabelValidator::CONTEXT_MATCHING_ITEM_ID => $matchingItemId,
				]
			),
			$this->newValidator()->validate( [ 'labels' => [ $language => $label ], 'descriptions' => [ $language => $description ] ] )
		);
	}

	public function testGivenEmptyItem_validateReturnsValidationError(): void {
		$this->deserializer = $this->createStub( ItemDeserializer::class );
		$this->deserializer->method( 'deserialize' )->willReturn( new Item() );

		$error = $this->newValidator()->validate( [] );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame( ItemValidator::CODE_MISSING_LABELS_AND_DESCRIPTIONS, $error->getCode() );
	}

	public function testGetValidatedItem_calledAfterValidate(): void {
		$serialization = [ 'labels' => [ 'en' => 'english label' ] ];
		$deserializedItem = NewItem::withLabel( 'en', 'english label' )->build();

		$this->deserializer = $this->createMock( ItemDeserializer::class );
		$this->deserializer->method( 'deserialize' )->with( $serialization )->willReturn( $deserializedItem );

		$validator = $this->newValidator();
		$this->assertNull( $validator->validate( $serialization ) );
		$this->assertSame( $deserializedItem, $validator->getValidatedItem() );
	}

	public function testGetValidatedItem_calledBeforeValidate(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedItem();
	}

	private function newValidator(): ItemValidator {
		return new ItemDeserializerItemValidator(
			$this->deserializer,
			$this->languageCodeValidator,
			$this->labelTextValidator,
			$this->termsCollisionDetector
		);
	}
}
