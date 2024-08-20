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
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\PatchedItemAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Infrastructure\TermValidatorFactoryAliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Infrastructure\ValueValidatorLanguageCodeValidator;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\PatchedItemAliasesValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchedItemAliasesValidatorTest extends TestCase {

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
	 *
	 * @param mixed $serialization
	 */
	public function testWithInvalidAliases( $serialization, UseCaseError $expectedError ): void {
		try {
			$this->newValidator()->validateAndDeserialize( $serialization );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function invalidAliasesProvider(): Generator {
		yield 'aliases is not an object' => [
			[ 'sequential array, not an object' ],
			UseCaseError::newPatchResultInvalidValue( '', [ 'sequential array, not an object' ] ),
		];

		yield 'aliases in language is not a list' => [
			[ 'en' => [ 'associative array' => 'not a list' ] ],
			UseCaseError::newPatchResultInvalidValue( '/en', [ 'associative array' => 'not a list' ] ),
		];

		yield 'empty alias' => [
			[ 'de' => [ '' ] ],
			UseCaseError::newPatchResultInvalidValue( '/de/0', '' ),
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

		yield 'alias too long' => [
			[ 'en' => [ str_repeat( 'A', self::LIMIT + 1 ) ] ],
			UseCaseError::newValueTooLong( '/en/0', self::LIMIT, true ),
		];

		$invalidAlias = "tab\t tab\t tab";
		yield 'alias contains invalid character' => [
			[ 'en' => [ 'valid alias', $invalidAlias ] ],
			UseCaseError::newPatchResultInvalidValue( '/en/1', $invalidAlias ),
		];

		$invalidLanguage = 'not-a-valid-language-code';
		yield 'invalid language code' => [
			[ $invalidLanguage => [ 'alias' ] ],
			UseCaseError::newPatchResultInvalidKey( '', $invalidLanguage ),
		];
	}

	private function newValidator(): PatchedItemAliasesValidator {
		$validLanguageCodes = [ 'ar', 'de', 'en', 'fr' ];
		return new PatchedItemAliasesValidator(
			new AliasesDeserializer(),
			new TermValidatorFactoryAliasesInLanguageValidator(
				new TermValidatorFactory(
					self::LIMIT,
					$validLanguageCodes,
					$this->createStub( EntityIdParser::class ),
					$this->createStub( TermsCollisionDetectorFactory::class ),
					$this->createStub( TermLookup::class ),
					$this->createStub( LanguageNameUtils::class )
				)
			),
			new ValueValidatorLanguageCodeValidator( new MembershipValidator( $validLanguageCodes ) )
		);
	}

}
