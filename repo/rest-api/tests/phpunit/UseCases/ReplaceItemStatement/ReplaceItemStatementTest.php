<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\ReplaceItemStatement;

use PHPUnit\Framework\MockObject\MockObject;
use ValueValidators\Result;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\DataAccess\SnakValidatorStatementValidator;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatement;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementSuccessResponse;
use Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\NewStatement;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\ReplaceItemStatement\ReplaceItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ReplaceItemStatementTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var MockObject|ItemRevisionMetadataRetriever
	 */
	private $revisionMetadataRetriever;
	/**
	 * @var MockObject|ItemRetriever
	 */
	private $itemRetriever;
	/**
	 * @var MockObject|ItemUpdater
	 */
	private $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testReplaceStatement(): void {
		$itemId = 'Q123';
		$statementId = $itemId . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$oldStatementValue = "old statement value";
		$newStatementValue = "new statement value";
		$oldStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( $statementId )
			->withValue( $oldStatementValue )
			->build();
		$newStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( $statementId )
			->withValue( $newStatementValue )
			->build();
		$item = NewItem::withId( $itemId )
			->andStatement( $oldStatement )
			->build();
		$updatedItem = NewItem::withId( $itemId )
			->andStatement( $newStatement )
			->build();
		$postModificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';
		$editTags = [ 'some', 'tags' ];
		$isBot = false;
		$comment = 'statement replaced by ' . __method__;

		$requestData = [
			'$statementId' => $statementId,
			'$statement' => $this->getStatementSerialization( $newStatement ),
			'$editTags' => $editTags,
			'$isBot' => $isBot,
			'$comment' => $comment,
			'$username' => null,
			'$itemId' => $itemId
		];
		$request = $this->newUseCaseRequest( $requestData );

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->method( 'update' )
			->with( $item, $this->equalTo( new EditMetadata( $editTags, $isBot, $comment ) ) )
			->willReturn( new ItemRevision( $updatedItem, $modificationTimestamp, $postModificationRevisionId ) );

		$useCase = $this->newUseCase();

		$response = $useCase->execute( $request );

		$this->assertInstanceOf( ReplaceItemStatementSuccessResponse::class, $response );
		$this->assertSame( $statementId, $response->getStatement()->getGuid() );
		$this->assertEquals( $newStatement, $response->getStatement() );
		$this->assertSame( $postModificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
	}

	private function newUseCase(): ReplaceItemStatement {
		return new ReplaceItemStatement(
			$this->newValidator(),
			$this->revisionMetadataRetriever,
			$this->itemRetriever,
			$this->itemUpdater
		);
	}

	private function newUseCaseRequest( array $requestData ): ReplaceItemStatementRequest {
		return new ReplaceItemStatementRequest(
			$requestData['$statementId'],
			$requestData['$statement'],
			$requestData['$editTags'],
			$requestData['$isBot'],
			$requestData['$comment'] ?? null,
			$requestData['$username'] ?? null,
			$requestData['$itemId'] ?? null
		);
	}

	private function newValidator(): ReplaceItemStatementValidator {
		$snakValidator = $this->createStub( SnakValidator::class );
		$snakValidator->method( 'validateStatementSnaks' )->willReturn( Result::newSuccess() );

		return new ReplaceItemStatementValidator(
			new SnakValidatorStatementValidator(
				WikibaseRepo::getBaseDataModelDeserializerFactory()->newStatementDeserializer(),
				$snakValidator
			)
		);
	}

	private function getStatementSerialization( Statement $statement ): array {
		$serializer = WikibaseRepo::getBaseDataModelSerializerFactory()->newStatementSerializer();
		return $serializer->serialize( $statement );
	}

}
