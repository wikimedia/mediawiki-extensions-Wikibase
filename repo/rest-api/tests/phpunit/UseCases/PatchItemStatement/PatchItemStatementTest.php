<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\PatchItemStatement;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\Domain\Services\StatementPatcher;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatement;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatementSuccessResponse;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\PatchItemStatement\PatchItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PatchItemStatementTest extends TestCase {

	/**
	 * @var MockObject|ItemRetriever
	 */
	private $itemRetriever;

	/**
	 * @var MockObject|StatementPatcher
	 */
	private $statementPatcher;

	/**
	 * @var MockObject|ItemUpdater
	 */
	private $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->statementPatcher = $this->createStub( StatementPatcher::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testPatchItemStatement(): void {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$oldStatementValue = "old statement value";
		$newStatementValue = "new statement value";
		$statement = NewStatement::someValueFor( 'P123' )
			->withGuid( $statementId )
			->withValue( $oldStatementValue )
			->build();

		$patchedStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( $statementId )
			->withValue( $newStatementValue )
			->build();
		$item = NewItem::withId( $itemId )
			->andStatement( $statement )
			->build();
		$updatedItem = NewItem::withId( $itemId )
			->andStatement( $patchedStatement )
			->build();
		$postModificationRevisionId = 567;
		$modificationTimestamp = '20221111070707';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$patch = [
			[
				'op' => 'replace',
				'path' => '/mainsnak/datavalue/value',
				'value' => $newStatementValue
			],
		];

		$requestData = [
			'$statementId' => $statementId,
			'$patch' => $patch,
			'$editTags' => $editTags,
			'$isBot' => $isBot,
			'$comment' => $comment,
			'$username' => null,
			'$itemId' => $itemId
		];

		$request = $this->newUseCaseRequest( $requestData );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever
			->method( 'getItem' )
			->with( $itemId )
			->willReturn( $item );

		$this->statementPatcher = $this->createStub( StatementPatcher::class );
		$this->statementPatcher
			->method( 'patch' )
			->with( $statement, $patch )
			->willReturn( $patchedStatement );

		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->itemUpdater
			->method( 'update' )
			->with( $item, new EditMetadata( $editTags, $isBot, $comment ) )
			->willReturn( new ItemRevision( $updatedItem, $modificationTimestamp, $postModificationRevisionId ) );

		$response = $this->newUseCase()->execute( $request );

		$this->assertInstanceOf( PatchItemStatementSuccessResponse::class, $response );
		$this->assertEquals( $response->getStatement(), $patchedStatement );
		$this->assertSame( $response->getLastModified(), $modificationTimestamp );
		$this->assertSame( $response->getRevisionId(), $postModificationRevisionId );
	}

	private function newUseCase(): PatchItemStatement {
			return new PatchItemStatement(
				new StatementGuidParser( new ItemIdParser() ),
				$this->itemRetriever,
				$this->statementPatcher,
				$this->itemUpdater
			);
	}

	private function newUseCaseRequest( array $requestData ): PatchItemStatementRequest {
		return new PatchItemStatementRequest(
			$requestData['$statementId'],
			$requestData['$patch'],
			$requestData['$editTags'] ?? [],
			$requestData['$isBot'] ?? false,
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null,
			$requestData['$itemId'] ?? null
		);
	}

}
