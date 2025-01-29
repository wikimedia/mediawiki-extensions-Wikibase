<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure;

use Generator;
use MediaWiki\Languages\LanguageNameUtils;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;
use Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryAliasesInLanguageValidator;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\TermValidatorFactoryAliasesInLanguageValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryAliasesInLanguageValidatorTest extends TestCase {

	private const MAX_LENGTH = 50;

	public function testValid(): void {
		$this->assertNull( $this->newValidator()->validate( new AliasGroup( 'en', [ 'valid alias' ] ), '/aliases' ) );
	}

	/**
	 * @dataProvider provideInvalidAliases
	 */
	public function testGivenInvalidAliases_returnsValidationError(
		AliasGroup $aliasesInLanguage,
		string $basePath,
		string $errorCode,
		array $errorContext = []
	): void {
		$this->assertEquals(
			new ValidationError( $errorCode, $errorContext ),
			$this->newValidator()->validate( $aliasesInLanguage, $basePath )
		);
	}

	public static function provideInvalidAliases(): Generator {
		$alias = str_repeat( 'a', self::MAX_LENGTH + 1 );
		yield 'alias too long' => [
			new AliasGroup( 'en', [ $alias ] ),
			'/aliases',
			AliasesInLanguageValidator::CODE_TOO_LONG,
			[
				AliasesInLanguageValidator::CONTEXT_VALUE => $alias,
				AliasesInLanguageValidator::CONTEXT_LIMIT => self::MAX_LENGTH,
				AliasesInLanguageValidator::CONTEXT_PATH => '/aliases/0',
			],
		];

		$alias = "alias with tab character \t not allowed";
		yield 'alias has invalid character' => [
			new AliasGroup( 'en', [ 'valid alias', $alias ] ),
			'/aliases/en',
			AliasesInLanguageValidator::CODE_INVALID,
			[
				AliasesInLanguageValidator::CONTEXT_VALUE => $alias,
				AliasesInLanguageValidator::CONTEXT_PATH => '/aliases/en/1',
			],
		];
	}

	private function newValidator(): TermValidatorFactoryAliasesInLanguageValidator {
		return new TermValidatorFactoryAliasesInLanguageValidator( $this->newTermValidatorFactory() );
	}

	private function newTermValidatorFactory(): TermValidatorFactory {
		return new TermValidatorFactory(
			self::MAX_LENGTH,
			WikibaseRepo::getTermsLanguages()->getLanguages(),
			$this->createStub( EntityIdParser::class ),
			$this->createStub( TermsCollisionDetectorFactory::class ),
			WikibaseRepo::getTermLookup(),
			$this->createStub( LanguageNameUtils::class )
		);
	}

}
