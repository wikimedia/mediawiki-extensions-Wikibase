<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryPropertyDescriptionValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryPropertyDescriptionValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryPropertyDescriptionValidatorTest extends TestCase {

	private const MAX_LENGTH = 50;

	public function testGivenValidDescription_returnsNull(): void {
		$this->assertNull(
			$this->newValidator()->validate(
				'en',
				'valid description',
				new TermList( [ new Term( 'en', 'valid item label' ) ] )
			)
		);
	}

	public function testGivenValidDescriptionAndPropertyWithoutLabel_returnsNull(): void {
		$this->assertNull(
			$this->newValidator()->validate( 'en', 'valid description', new TermList( [] ) )
		);
	}

	/**
	 * @dataProvider provideInvalidDescription
	 */
	public function testGivenInvalidDescription_returnsValidationError(
		string $language,
		string $description,
		string $errorCode,
		array $errorContext = []
	): void {
		$this->assertEquals(
			new ValidationError( $errorCode, $errorContext ),
			$this->newValidator()->validate( $language, $description, new TermList( [] ) )
		);
	}

	public static function provideInvalidDescription(): Generator {
		$language = 'en';
		yield 'description too short' => [
			$language,
			'',
			PropertyDescriptionValidator::CODE_EMPTY,
			[ PropertyDescriptionValidator::CONTEXT_LANGUAGE => $language ],
		];

		$description = str_repeat( 'a', self::MAX_LENGTH + 1 );
		yield 'description too long' => [
			$language,
			$description,
			PropertyDescriptionValidator::CODE_TOO_LONG,
			[
				PropertyDescriptionValidator::CONTEXT_DESCRIPTION => $description,
				PropertyDescriptionValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				PropertyDescriptionValidator::CONTEXT_LANGUAGE => $language,
			],
		];

		$description = "description with tab character \t not allowed";
		yield 'description has invalid character' => [
			$language,
			$description,
			PropertyDescriptionValidator::CODE_INVALID,
			[
				PropertyDescriptionValidator::CONTEXT_DESCRIPTION => $description,
				PropertyDescriptionValidator::CONTEXT_LANGUAGE => $language,
			],
		];
	}

	public function testGivenDescriptionSameAsLabel_returnsValidationError(): void {
		$propertyLabel = 'Property Label';
		$language = 'en';

		$this->assertEquals(
			new ValidationError(
				PropertyDescriptionValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyDescriptionValidator::CONTEXT_LANGUAGE => $language ]
			),
			$this->newValidator()->validate(
				$language,
				$propertyLabel,
				new TermList( [ new Term( $language, $propertyLabel ) ] )
			)
		);
	}

	public function testUnchangedDescription_willNotPerformValidation(): void {
		$language = 'en';
		$description = 'Property Description';

		$this->assertNull( $this->newValidator()->validate( $language, $description, new TermList( [] ) ) );
	}

	private function newValidator(): TermValidatorFactoryPropertyDescriptionValidator {
		return new TermValidatorFactoryPropertyDescriptionValidator( $this->newTermValidatorFactory() );
	}

	private function newTermValidatorFactory(): TermValidatorFactory {
		return new TermValidatorFactory(
			self::MAX_LENGTH,
			WikibaseRepo::getTermsLanguages()->getLanguages(),
			WikibaseRepo::getEntityIdParser(),
			WikibaseRepo::getTermsCollisionDetectorFactory(),
			WikibaseRepo::getTermLookup(),
			$this->createStub( LanguageNameUtils::class )
		);
	}

}
