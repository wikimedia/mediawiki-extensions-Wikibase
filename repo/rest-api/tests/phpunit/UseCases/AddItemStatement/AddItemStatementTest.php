<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\UseCases\AddItemStatement;

use PHPUnit\Framework\MockObject\MockObject;
use ValueValidators\Result;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\RestApi\DataAccess\SnakValidatorStatementValidator;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Model\LatestItemRevisionMetadataResult;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatement;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementRequest;
use Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\UseCases\AddItemStatement\AddItemStatement
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddItemStatementTest extends \PHPUnit\Framework\TestCase {

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
	/**
	 * @var MockObject|GuidGenerator
	 */
	private $guidGenerator;

	protected function setUp(): void {
		parent::setUp();

		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
		$this->guidGenerator = $this->createStub( GuidGenerator::class );
	}

	public function testAddStatement(): void {
		$item = NewItem::withId( 'Q123' )->build();
		$postModificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';
		$newGuid = $item->getId() . StatementGuid::SEPARATOR . 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
		$serialization = [
			'mainsnak' => [
				'snaktype' => 'novalue',
				'property' => 'P123',
			],
			'type' => 'statement'
		];
		$editTags = [ 'some', 'tags' ];
		$isBot = false;

		$request = new AddItemStatementRequest(
			$item->getId()->getSerialization(),
			$serialization,
			$editTags,
			$isBot
		);
		$this->revisionMetadataRetriever = $this->createStub( ItemRevisionMetadataRetriever::class );
		$this->revisionMetadataRetriever->method( 'getLatestRevisionMetadata' )
			->willReturn( LatestItemRevisionMetadataResult::concreteRevision( 321, '20201111070707' ) );

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$this->guidGenerator = $this->createStub( GuidGenerator::class );
		$this->guidGenerator->method( 'newGuid' )->willReturn( $newGuid );

		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->method( 'update' )
			->with( $item, $this->equalTo( new EditMetadata( $editTags, $isBot ) ) )
			->willReturn( new ItemRevision( $item, $modificationTimestamp, $postModificationRevisionId ) );

		$useCase = $this->newUseCase();

		$response = $useCase->execute( $request );

		$this->assertNotNull( $item->getStatements()->getFirstStatementWithGuid( $newGuid ) );
		$this->assertSame( $newGuid, $response->getStatement()->getGuid() );
		$this->assertSame( $postModificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
	}

	private function newUseCase(): AddItemStatement {
		return new AddItemStatement(
			$this->newValidator(),
			$this->revisionMetadataRetriever,
			$this->itemRetriever,
			$this->itemUpdater,
			$this->guidGenerator
		);
	}

	private function newValidator(): AddItemStatementValidator {
		$snakValidator = $this->createStub( SnakValidator::class );
		$snakValidator->method( 'validateStatementSnaks' )->willReturn( Result::newSuccess() );

		return new AddItemStatementValidator(
			new SnakValidatorStatementValidator(
				WikibaseRepo::getBaseDataModelDeserializerFactory()->newStatementDeserializer(),
				$snakValidator
			)
		);
	}

}
