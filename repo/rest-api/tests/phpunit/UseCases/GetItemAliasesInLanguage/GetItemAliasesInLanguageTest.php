<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemAliasesInLanguage;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\Services\ItemAliasesInLanguageRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguage;
use Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageResponse;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemAliases\GetItemAliasesInLanguage
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemAliasesInLanguageTest extends TestCase {

	/**
	 * @var MockObject|ItemRevisionMetadataRetriever
	 */
	private $itemRevisionMetadataRetriever;

	/**
	 * @var MockObject|ItemAliasesInLanguageRetriever
	 */
	private $aliasesInLanguageRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRevisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
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

		$this->itemRevisionMetadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$this->itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( $revisionId, $lastModified ) );

		$this->aliasesInLanguageRetriever = $this->createMock( ItemAliasesInLanguageRetriever::class );
		$this->aliasesInLanguageRetriever->expects( $this->once() )
			->method( 'getAliasesInLanguage' )
			->with( $itemId, $languageCode )
			->willReturn( $aliasesInLanguage );

		$request = new GetItemAliasesInLanguageRequest( 'Q2', $languageCode );
		$response = $this->newUseCase()->execute( $request );
		$this->assertEquals( new GetItemAliasesInLanguageResponse( $aliasesInLanguage, $lastModified, $revisionId ), $response );
	}

	private function newUseCase(): GetItemAliasesInLanguage {
		return new GetItemAliasesInLanguage(
			$this->itemRevisionMetadataRetriever,
			$this->aliasesInLanguageRetriever
		);
	}

}
