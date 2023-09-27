<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyAliasesInLanguage;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguageResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesInLanguageRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguage
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesInLanguageTest extends TestCase {
	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;
	private PropertyAliasesInLanguageRetriever $aliasesRetriever;

	protected function setUp(): void {
		parent::setUp();
		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->aliasesRetriever = $this->createStub( PropertyAliasesInLanguageRetriever::class );
	}

	public function testSuccess(): void {
		$languageCode = 'en';
		$aliases = new AliasesInLanguage( $languageCode, [ 'is a', 'example of' ] );

		$propertyId = new NumericPropertyId( 'P10' );
		$lastModified = '20201111070707';
		$revisionId = 2;

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModified ] );

		$this->aliasesRetriever = $this->createMock( PropertyAliasesInLanguageRetriever::class );
		$this->aliasesRetriever->expects( $this->once() )
			->method( 'getAliasesInLanguage' )
			->with( $propertyId, $languageCode )
			->willReturn( $aliases );

		$response = $this->newUseCase()
			->execute( new GetPropertyAliasesInLanguageRequest( "$propertyId", $languageCode ) );

		$this->assertEquals( new GetPropertyAliasesInLanguageResponse( $aliases, $lastModified, $revisionId ), $response );
	}

	public function testGivenPropertyNotFound_throws(): void {
		$expectedException = $this->createStub( UseCaseError::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute( new GetPropertyAliasesInLanguageRequest( 'P999999', 'en' ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	private function newUseCase(): GetPropertyAliasesInLanguage {
		return new GetPropertyAliasesInLanguage( $this->getRevisionMetadata, $this->aliasesRetriever );
	}

}
