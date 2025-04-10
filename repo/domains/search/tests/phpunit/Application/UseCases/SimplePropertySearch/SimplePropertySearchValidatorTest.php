<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Application\UseCases\SimplePropertySearch;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearchRequest;
use Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearchValidator;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;
use Wikibase\Repo\Domains\Search\Infrastructure\LanguageCodeValidator;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\MembershipValidator;
use Wikibase\Repo\Validators\NotMulValidator;
use Wikibase\Repo\Validators\TypeValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch\SimplePropertySearchValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SimplePropertySearchValidatorTest extends TestCase {

	/**
	 * @doesNotPerformAssertions
	 */
	public function testValidate_passes(): void {
		$this->newUseCaseValidator()
			->validate( new SimplePropertySearchRequest( 'search term', 'en', 10, 0 ) );
	}

	public function testGivenInvalidLanguageCode_throws(): void {
		try {
			$this->newUseCaseValidator()
				->validate( new SimplePropertySearchRequest( 'search term', 'xyz', 10, 0 ) );

			$this->fail( 'Expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals( UseCaseError::INVALID_QUERY_PARAMETER, $e->getErrorCode() );
			$this->assertEquals( "Invalid query parameter: 'language'", $e->getErrorMessage() );
			$this->assertEquals(
				[ UseCaseError::CONTEXT_PARAMETER => SimplePropertySearchValidator::LANGUAGE_QUERY_PARAM ],
				$e->getErrorContext()
			);
		}
	}

	private function newUseCaseValidator(): SimplePropertySearchValidator {
		return new SimplePropertySearchValidator( $this->newSearchLanguageValidator() );
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
