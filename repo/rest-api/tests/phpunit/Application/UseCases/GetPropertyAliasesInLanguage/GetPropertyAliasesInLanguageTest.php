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
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

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

	public function testGivenLanguageCodeWithNoAliasesFor_throwsUseCaseError(): void {
		$propertyId = 'P123';
		$languageCode = 'en';

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ 123, '20230927070707' ] );

		try {
			$this->newUseCase()->execute( new GetPropertyAliasesInLanguageRequest( $propertyId, $languageCode ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertEquals(
				new UseCaseError(
					UseCaseError::ALIASES_NOT_DEFINED,
					"Property with the ID $propertyId does not have aliases in the language: $languageCode"
				),
				$e
			);
		}
	}

	public function testGivenInvalidRequest_throws(): void {
		try {
			$this->newUseCase()->execute( new GetPropertyAliasesInLanguageRequest( 'X321', 'en' ) );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::INVALID_PROPERTY_ID, $e->getErrorCode() );
		}
	}

	private function newUseCase(): GetPropertyAliasesInLanguage {
		return new GetPropertyAliasesInLanguage(
			new TestValidatingRequestDeserializer(),
			$this->getRevisionMetadata,
			$this->aliasesRetriever
		);
	}

}
