<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\PatchItemAliases;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\PatchedAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Infrastructure\WikibaseRepoAliasesInLanguageValidator;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\PatchedAliasesValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedAliasesValidatorTest extends TestCase {

	private const LIMIT = 50;

	/**
	 * @dataProvider validAliasesProvider
	 */
	public function testWithValidAliases( array $serialization, AliasGroupList $expectedResult ): void {
		$this->assertEquals(
			$expectedResult,
			$this->newValidator()->validateAndDeserialize( $serialization )
		);
	}

	public static function validAliasesProvider(): Generator {
		yield 'no aliases' => [ [], new AliasGroupList() ];

		$enAliases = [ 'spud', 'tater' ];
		$deAliases = [ 'Erdapfel', 'Grundbirne' ];
		yield 'valid aliases' => [
			[ 'en' => $enAliases, 'de' => $deAliases ],
			new AliasGroupList( [ new AliasGroup( 'en', $enAliases ), new AliasGroup( 'de', $deAliases ) ] ),
		];
	}

	/**
	 * @dataProvider invalidAliasesProvider
	 */
	public function testWithInvalidAliases( array $serialization, UseCaseError $expectedError ): void {
		try {
			$this->newValidator()->validateAndDeserialize( $serialization );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function invalidAliasesProvider(): Generator {
		yield 'empty alias' => [
			[ 'de' => [ '' ] ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIAS_EMPTY,
				"Changed alias for 'de' cannot be empty",
				[ UseCaseError::CONTEXT_LANGUAGE => 'de' ]
			),
		];

		$duplicate = 'tomato';
		yield 'duplicate alias' => [
			[ 'en' => [ $duplicate, $duplicate ] ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIAS_DUPLICATE,
				"Aliases in language 'en' contain duplicate alias: '{$duplicate}'",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en', UseCaseError::CONTEXT_VALUE => $duplicate ]
			),
		];

		$tooLongAlias = str_repeat( 'A', self::LIMIT + 1 );
		yield 'alias too long' => [
			[ 'en' => [ $tooLongAlias ] ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIAS_TOO_LONG,
				"Changed alias for 'en' must not be more than '" . self::LIMIT . "'",
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_VALUE => $tooLongAlias,
					UseCaseError::CONTEXT_CHARACTER_LIMIT => self::LIMIT,
				]
			),
		];

		$invalidAlias = "tab\t tab\t tab";
		yield 'alias contains invalid character' => [
			[ 'en' => [ $invalidAlias ] ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIAS_INVALID,
				"Changed alias for 'en' is invalid: '{$invalidAlias}'",
				[
					UseCaseError::CONTEXT_LANGUAGE => 'en',
					UseCaseError::CONTEXT_VALUE => $invalidAlias,
				]
			),
		];

		$invalidLanguage = 'not-a-valid-language-code';
		yield 'invalid language code' => [
			[ $invalidLanguage => [ 'alias' ] ],
			new UseCaseError(
				UseCaseError::PATCHED_ALIAS_INVALID_LANGUAGE_CODE,
				"Not a valid language code '{$invalidLanguage}' in changed aliases",
				[ UseCaseError::CONTEXT_LANGUAGE => $invalidLanguage ]
			),
		];
	}

	private function newValidator(): PatchedAliasesValidator {
		$validLanguageCodes = [ 'ar', 'de', 'en', 'fr' ];
		return new PatchedAliasesValidator(
			new AliasesDeserializer(),
			new WikibaseRepoAliasesInLanguageValidator(
				new TermValidatorFactory(
					self::LIMIT,
					$validLanguageCodes,
					$this->createStub( EntityIdParser::class ),
					$this->createStub( TermsCollisionDetectorFactory::class ),
					$this->createStub( TermLookup::class ),
					$this->createStub( LanguageNameUtils::class )
				)
			),
			new LanguageCodeValidator( $validLanguageCodes )
		);
	}

}
