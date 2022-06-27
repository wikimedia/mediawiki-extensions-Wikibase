<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use Generator;
use IContextSource;
use PHPUnit\Framework\TestCase;
use Status;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\EditEntity\EditEntity;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\RestApi\DataAccess\MediaWikiEditEntityFactoryItemUpdater;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\Tests\NewItem;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\MediaWikiEditEntityFactoryItemUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiEditEntityFactoryItemUpdaterTest extends TestCase {

	/**
	 * @dataProvider editMetadataProvider
	 */
	public function testUpdate( EditMetadata $editMetadata, string $expectedComment ): void {
		$itemToUpdate = NewItem::withId( 'Q123' )->build();
		$expectedRevisionId = 234;
		$expectedRevisionTimestamp = '20221111070707';
		$expectedRevisionItem = $this->createStub( Item::class );

		$context = $this->createStub( IContextSource::class );

		$editEntity = $this->createMock( EditEntity::class );
		$editEntity->expects( $this->once() )
			->method( 'attemptSave' )
			->with(
				$itemToUpdate,
				$expectedComment,
				$editMetadata->isBot() ? EDIT_UPDATE | EDIT_FORCE_BOT : EDIT_UPDATE,
				false,
				false,
				$editMetadata->getTags()
			)
			->willReturn( Status::newGood( [
				'revision' => new EntityRevision( $expectedRevisionItem, $expectedRevisionId, $expectedRevisionTimestamp ),
			] ) );

		$editEntityFactory = $this->createMock( MediawikiEditEntityFactory::class );
		$editEntityFactory->expects( $this->once() )
			->method( 'newEditEntity' )
			->with( $context, $itemToUpdate->getId() )
			->willReturn( $editEntity );

		$updater = new MediaWikiEditEntityFactoryItemUpdater( $context, $editEntityFactory );
		$itemRevision = $updater->update( $itemToUpdate, $editMetadata );

		$this->assertSame( $expectedRevisionItem, $itemRevision->getItem() );
		$this->assertSame( $expectedRevisionId, $itemRevision->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $itemRevision->getLastModified() );
	}

	public function editMetadataProvider(): Generator {
		$someUserProvidedComment = 'im a comment';

		yield 'bot edit' => [
			new EditMetadata( [], true, $someUserProvidedComment ),
			$someUserProvidedComment,
		];
		yield 'user edit' => [
			new EditMetadata( [], false, $someUserProvidedComment ),
			$someUserProvidedComment,
		];
		yield 'default edit comment is used if user provides none' => [
			new EditMetadata( [], false, null ),
			MediaWikiEditEntityFactoryItemUpdater::DEFAULT_COMMENT
		];
	}

	public function testGivenSavingFails_returnsNull(): void {
		$itemToUpdate = NewItem::withId( 'Q123' )->build();
		$editMeta = new EditMetadata( [ 'tag', 'also a tag' ], false, 'im a comment' );

		$context = $this->createStub( IContextSource::class );

		$editEntity = $this->createStub( EditEntity::class );
		$editEntity->method( 'attemptSave' )
			->willReturn( Status::newFatal( 'failed to save. sad times.' ) );

		$editEntityFactory = $this->createStub( MediawikiEditEntityFactory::class );
		$editEntityFactory->method( 'newEditEntity' )->willReturn( $editEntity );

		$updater = new MediaWikiEditEntityFactoryItemUpdater( $context, $editEntityFactory );

		$this->assertNull( $updater->update( $itemToUpdate, $editMeta ) );
	}

}
