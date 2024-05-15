<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\AliasesValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AliasesValidatorTest extends TestCase {

	private AliasesInLanguageValidator $aliasesInLanguageValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->aliasesInLanguageValidator = $this->createStub( AliasesInLanguageValidator::class );
	}

	public function testValid(): void {
		$language = 'en';
		$aliases = [ 'en-alias-1', 'en-alias-2' ];

		$validator = $this->newValidator();

		$this->assertNull( $validator->validate( [ $language => $aliases ] ) );
		$this->assertEquals( new AliasGroupList( [ new AliasGroup( $language, $aliases ) ] ), $validator->getValidatedAliases() );
	}

	public function testValidWithEmptyAliases(): void {
		$validator = $this->newValidator();

		$this->assertNull( $validator->validate( [] ) );
		$this->assertEquals( new AliasGroupList(), $validator->getValidatedAliases() );
	}

	public function testMulLanguage_isValid(): void {
		$this->assertNull( $this->newValidator()->validate( [ 'mul' => [ 'alias' ] ] ) );
	}

	public function testInvalidAliases_returnsValidationError(): void {
		$invalidAliases = [ [ 'alias 1', 'alias 2' ], [ 'alias 3' ] ];

		$this->assertEquals(
			new ValidationError(
				AliasesValidator::CODE_INVALID_ALIASES,
				[ AliasesValidator::CONTEXT_ALIASES => $invalidAliases ]
			),
			$this->newValidator()->validate( $invalidAliases )
		);
	}

	/**
	 * @dataProvider provideInvalidLanguageCode
	 *
	 * @param string|int $invalidLanguageCode
	 */
	public function testInvalidLanguage_returnsValidationError( $invalidLanguageCode ): void {
		$this->assertEquals(
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => $invalidLanguageCode,
					LanguageCodeValidator::CONTEXT_PATH => 'alias',
				]
			),
			$this->newValidator()->validate( [ $invalidLanguageCode => [ 'alias' ] ] )
		);
	}

	public function provideInvalidLanguageCode(): Generator {
		yield "'fr' is not valid language code" => [ 'fr' ];
		yield 'empty string not a valid language code' => [ '' ];
		yield "'123' is not a valid language code" => [ '123' ];
		yield '321 is not a valid language code' => [ 321 ];
	}

	public function testEmptyAliasesInLanguageList_returnsValidationError(): void {
		$language = 'en';

		$this->assertEquals(
			new ValidationError(
				AliasesValidator::CODE_EMPTY_ALIAS_LIST,
				[ AliasesValidator::CONTEXT_LANGUAGE => $language ]
			),
			$this->newValidator()->validate( [ $language => [] ] )
		);
	}

	public function testInvalidAliasesInLanguageList_returnsValidationError(): void {
		$languageCode = 'en';
		$aliasesInLanguage = 'not a list of aliases in a language';

		$this->aliasesInLanguageValidator = $this->createMock( AliasesInLanguageValidator::class );
		$this->aliasesInLanguageValidator->expects( $this->never() )->method( 'validate' );

		$this->assertEquals(
			new ValidationError(
				AliasesValidator::CODE_INVALID_ALIAS_LIST,
				[ AliasesValidator::CONTEXT_LANGUAGE => $languageCode ]
			),
			$this->newValidator()->validate( [ $languageCode => $aliasesInLanguage ] )
		);
	}

	public function testInvalidAlias_returnsValidationError(): void {
		$language = 'en';
		$invalidAlias = 'invalid /t alias';

		$expectedError = $this->createStub( ValidationError::class );
		$this->aliasesInLanguageValidator = $this->createMock( AliasesInLanguageValidator::class );
		$this->aliasesInLanguageValidator->expects( $this->once() )
			->method( 'validate' )
			->with( new AliasGroup( $language, [ $invalidAlias ] ) )
			->willReturn( $expectedError );

		$this->assertEquals( $expectedError, $this->newValidator()->validate( [ $language => [ $invalidAlias ] ] ) );
	}

	public function testEmptyAlias_returnsValidationError(): void {
		$language = 'en';

		$this->assertEquals(
			new ValidationError(
				AliasesValidator::CODE_EMPTY_ALIAS,
				[ AliasesValidator::CONTEXT_LANGUAGE => $language ]
			),
			$this->newValidator()->validate( [ $language => [ '' ] ] )
		);
	}

	public function testDuplicateAlias_returnsValidationError(): void {
		$language = 'en';
		$duplicatedAlias = 'alias';

		$this->assertEquals(
			new ValidationError(
				AliasesValidator::CODE_DUPLICATE_ALIAS,
				[
					AliasesValidator::CONTEXT_LANGUAGE => $language,
					AliasesValidator::CONTEXT_ALIAS => $duplicatedAlias,
				]
			),
			$this->newValidator()->validate( [ $language => [ $duplicatedAlias, $duplicatedAlias ] ] )
		);
	}

	public function testInvalidAliasType_returnsValidationError(): void {
		$language = 'en';
		$invalidAlias = 123;

		$this->assertEquals(
			new ValidationError(
				AliasesValidator::CODE_INVALID_ALIAS,
				[
					AliasesValidator::CONTEXT_LANGUAGE => $language,
					AliasesValidator::CONTEXT_ALIAS => $invalidAlias,
				]
			),
			$this->newValidator()->validate( [ $language => [ $invalidAlias ] ] )
		);
	}

	public function testGivenGetValidatedAliasesCalledBeforeValidate_throws(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedAliases();
	}

	private function newValidator(): AliasesValidator {
		return new AliasesValidator(
			$this->aliasesInLanguageValidator,
			new LanguageCodeValidator( [ 'en', 'de', 'mul' ] ),
			new AliasesDeserializer(),
		);
	}

}
