<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\GetPropertyAliasesInLanguage;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguage;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\GetPropertyAliasesInLanguageResponse;
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
	private PropertyAliasesInLanguageRetriever $aliasesRetriever;

	protected function setUp(): void {
		parent::setUp();
		$this->aliasesRetriever = $this->createStub( PropertyAliasesInLanguageRetriever::class );
	}

	public function testSuccess(): void {
		$languageCode = 'en';
		$aliases = new AliasesInLanguage( $languageCode, [ 'is a', 'example of' ] );
		$propertyId = new NumericPropertyId( 'P10' );

		$this->aliasesRetriever = $this->createMock( PropertyAliasesInLanguageRetriever::class );
		$this->aliasesRetriever->expects( $this->once() )
			->method( 'getAliasesInLanguage' )
			->with( $propertyId, $languageCode )
			->willReturn( $aliases );

		$response = $this->newUseCase()
			->execute( new GetPropertyAliasesInLanguageRequest( "$propertyId", $languageCode ) );

		$this->assertEquals( new GetPropertyAliasesInLanguageResponse( $aliases ), $response );
	}

	private function newUseCase(): GetPropertyAliasesInLanguage {
		return new GetPropertyAliasesInLanguage( $this->aliasesRetriever );
	}

}
