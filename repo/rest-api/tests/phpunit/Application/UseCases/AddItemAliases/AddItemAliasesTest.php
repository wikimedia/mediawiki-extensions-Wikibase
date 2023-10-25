<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\AddItemAliases;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemAliases\AddItemAliases;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemAliases\AddItemAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemAliases\AddItemAliasesResponse;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item as ReadModelItem;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemRevision;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\ItemRetriever;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdater;
use Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation\TestValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AddItemAliases\AddItemAliases
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AddItemAliasesTest extends TestCase {

	private ItemRetriever $itemRetriever;
	private ItemUpdater $itemUpdater;

	protected function setUp(): void {
		parent::setUp();

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemUpdater = $this->createStub( ItemUpdater::class );
	}

	public function testCreateAliases(): void {
		$languageCode = 'en';
		$item = NewItem::withId( 'Q123' )->build();
		$aliasesToCreate = [ 'alias 1', 'alias 2' ];
		$postModificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[0] ];
		$isBot = false;
		$comment = 'potato';

		$request = new AddItemAliasesRequest(
			$item->getId()->getSerialization(),
			$languageCode,
			$aliasesToCreate,
			$editTags,
			$isBot,
			$comment
		);

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$updatedItem = new ReadModelItem(
			new Labels(),
			new Descriptions(),
			new Aliases( new AliasesInLanguage( $languageCode, $aliasesToCreate ) ),
			new StatementList()
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->method( 'update' )
			->with(
				$this->callback(
					fn( Item $item ) => $item->getAliasGroups()
						->getByLanguage( $languageCode )
						->equals( new AliasGroup( $languageCode, $aliasesToCreate ) )
				),
			)
			->willReturn( new ItemRevision( $updatedItem, $modificationTimestamp, $postModificationRevisionId ) );

		$response = $this->newUseCase()->execute( $request );

		$this->assertInstanceOf( AddItemAliasesResponse::class, $response );
		$this->assertEquals( new AliasesInLanguage( $languageCode, $aliasesToCreate ), $response->getAliases() );
		$this->assertFalse( $response->wasAddedToExistingAliasGroup() );
		$this->assertSame( $postModificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
	}

	public function testAddAliases(): void {
		$languageCode = 'en';
		$existingAliases = [ 'alias 1', 'alias 2' ];
		$item = NewItem::withId( 'Q123' )->andAliases( $languageCode, $existingAliases )->build();
		$aliasesToAdd = [ 'alias 3', 'alias 4' ];
		$postModificationRevisionId = 322;
		$modificationTimestamp = '20221111070707';
		$editTags = [ TestValidatingRequestDeserializer::ALLOWED_TAGS[0] ];
		$isBot = false;
		$comment = 'potato';

		$request = new AddItemAliasesRequest(
			$item->getId()->getSerialization(),
			$languageCode,
			$aliasesToAdd,
			$editTags,
			$isBot,
			$comment
		);

		$this->itemRetriever = $this->createStub( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )->willReturn( $item );

		$updatedAliases = array_merge( $existingAliases, $aliasesToAdd );
		$updatedItem = new ReadModelItem(
			new Labels(),
			new Descriptions(),
			new Aliases( new AliasesInLanguage( $languageCode, $updatedAliases ) ),
			new StatementList()
		);
		$this->itemUpdater = $this->createMock( ItemUpdater::class );
		$this->itemUpdater->method( 'update' )
			->with(
				$this->callback(
					fn( Item $item ) => $item->getAliasGroups()
						->getByLanguage( $languageCode )
						->equals( new AliasGroup( $languageCode, $updatedAliases ) )
				),
			)
			->willReturn( new ItemRevision( $updatedItem, $modificationTimestamp, $postModificationRevisionId ) );

		$response = $this->newUseCase()->execute( $request );

		$this->assertInstanceOf( AddItemAliasesResponse::class, $response );
		$this->assertEquals( new AliasesInLanguage( $languageCode, $updatedAliases ), $response->getAliases() );
		$this->assertTrue( $response->wasAddedToExistingAliasGroup() );
		$this->assertSame( $postModificationRevisionId, $response->getRevisionId() );
		$this->assertSame( $modificationTimestamp, $response->getLastModified() );
	}

	public function testValidationError_throwsUseCaseError(): void {
		try {
			$this->newUseCase()->execute(
				new AddItemAliasesRequest( 'Q123', 'en', [ '' ], [], false, null )
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::ALIAS_EMPTY, $e->getErrorCode() );
		}
	}

	public function testAddDuplicateAlias_throwsUseCaseError(): void {
		$itemId = new ItemId( 'Q123' );
		$aliases = new AliasGroupList( [ new AliasGroup( 'en', [ 'duplicate alias' ] ) ] );
		$item = new Item( $itemId, new Fingerprint( null, null, $aliases ) );

		$this->itemRetriever = $this->createMock( ItemRetriever::class );
		$this->itemRetriever->method( 'getItem' )
			->with( $itemId )
			->willReturn( $item );

		try {
			$this->newUseCase()->execute(
				new AddItemAliasesRequest(
					"$itemId",
					'en',
					[ 'duplicate alias' ],
					[],
					false,
					null
				)
			);
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( UseCaseError::ALIAS_DUPLICATE, $e->getErrorCode() );
		}
	}

	private function newUseCase(): AddItemAliases {
		return new AddItemAliases( $this->itemRetriever, $this->itemUpdater, new TestValidatingRequestDeserializer() );
	}
}
