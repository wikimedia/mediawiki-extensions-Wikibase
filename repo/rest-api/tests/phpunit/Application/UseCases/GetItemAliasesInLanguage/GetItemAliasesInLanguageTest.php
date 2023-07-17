<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetItemAliasesInLanguage;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageResponse;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestItemRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesInLanguageRetriever;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesInLanguage
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemAliasesInLanguageTest extends TestCase {

	private GetLatestItemRevisionMetadata $getRevisionMetadata;
	private ItemAliasesInLanguageRetriever $aliasesInLanguageRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->aliasesInLanguageRetriever = $this->createStub( ItemAliasesInLanguageRetriever::class );
	}

	public function testSuccess(): void {
		$languageCode = 'en';

		$aliasesInLanguage = new AliasesInLanguage(
			$languageCode,
			[ 'Planet Earth', 'the Earth' ]
		);

		$itemId = new ItemId( 'Q2' );
		$lastModified = '20201111070707';
		$revisionId = 2;

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModified ] );

		$this->aliasesInLanguageRetriever = $this->createMock( ItemAliasesInLanguageRetriever::class );
		$this->aliasesInLanguageRetriever->expects( $this->once() )
			->method( 'getAliasesInLanguage' )
			->with( $itemId, $languageCode )
			->willReturn( $aliasesInLanguage );

		$request = new GetItemAliasesInLanguageRequest( 'Q2', $languageCode );
		$response = $this->newUseCase()->execute( $request );
		$this->assertEquals( new GetItemAliasesInLanguageResponse( $aliasesInLanguage, $lastModified, $revisionId ), $response );
	}

	public function testGivenInvalidItemId_throws(): void {
		try {
			$this->newUseCase()->execute( new GetItemAliasesInLanguageRequest( 'X321', 'en' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_ITEM_ID, $e->getErrorCode() );
			$this->assertSame( 'Not a valid item ID: X321', $e->getErrorMessage() );
			$this->assertNull( $e->getErrorContext() );
		}
	}

	public function testGivenInvalidLanguageCode_throwsUseCaseException(): void {
		try {
			$this->newUseCase()->execute( new GetItemAliasesInLanguageRequest( 'Q123', '1e' ) );

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $useCaseEx ) {
			$this->assertSame( UseCaseError::INVALID_LANGUAGE_CODE, $useCaseEx->getErrorCode() );
			$this->assertSame( 'Not a valid language code: 1e', $useCaseEx->getErrorMessage() );
			$this->assertNull( $useCaseEx->getErrorContext() );
		}
	}

	public function testGivenLanguageCodeWithNoAliasesFor_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q2' );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 2, '20201111070707' ] );

		try {
			$this->newUseCase()->execute(
				new GetItemAliasesInLanguageRequest( $itemId->getSerialization(), 'de' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::ALIASES_NOT_DEFINED, $e->getErrorCode() );
			$this->assertSame( 'Item with the ID Q2 does not have aliases in the language: de', $e->getErrorMessage() );
			$this->assertNull( $e->getErrorContext() );
		}
	}

	public function testGivenItemNotFoundOrRedirect_throws(): void {
		$itemId = new ItemId( 'Q10' );

		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestItemRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetItemAliasesInLanguageRequest( $itemId->getSerialization(), 'en' )
			);

			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): GetItemAliasesInLanguage {
		return new GetItemAliasesInLanguage(
			$this->getRevisionMetadata,
			$this->aliasesInLanguageRetriever,
			new GetItemAliasesInLanguageValidator(
				new ItemIdValidator(),
				new LanguageCodeValidator( WikibaseRepo::getTermsLanguages()->getLanguages() )
			)
		);
	}

}
