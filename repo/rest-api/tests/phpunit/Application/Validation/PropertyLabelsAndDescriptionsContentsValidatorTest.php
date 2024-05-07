<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\Validation\PartiallyValidatedDescriptions;
use Wikibase\Repo\RestApi\Application\Validation\PartiallyValidatedLabels;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelsContentsValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\PropertyLabelsContentsValidator
 * @covers \Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionsContentsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyLabelsAndDescriptionsContentsValidatorTest extends TestCase {

	/**
	 * @dataProvider validatorProvider
	 *
	 * @param MockObject $singleTermValidator
	 * @param mixed $validator
	 * @param string $partialResultClass
	 * @param callable $getValidationResult
	 */
	public function testValid(
		MockObject $singleTermValidator,
		$validator,
		string $partialResultClass,
		callable $getValidationResult
	): void {
		$termsToValidate = new $partialResultClass( [
			new Term( 'de', 'de term' ),
			new Term( 'en', 'en term' ),
		] );
		$propertyId = new NumericPropertyId( 'P42' );

		$matcher = $this->exactly( count( $termsToValidate ) );
		$singleTermValidator->expects( $matcher )
			->method( 'validate' )
			->willReturnCallback(
				function ( $argId, $language, $text ) use ( $matcher, $termsToValidate, $propertyId ) {
					$termBeingValidated = array_values( iterator_to_array( $termsToValidate ) )[$matcher->getInvocationCount() - 1];

					$this->assertSame( $termBeingValidated->getLanguageCode(), $language );
					$this->assertSame( $termBeingValidated->getText(), $text );
					$this->assertSame( $propertyId, $argId );

					return null;
				}
			);

		$this->assertNull( $validator->validate( $termsToValidate, $propertyId ) );
		$this->assertEquals( $termsToValidate->asPlainTermList(), $getValidationResult() );
		$this->assertNotInstanceOf( $partialResultClass, $getValidationResult() );
	}

	/**
	 * @dataProvider validatorProvider
	 *
	 * @param MockObject $singleTermValidator
	 * @param mixed $validator
	 * @param string $partialResultClass
	 * @param callable $getValidationResult
	 */
	public function testValidateSpecificLanguages(
		MockObject $singleTermValidator,
		$validator,
		string $partialResultClass,
		callable $getValidationResult
	): void {
		$languagesToValidate = [ 'ar', 'en' ];
		$inputTerms = new $partialResultClass( [
			new Term( 'ar', 'ar term' ),
			new Term( 'de', 'de term' ),
			new Term( 'en', 'en term' ),
		] );
		$propertyId = new NumericPropertyId( 'P23' );

		$matcher = $this->exactly( count( $languagesToValidate ) );
		$singleTermValidator->expects( $matcher )
			->method( 'validate' )
			->willReturnCallback(
				function ( $argId, $language, $text ) use ( $matcher, $languagesToValidate, $inputTerms, $propertyId ) {
					$languageBeingValidated = $languagesToValidate[$matcher->getInvocationCount() - 1];

					$this->assertSame( $languageBeingValidated, $language );
					$this->assertSame( $inputTerms->getByLanguage( $languageBeingValidated )->getText(), $text );
					$this->assertSame( $propertyId, $argId );

					return null;
				}
			);

		$this->assertNull( $validator->validate( $inputTerms, $propertyId, $languagesToValidate ) );
		$this->assertEquals( $inputTerms->asPlainTermList(), $getValidationResult() );
		$this->assertNotInstanceOf( $partialResultClass, $getValidationResult() );
	}

	/**
	 * @dataProvider validatorProvider
	 *
	 * @param MockObject $singleTermValidator
	 * @param mixed $validator
	 * @param string $partialResultClass
	 * @throws Exception
	 */
	public function testInvalid(
		MockObject $singleTermValidator,
		$validator,
		string $partialResultClass
	): void {
		$termsToValidate = new $partialResultClass( [ new Term( 'de', 'de term' ) ] );
		$propertyId = new NumericPropertyId( 'P123' );

		$expectedValidationError = $this->createStub( ValidationError::class );
		$singleTermValidator->method( 'validate' )->willReturn( $expectedValidationError );

		$this->assertSame( $expectedValidationError, $validator->validate( $termsToValidate, $propertyId ) );
	}

	public function validatorProvider(): Generator {
		$itemLabelValidator = $this->createMock( PropertyLabelValidator::class );
		$itemLabelsContentsValidator = new PropertyLabelsContentsValidator( $itemLabelValidator );
		yield PropertyLabelsContentsValidator::class => [
			$itemLabelValidator,
			$itemLabelsContentsValidator,
			PartiallyValidatedLabels::class,
			fn() => $itemLabelsContentsValidator->getValidatedLabels(),
		];

		$itemDescriptionValidator = $this->createMock( PropertyDescriptionValidator::class );
		$itemDescriptionsContentsValidator = new PropertyDescriptionsContentsValidator( $itemDescriptionValidator );
		yield PropertyDescriptionsContentsValidator::class => [
			$itemDescriptionValidator,
			$itemDescriptionsContentsValidator,
			PartiallyValidatedDescriptions::class,
			fn() => $itemDescriptionsContentsValidator->getValidatedDescriptions(),
		];
	}

}
