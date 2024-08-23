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
	 * @param UseCaseError $expectedError
	 * @param mixed $serialization
	 */
	public function testWithInvalidAliases( UseCaseError $expectedError, $serialization ): void {
		try {
			$this->newValidator()->validateAndDeserialize( $serialization );
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function invalidAliasesProvider(): Generator {
		yield 'invalid serialization - string' => [
			UseCaseError::newPatchResultInvalidValue( '', 'not an array' ),
			'not an array',
		];

		yield 'invalid serialization - sequential array' => [
			UseCaseError::newPatchResultInvalidValue( '', [ 'not', 'an', 'associative', 'array' ] ),
			[ 'not', 'an', 'associative', 'array' ],
		];

		yield 'invalid language code - not an allowed language code' => [
			UseCaseError::newPatchResultInvalidKey( '', 'xyz' ),
			[ 'xyz' => [ 'alias 1' ] ],
		];

		yield 'invalid aliases in language - string' => [
			UseCaseError::newPatchResultInvalidValue( '/en', 'not a list' ),
			[ 'en' => 'not a list' ],
		];

		yield 'invalid aliases in language - associative array' => [
			UseCaseError::newPatchResultInvalidValue( '/en', [ 'not' => 'a', 'sequential' => 'array' ] ),
			[ 'en' => [ 'not' => 'a', 'sequential' => 'array' ] ],
		];

		yield 'invalid alias - integer' => [
			UseCaseError::newPatchResultInvalidValue( '/en/0', 7940 ),
			[ 'en' => [ 7940, 'alias 2' ] ],
		];

		yield 'invalid alias - zero length string' => [
			UseCaseError::newPatchResultInvalidValue( '/en/1', '' ),
			[ 'en' => [ 'alias 1', '' ] ],
		];

		yield 'invalid alias - whitespace only' => [
			UseCaseError::newPatchResultInvalidValue( '/en/0', '' ),
			[ 'en' => [ "  \t  ", 'alias 1' ] ],
		];

		yield 'invalid alias - invalid characters' => [
			UseCaseError::newPatchResultInvalidValue( '/en/1', "alias \t with \t tabs" ),
			[ 'en' => [ 'alias 1', "alias \t with \t tabs" ] ],
		];

		yield 'invalid alias - too long' => [
			UseCaseError::newValueTooLong( '/en/0', self::LIMIT, true ),
			[ 'en' => [ str_repeat( 'A', self::LIMIT + 1 ) ] ],
		];

		$duplicate = 'tomato';
		yield 'invalid alias - duplicate' => [
			new UseCaseError(
				UseCaseError::PATCHED_ALIAS_DUPLICATE,
				"Aliases in language 'en' contain duplicate alias: '{$duplicate}'",
				[ UseCaseError::CONTEXT_LANGUAGE => 'en', UseCaseError::CONTEXT_VALUE => $duplicate ]
			),
			[ 'en' => [ $duplicate, $duplicate ] ],
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
