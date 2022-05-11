<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\GetItemStatement;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemStatementRetriever;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement;
use Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatementRequest;
use Wikibase\Repo\Tests\NewStatement;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\GetItemStatement\GetItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class GetItemStatementTest extends TestCase {

	/**
	 * @var Stub|ItemRevisionMetadataRetriever
	 */
	private $itemRevisionMetadataRetriever;

	/**
	 * @var Stub|ItemStatementRetriever
	 */
	private $statementRetriever;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRevisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->statementRetriever = $this->createStub( ItemStatementRetriever::class );
	}

	public function testGetItemStatement(): void {
		$itemId = new ItemId( 'Q123' );
		$revision = 987;
		$lastModified = '20201111070707';
		$statementId = $itemId . StatementGuid::SEPARATOR . "c48c32c3-42b5-498f-9586-84608b88747c";
		$statementPropertyId = 'P123';
		$statementValue = 'potato';

		$statement = NewStatement::forProperty( $statementPropertyId )
			->withValue( $statementValue )
			->withGuid( $statementId )
			->build();

		$this->itemRevisionMetadataRetriever = $this->createMock( ItemRevisionMetadataRetriever::class );
		$this->itemRevisionMetadataRetriever->expects( $this->once() )
			->method( 'getLatestRevisionMetadata' )
			->with( $itemId )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( $revision, $lastModified ) );

		$this->statementRetriever = $this->createMock( ItemStatementRetriever::class );
		$this->statementRetriever->expects( $this->once() )
			->method( 'getStatement' )
			->with( $statementId )
			->willReturn( $statement );

		$response = $this->newUseCase()->execute(
			new GetItemStatementRequest( $statement->getGuid() )
		);

		$serializedStatement = $response->getSerializedStatement();
		$this->assertSame( $statementId, $serializedStatement['id'] );
		$this->assertSame( $statementValue, $serializedStatement['mainsnak']['datavalue']['value'] );

		$this->assertSame( $revision, $response->getRevisionId() );
		$this->assertSame( $lastModified, $response->getLastModified() );
	}

	private function newUseCase(): GetItemStatement {
		return new GetItemStatement(
			$this->statementRetriever,
			$this->itemRevisionMetadataRetriever,
			WikibaseRepo::getBaseDataModelSerializerFactory()->newStatementSerializer()
		);
	}

}
