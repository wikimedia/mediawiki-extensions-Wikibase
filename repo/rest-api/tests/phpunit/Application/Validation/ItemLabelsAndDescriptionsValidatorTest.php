<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelsAndDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\ItemLabelsAndDescriptionsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemLabelsAndDescriptionsValidatorTest extends TestCase {

	private ItemLabelValidator $newItemLabelValidator;
	private ItemDescriptionValidator $newItemDescriptionValidator;

	protected function setUp(): void {
		parent::setUp();
		$this->newItemLabelValidator = $this->createStub( ItemLabelValidator::class );
		$this->newItemDescriptionValidator = $this->createStub( ItemDescriptionValidator::class );
	}

	public function testValid(): void {
		$language = 'en';
		$labelText = 'valid item label';
		$descriptionText = 'valid item description';

		$validator = $this->newValidator();

		$this->assertNull( $validator->validate( [ $language => $labelText ], [ $language => $descriptionText ] ) );
		$this->assertEquals( new TermList( [ new Term( $language, $labelText ) ] ), $validator->getValidatedLabels() );
		$this->assertEquals( new TermList( [ new Term( $language, $descriptionText ) ] ), $validator->getValidatedDescriptions() );
	}

	public function testMulLabelLanguage_isValid(): void {
		$this->assertNull( $this->newValidator()->validate( [ 'mul' => 'item label' ], [] ) );
	}

	public function testInvalidLabelLanguage_returnsValidationError(): void {
		$language = 'xyz';

		$this->assertEquals(
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE => $language,
					LanguageCodeValidator::CONTEXT_PATH_VALUE => 'labels',
				]
			),
			$this->newValidator()->validate( [ $language => 'item label' ], [] )
		);
	}

	public function testInvalidLabel_returnsValidationError(): void {
		$language = 'en';
		$label = 'invalidLabel';
		$description = 'description';

		$expectedError = $this->createStub( ValidationError::class );
		$this->newItemLabelValidator = $this->createMock( ItemLabelValidator::class );
		$this->newItemLabelValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $language, $label, new TermList( [ new Term( $language, $description ) ] ) )
			->willReturn( $expectedError );

		$this->assertEquals(
			$expectedError,
			$this->newValidator()->validate( [ $language => $label ], [ $language => $description ] )
		);
	}

	public function testEmptyLabel_returnsValidationError(): void {
		$language = 'en';

		$this->assertEquals(
			new ValidationError(
				ItemLabelsAndDescriptionsValidator::CODE_EMPTY_LABEL,
				[ ItemLabelsAndDescriptionsValidator::CONTEXT_FIELD_LANGUAGE => $language ]
			),
			$this->newValidator()->validate( [ $language => '' ], [] )
		);
	}

	public function testInvalidLabelType_returnsValidationError(): void {
		$language = 'en';
		$invalidLabel = 123;

		$this->assertEquals(
			new ValidationError(
				ItemLabelsAndDescriptionsValidator::CODE_INVALID_LABEL,
				[
					ItemLabelsAndDescriptionsValidator::CONTEXT_FIELD_LANGUAGE => $language,
					ItemLabelsAndDescriptionsValidator::CONTEXT_FIELD_LABEL => $invalidLabel,
				]
			),
			$this->newValidator()->validate( [ $language => $invalidLabel ], [] )
		);
	}

	public function testMulDescriptionLanguage_returnsValidationError(): void {
		$this->assertEquals(
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_PATH_VALUE => 'descriptions',
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE => 'mul',
				]
			),
			$this->newValidator()->validate( [], [ 'mul' => 'item description' ] )
		);
	}

	public function testInvalidDescriptionLanguage_returnsValidationError(): void {
		$language = 'xyz';

		$this->assertEquals(
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_PATH_VALUE => 'descriptions',
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE => $language,
				]
			),
			$this->newValidator()->validate( [], [ $language => 'item description' ] )
		);
	}

	public function testInvalidDescription_returnsValidationError(): void {
		$language = 'en';
		$label = 'label';
		$description = 'invalidDescription';

		$expectedError = $this->createStub( ValidationError::class );
		$this->newItemDescriptionValidator = $this->createMock( ItemDescriptionValidator::class );
		$this->newItemDescriptionValidator->expects( $this->once() )
			->method( 'validate' )
			->with( $language, $description, new TermList( [ new Term( $language, $label ) ] ) )
			->willReturn( $expectedError );

		$this->assertEquals(
			$expectedError,
			$this->newValidator()->validate( [ $language => $label ], [ $language => $description ] )
		);
	}

	public function testEmptyDescription_returnsValidationError(): void {
		$language = 'en';

		$this->assertEquals(
			new ValidationError(
				ItemLabelsAndDescriptionsValidator::CODE_EMPTY_DESCRIPTION,
				[ ItemLabelsAndDescriptionsValidator::CONTEXT_FIELD_LANGUAGE => $language ]
			),
			$this->newValidator()->validate( [], [ $language => '' ] )
		);
	}

	public function testInvalidDescriptionType_returnsValidationError(): void {
		$language = 'en';
		$invalidDescription = 123;

		$this->assertEquals(
			new ValidationError(
				ItemLabelsAndDescriptionsValidator::CODE_INVALID_DESCRIPTION,
				[
					ItemLabelsAndDescriptionsValidator::CONTEXT_FIELD_LANGUAGE => $language,
					ItemLabelsAndDescriptionsValidator::CONTEXT_FIELD_DESCRIPTION => $invalidDescription,
				]
			),
			$this->newValidator()->validate( [], [ $language => $invalidDescription ] )
		);
	}

	private function newValidator(): ItemLabelsAndDescriptionsValidator {
		return new ItemLabelsAndDescriptionsValidator(
			$this->newItemLabelValidator,
			$this->newItemDescriptionValidator,
			new LanguageCodeValidator( [ 'en', 'de', 'mul' ] ),
			new LanguageCodeValidator( [ 'en', 'de' ] ),
			new LabelsDeserializer(),
			new DescriptionsDeserializer()
		);
	}

}
