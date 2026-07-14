<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Application\UseCases\ItemPrefixSearch;

use Generator;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;
use Wikibase\Repo\Domains\Search\Domain\Model\User;
use Wikibase\Repo\Domains\Search\Domain\Services\PermissionChecker;
use Wikibase\Repo\Domains\Search\Infrastructure\LanguageCodeValidator;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\NotMulValidator;
use Wikibase\Repo\Validators\TypeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Search\Application\UseCases\ItemPrefixSearch\ItemPrefixSearchValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemPrefixSearchValidatorTest extends TestCase {

	private const DEFAULT_LIMIT = 10;
	private const DEFAULT_OFFSET = 0;
	private bool $hasApiHighLimits = false;

	protected function setUp(): void {
		parent::setUp();
		$this->hasApiHighLimits = false;
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidate_passes(): void {
		$this->newUseCaseValidator()
			->validate( new ItemPrefixSearchRequest(
				'search term',
				'en',
				User::newAnonymous(),
				self::DEFAULT_LIMIT,
				self::DEFAULT_OFFSET
			) );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidateWithoutLimitAndOffsetParams_passes(): void {
		$this->newUseCaseValidator()
			->validate( new ItemPrefixSearchRequest( 'search term', 'en', User::newAnonymous() ) );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidateWithLimitAtMax_passes(): void {
		$this->newUseCaseValidator()
			->validate( new ItemPrefixSearchRequest(
				'search term',
				'en',
				User::newAnonymous(),
				50,
				self::DEFAULT_OFFSET
			) );
	}

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidateWithApiHighLimits_passes(): void {
		$this->hasApiHighLimits = true;
		$this->newUseCaseValidator()
			->validate( new ItemPrefixSearchRequest(
				'search term',
				'en',
				User::withUsername( 'myUser' ),
				500,
				self::DEFAULT_OFFSET
			) );
	}

	public function testGivenInvalidLanguageCode_throws(): void {
		try {
			$this->newUseCaseValidator()
				->validate( new ItemPrefixSearchRequest(
					'search term',
					'xyz',
					User::newAnonymous(),
					self::DEFAULT_LIMIT,
					self::DEFAULT_OFFSET
				) );

			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::INVALID_QUERY_PARAMETER, $e->getErrorCode() );
			$this->assertEquals( "Invalid query parameter: 'language'", $e->getErrorMessage() );
			$this->assertEquals(
				[ UseCaseError::CONTEXT_PARAMETER => ItemPrefixSearchValidator::LANGUAGE_QUERY_PARAM ],
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
		int $offset,
		bool $hasApiHighLimits
	): void {
		$this->hasApiHighLimits = $hasApiHighLimits;

		try {
			$this->newUseCaseValidator()
				->validate( new ItemPrefixSearchRequest(
					'search term',
					'en',
					$this->createStub( User::class ),
					$limit,
					$offset,
				) );
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
			false,
		];

		yield 'invalid limit - limit exceeds max (50)' => [
			UseCaseError::invalidQueryParameter( 'limit' ),
			51,
			self::DEFAULT_OFFSET,
			false,
		];

		yield 'invalid limit - limit exceeds max api high limits (500)' => [
			UseCaseError::invalidQueryParameter( 'limit' ),
			501,
			self::DEFAULT_OFFSET,
			true,
		];

		yield 'invalid offset - negative offset' => [
			UseCaseError::invalidQueryParameter( 'offset' ),
			self::DEFAULT_LIMIT,
			-2,
			false,
		];
	}

	private function newUseCaseValidator(): ItemPrefixSearchValidator {
		$permissionChecker = $this->createStub( PermissionChecker::class );
		$permissionChecker->method( 'hasApiHighLimits' )->willReturn( $this->hasApiHighLimits );

		return new ItemPrefixSearchValidator(
			$this->newSearchLanguageValidator(),
			$permissionChecker,
			50,
			500
		);
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
