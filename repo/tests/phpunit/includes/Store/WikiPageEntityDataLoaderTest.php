<?php

namespace Wikibase\Repo\Tests\Store;

use MediaWiki\Revision\RevisionAccessException;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\BlobAccessException;
use MediaWiki\Storage\BlobStore;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\Sql\WikiPageEntityDataLoader;
use Wikibase\Lib\Store\StorageException;

/**
 * @covers \Wikibase\Lib\Store\Sql\WikiPageEntityDataLoader
 *
 * @group Database
 * @group Wikibase
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class WikiPageEntityDataLoaderTest extends MediaWikiIntegrationTestCase {

	private const WIKI_ID = 'testwiki';

	public function testGivenRevisionContainingEntityData_loadEntityDataFromWikiPageRevisionReturnsEntityRevisionAndNull() {
		$revisionId = 123;
		$slotRole = SlotRecord::MAIN;

		$revision = $this->newRevisionRecord( $revisionId, $slotRole );

		$item = new Item( new ItemId( 'Q123' ) );

		$codec = $this->createMock( EntityContentDataCodec::class );
		$codec
			->method( 'decodeEntity' )
			->willReturn( $item );

		$blobStore = $this->createMock( BlobStore::class );

		$loader = new WikiPageEntityDataLoader( $codec, $blobStore, self::WIKI_ID );

		list( $entityRevision, $redirect ) = $loader->loadEntityDataFromWikiPageRevision( $revision, $slotRole, 0 );

		$this->assertInstanceOf( EntityRevision::class, $entityRevision );
		$this->assertEquals( $item, $entityRevision->getEntity() );
		$this->assertEquals( $revisionId, $entityRevision->getRevisionId() );
		$this->assertNull( $redirect );
	}

	public function testGivenRevisionContainingRedirectData_loadEntityDataFromWikiPageRevisionReturnsNullAndEntityRedirect() {
		$revisionId = 123;
		$slotRole = SlotRecord::MAIN;

		$revision = $this->newRevisionRecord( $revisionId, $slotRole );

		$sourceId = new ItemId( 'Q123' );
		$targetId = new ItemId( 'Q321' );

		$codec = $this->createMock( EntityContentDataCodec::class );
		$codec
			->method( 'decodeEntity' )
			->willReturn( null );
		$codec
			->method( 'decodeRedirect' )
			->willReturn( new EntityRedirect( $sourceId, $targetId ) );

		$blobStore = $this->createMock( BlobStore::class );

		$loader = new WikiPageEntityDataLoader( $codec, $blobStore, self::WIKI_ID );

		list( $entityRevision, $redirect ) = $loader->loadEntityDataFromWikiPageRevision( $revision, $slotRole, 0 );

		$this->assertNull( $entityRevision );
		$this->assertInstanceOf( EntityRedirect::class, $redirect );
		$this->assertEquals( $sourceId, $redirect->getEntityId() );
		$this->assertEquals( $targetId, $redirect->getTargetId() );
	}

	public function testGivenRevisionDoesNotContainEntityNorRedirectData_loadEntityDataThrows() {
		$revisionId = 123;
		$slotRole = SlotRecord::MAIN;

		$revision = $this->newRevisionRecord( $revisionId, $slotRole );

		$codec = $this->createMock( EntityContentDataCodec::class );
		$codec
			->method( 'decodeEntity' )
			->willReturn( null );
		$codec
			->method( 'decodeRedirect' )
			->willReturn( null );

		$blobStore = $this->createMock( BlobStore::class );

		$loader = new WikiPageEntityDataLoader( $codec, $blobStore, self::WIKI_ID );

		$this->expectException( StorageException::class );

		$loader->loadEntityDataFromWikiPageRevision( $revision, $slotRole, 0 );

		$this->fail( 'Should throw specific exception' );
	}

	public function testGivenRevisionDoesNotContainEntitySlotData_loadEntityDataReturnsNulls() {
		$slotRole = SlotRecord::MAIN;

		$revision = $this->createMock( RevisionRecord::class );
		$revision
			->method( 'hasSlot' )
			->with( $slotRole )
			->willReturn( false );

		$codec = $this->createMock( EntityContentDataCodec::class );
		$blobStore = $this->createMock( BlobStore::class );

		$loader = new WikiPageEntityDataLoader( $codec, $blobStore, self::WIKI_ID );

		list( $entityRevision, $redirect ) = $loader->loadEntityDataFromWikiPageRevision( $revision, $slotRole, 0 );

		$this->assertNull( $entityRevision );
		$this->assertNull( $redirect );
	}

	public function testGivenRevisionCannotBeAccessed_loadEntityDataReturnsNulls() {
		$revisionId = 123;
		$slotRole = SlotRecord::MAIN;

		$revision = $this->newRevisionRecord( $revisionId, $slotRole );
		$revision = $this->createMock( RevisionRecord::class );
		$revision
			->method( 'audienceCan' )
			->willReturn( false );

		$codec = $this->createMock( EntityContentDataCodec::class );
		$blobStore = $this->createMock( BlobStore::class );

		$loader = new WikiPageEntityDataLoader( $codec, $blobStore, self::WIKI_ID );

		list( $entityRevision, $redirect ) = $loader->loadEntityDataFromWikiPageRevision( $revision, $slotRole, 0 );

		$this->assertNull( $entityRevision );
		$this->assertNull( $redirect );
	}

	public function testGivenSlotCannotBeLoaded_loadEntityDataThrows() {
		$revisionId = 123;
		$slotRole = SlotRecord::MAIN;

		$revision = $this->newRevisionRecord( $revisionId, $slotRole );
		$revision
			->method( 'getSlot' )
			->willThrowException( new RevisionAccessException() );

		$codec = $this->createMock( EntityContentDataCodec::class );

		$blobStore = $this->createMock( BlobStore::class );

		$loader = new WikiPageEntityDataLoader( $codec, $blobStore, self::WIKI_ID );

		$this->expectException( StorageException::class );

		$loader->loadEntityDataFromWikiPageRevision( $revision, $slotRole, 0 );

		$this->fail( 'Should throw specific exception' );
	}

	public function testGivenBlobCannotBeLoaded_loadEntityDataThrows() {
		$revisionId = 123;
		$slotRole = SlotRecord::MAIN;

		$revision = $this->newRevisionRecord( $revisionId, $slotRole );

		$codec = $this->createMock( EntityContentDataCodec::class );

		$blobStore = $this->createMock( BlobStore::class );
		$blobStore
			->method( 'getBlob' )
			->willThrowException( new BlobAccessException() );

		$loader = new WikiPageEntityDataLoader( $codec, $blobStore, self::WIKI_ID );

		$this->expectException( StorageException::class );

		$loader->loadEntityDataFromWikiPageRevision( $revision, $slotRole, 0 );

		$this->fail( 'Should throw specific exception' );
	}

	private function newRevisionRecord( $revisionId, $slotRole ) {
		$revision = $this->createMock( RevisionRecord::class );
		$revision
			->method( 'getId' )
			->with( self::WIKI_ID )
			->willReturn( $revisionId );
		$revision
			->method( 'hasSlot' )
			->with( $slotRole )
			->willReturn( true );
		$revision
			->method( 'getSlot' )
			->with( $slotRole )
			->willReturn( $this->createMock( SlotRecord::class ) );
		$revision
			->method( 'audienceCan' )
			->willReturn( true );
		$revision
			->method( 'getTimestamp' )
			->willReturn( '20191017010101' );

		return $revision;
	}

}
