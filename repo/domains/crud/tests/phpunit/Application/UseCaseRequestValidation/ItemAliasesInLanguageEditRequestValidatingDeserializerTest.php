<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Application\UseCaseRequestValidation;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Repo\Domains\Crud\Application\Serialization\AliasesInLanguageDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemAliasesInLanguageEditRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemAliasesInLanguageEditRequestValidatingDeserializer;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryAliasesInLanguageValidator;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\ItemAliasesInLanguageEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemAliasesInLanguageEditRequestValidatingDeserializerTest extends TestCase {

	private const MAX_LENGTH = 40;

	/**
	 * @dataProvider provideValidAliases
	 */
	public function testGivenValidRequest_returnsAliases( array $aliases, array $expectedDeserializedAliases ): void {
		$request = $this->createStub( ItemAliasesInLanguageEditRequest::class );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getAliasesInLanguage' )->willReturn( $aliases );

		$this->assertSame(
			$expectedDeserializedAliases,
			$this->newRequestValidatingDeserializer()->validateAndDeserialize( $request )
		);
	}

	public static function provideValidAliases(): Generator {
		yield 'valid aliases pass validation' => [
			[ 'first alias', 'second alias' ],
			[ 'first alias', 'second alias' ],
		];

		yield 'white space is trimmed from aliases' => [
			[ ' space at the start', "\ttab at the start", 'space at end ', "tab at end\t", "\t  multiple spaces and tabs \t" ],
			[ 'space at the start', 'tab at the start', 'space at end', 'tab at end', 'multiple spaces and tabs' ],
		];

		yield 'duplicates are removed' => [
			[ 'first alias', 'second alias', 'third alias', 'second alias' ],
			[ 'first alias', 'second alias', 'third alias' ],
		];
	}

	/**
	 * @dataProvider provideInvalidAliases
	 */
	public function testGivenInvalidAliases_throwsUseCaseError( UseCaseError $expectedException, array $aliases ): void {
		$request = $this->createStub( ItemAliasesInLanguageEditRequest::class );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getAliasesInLanguage' )->willReturn( $aliases );

		try {
			$this->newRequestValidatingDeserializer()->validateAndDeserialize( $request );
			$this->fail( 'Expected exception not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedException, $e );
		}
	}

	public static function provideInvalidAliases(): Generator {
		yield 'invalid aliases - associative array' => [
			UseCaseError::newInvalidValue( '/aliases' ),
			[ 'not' => 'a', 'sequential' => 'array' ],
		];

		yield 'invalid aliases - empty array' => [
			UseCaseError::newInvalidValue( '/aliases' ),
			[],
		];

		yield 'invalid alias - integer' => [
			UseCaseError::newInvalidValue( '/aliases/0' ),
			[ 5675 ],
		];

		yield 'invalid alias - zero length string' => [
			UseCaseError::newInvalidValue( '/aliases/1' ),
			[ 'aka', '' ],
		];

		yield 'invalid alias - alias too long' => [
			UseCaseError::newValueTooLong( '/aliases/0', self::MAX_LENGTH ),
			[ 'this alias is too long for the configured limit' ],
		];

		yield 'invalid alias - disallowed character' => [
			UseCaseError::newInvalidValue( '/aliases/1' ),
			[ 'aka', "tabs \t not \t allowed" ],
		];
	}

	private function newRequestValidatingDeserializer(): ItemAliasesInLanguageEditRequestValidatingDeserializer {
		return new ItemAliasesInLanguageEditRequestValidatingDeserializer(
			new AliasesInLanguageDeserializer(),
			new TermValidatorFactoryAliasesInLanguageValidator(
				new TermValidatorFactory(
					self::MAX_LENGTH,
					[ 'en', 'de', 'ar', 'mul' ],
					$this->createStub( EntityIdParser::class ),
					$this->createStub( TermsCollisionDetectorFactory::class ),
					WikibaseRepo::getTermLookup(),
					$this->createStub( LanguageNameUtils::class )
				)
			)
		);
	}

}
