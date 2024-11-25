<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\AliasesValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Validators\MembershipValidator;

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

		$this->assertNull( $validator->validate( [ $language => $aliases ], '' ) );
		$this->assertEquals( new AliasGroupList( [ new AliasGroup( $language, $aliases ) ] ), $validator->getValidatedAliases() );
	}

	public function testValidWithEmptyAliases(): void {
		$validator = $this->newValidator();

		$this->assertNull( $validator->validate( [], '/item/aliases' ) );
		$this->assertEquals( new AliasGroupList(), $validator->getValidatedAliases() );
	}

	public function testMulLanguage_isValid(): void {
		$this->assertNull( $this->newValidator()->validate( [ 'mul' => [ 'alias' ] ], '/property/aliases' ) );
	}

	/**
	 * @dataProvider provideInvalidAliases
	 */
	public function testInvalidSerialization_returnsValidationError(
		ValidationError $expectedError,
		array $aliases,
		string $basePath
	): void {
		$validationError = $this->newValidator()->validate( $aliases, $basePath );
		$this->assertEquals( $expectedError, $validationError );
	}

	public static function provideInvalidAliases(): Generator {
		yield 'invalid aliases - sequential array' => [
			new ValidationError(
				AliasesValidator::CODE_INVALID_VALUE,
				[
					AliasesValidator::CONTEXT_VALUE => [ 'not', 'an', 'associative', 'array' ],
					AliasesValidator::CONTEXT_PATH => '/aliases',
				]
			),
			[ 'not', 'an', 'associative', 'array' ],
			'/aliases',
		];

		yield 'invalid language code - integer' => [
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 4602,
					LanguageCodeValidator::CONTEXT_PATH => '/item/aliases',
				]
			),
			[ 4602 => [ 'alias 1', 'alias 2' ] ],
			'/item/aliases',
		];

		yield 'invalid language code - xyz' => [
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => 'xyz',
					LanguageCodeValidator::CONTEXT_PATH => '/property/aliases',
				]
			),
			[ 'xyz' => [ 'alias 1', 'alias 2' ] ],
			'/property/aliases',
		];

		yield 'invalid language code - empty string' => [
			new ValidationError(
				LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
				[
					LanguageCodeValidator::CONTEXT_LANGUAGE_CODE => '',
					LanguageCodeValidator::CONTEXT_PATH => '',
				]
			),
			[ '' => [ 'alias 1', 'alias 2' ] ],
			'',
		];

		yield "invalid 'aliases in language' list - string" => [
			new ValidationError(
				AliasesValidator::CODE_INVALID_VALUE,
				[
					AliasesValidator::CONTEXT_PATH => '/item/aliases/en',
					AliasesValidator::CONTEXT_VALUE => 'not a list of aliases in a language',
				]
			),
			[ 'en' => 'not a list of aliases in a language' ],
			'/item/aliases',
		];

		yield "invalid 'aliases in language' list - associative array" => [
			new ValidationError(
				AliasesValidator::CODE_INVALID_VALUE,
				[
					AliasesValidator::CONTEXT_PATH => '/property/aliases/en',
					AliasesValidator::CONTEXT_VALUE => [ 'not' => 'a', 'sequential' => 'array' ],
				]
			),
			[ 'en' => [ 'not' => 'a', 'sequential' => 'array' ] ],
			'/property/aliases',
		];

		yield "invalid 'aliases in language' list - empty array" => [
			new ValidationError(
				AliasesValidator::CODE_INVALID_VALUE,
				[ AliasesValidator::CONTEXT_VALUE => [], AliasesValidator::CONTEXT_PATH => '/en' ]
			),
			[ 'en' => [] ],
			'',
		];

		yield 'invalid alias - integer' => [
			new ValidationError(
				AliasesValidator::CODE_INVALID_VALUE,
				[ AliasesValidator::CONTEXT_PATH => '/item/aliases/en/1', AliasesValidator::CONTEXT_VALUE => 1794 ]
			),
			[ 'en' => [ 'first alias', 1794 ] ],
			'/item/aliases',
		];

		yield 'invalid alias - empty alias at position 0' => [
			new ValidationError(
				AliasesValidator::CODE_INVALID_VALUE,
				[ AliasesValidator::CONTEXT_PATH => '/property/aliases/en/0', AliasesValidator::CONTEXT_VALUE => '' ]
			),
			[ 'en' => [ '', 'second alias' ] ],
			'/property/aliases',
		];

		yield 'invalid alias - empty alias at position 1' => [
			new ValidationError(
				AliasesValidator::CODE_INVALID_VALUE,
				[ AliasesValidator::CONTEXT_PATH => '/en/1', AliasesValidator::CONTEXT_VALUE => '' ]
			),
			[ 'en' => [ 'first alias', '' ] ],
			'',
		];
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

		$this->assertEquals( $expectedError, $this->newValidator()->validate( [ $language => [ $invalidAlias ] ], '' ) );
	}

	public function testGivenGetValidatedAliasesCalledBeforeValidate_throws(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedAliases();
	}

	private function newValidator(): AliasesValidator {
		return new AliasesValidator(
			$this->aliasesInLanguageValidator,
			new ValueValidatorLanguageCodeValidator( new MembershipValidator( [ 'en', 'de', 'mul' ] ) ),
			new AliasesDeserializer( new AliasesInLanguageDeserializer() ),
		);
	}

}
