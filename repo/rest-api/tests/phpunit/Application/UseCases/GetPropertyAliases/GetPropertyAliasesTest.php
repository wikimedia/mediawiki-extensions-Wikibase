<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyAliases;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetLatestPropertyRevisionMetadata;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliases;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliasesResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseException;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\GetPropertyAliases
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetPropertyAliasesTest extends TestCase {

	private GetLatestPropertyRevisionMetadata $getRevisionMetadata;
	private PropertyAliasesRetriever $propertyAliasesRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->propertyAliasesRetriever = $this->createStub( PropertyAliasesRetriever::class );
	}

	public function testSuccess(): void {
		$propertyId = 'P123';
		$lastModified = '20201111070707';
		$revisionId = 2;

		$aliases = new Aliases(
			new AliasesInLanguage( 'en', [ 'en-alias1', 'en-alias2' ] ),
			new AliasesInLanguage( 'de', [ 'de-alias1', 'de-alias2' ] )
		);

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )->willReturn( [ $revisionId, $lastModified ] );

		$this->propertyAliasesRetriever = $this->createMock( PropertyAliasesRetriever::class );
		$this->propertyAliasesRetriever->expects( $this->once() )
			->method( 'getAliases' )
			->with( new NumericPropertyId( $propertyId ) )
			->willReturn( $aliases );

		$response = $this->newUseCase()->execute( new GetPropertyAliasesRequest( $propertyId ) );
		$this->assertEquals( new GetPropertyAliasesResponse( $aliases, $lastModified, $revisionId ), $response );
	}

	public function testGivenPropertyNotFound_throws(): void {
		$propertyId = new NumericPropertyId( 'P123' );

		$expectedException = $this->createStub( UseCaseException::class );

		$this->getRevisionMetadata = $this->createStub( GetLatestPropertyRevisionMetadata::class );
		$this->getRevisionMetadata->method( 'execute' )
			->willThrowException( $expectedException );

		try {
			$this->newUseCase()->execute(
				new GetPropertyAliasesRequest( $propertyId->getSerialization() )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseException $e ) {
			$this->assertSame( $expectedException, $e );
		}
	}

	public function newUseCase(): GetPropertyAliases {
		return new GetPropertyAliases( $this->getRevisionMetadata, $this->propertyAliasesRetriever );
	}

}
