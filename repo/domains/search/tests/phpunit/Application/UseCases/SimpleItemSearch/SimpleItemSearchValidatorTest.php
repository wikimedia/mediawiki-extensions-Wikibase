<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Application\UseCases\SimpleItemSearch;

use Generator;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;
use Wikibase\Repo\Domains\Search\Infrastructure\LanguageCodeValidator;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\NotMulValidator;
use Wikibase\Repo\Validators\TypeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch\SimpleItemSearchValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SimpleItemSearchValidatorTest extends TestCase {

	private const DEFAULT_LIMIT = 10;
	private const DEFAULT_OFFSET = 0;

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidate_passes(): void {
		$this->newUseCaseValidator()
			->validate( new SimpleItemSearchRequest( 'search term', 'en', self::DEFAULT_LIMIT, self::DEFAULT_OFFSET ) );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidateWithoutLimitAndOffsetParams_passe(): void {
		$this->newUseCaseValidator()
			->validate( new SimpleItemSearchRequest( 'search term', 'en' ) );
	}

	public function testGivenInvalidLanguageCode_throws(): void {
		try {
			$this->newUseCaseValidator()
				->validate( new SimpleItemSearchRequest( 'search term', 'xyz', self::DEFAULT_LIMIT, self::DEFAULT_OFFSET ) );

			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::INVALID_QUERY_PARAMETER, $e->getErrorCode() );
			$this->assertEquals( "Invalid query parameter: 'language'", $e->getErrorMessage() );
			$this->assertEquals(
				[ UseCaseError::CONTEXT_PARAMETER => SimpleItemSearchValidator::LANGUAGE_QUERY_PARAM ],
				$e->getErrorContext()
			);
		}
	}

	/**
	 * @dataProvider provideInvalidLimitAndOffset
	 */
	public function testGivenInvalidLimitAndOffset_throws(
		UseCaseError $expectedError,
		int $limit,
		int $offset
	): void {
		try {
			$this->newUseCaseValidator()
				->validate( new SimpleItemSearchRequest( 'search term', 'en', $limit, $offset ) );
			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( $expectedError, $e );
		}
	}

	public static function provideInvalidLimitAndOffset(): Generator {
		yield 'invalid limit - negative limit' => [
			UseCaseError::invalidQueryParameter( 'limit' ),
			-1,
			self::DEFAULT_OFFSET,
		];

		yield 'invalid limit - limit exceeds max (500)' => [
			UseCaseError::invalidQueryParameter( 'limit' ),
			501,
			self::DEFAULT_OFFSET,
		];

		yield 'invalid offset - negative offset' => [
			UseCaseError::invalidQueryParameter( 'offset' ),
			self::DEFAULT_LIMIT,
			-2,
		];
	}

	private function newUseCaseValidator(): SimpleItemSearchValidator {
		return new SimpleItemSearchValidator( $this->newSearchLanguageValidator() );
	}

	private function newSearchLanguageValidator(): SearchLanguageValidator {
		return new LanguageCodeValidator(
			new CompositeValidator( [
				new TypeValidator( 'string' ),
				new MembershipValidator( WikibaseRepo::getTermsLanguages()->getLanguages(), 'not-a-language' ),
				new NotMulValidator( MediaWikiServices::getInstance()->getLanguageNameUtils() ),
			] )
		);
	}
}
