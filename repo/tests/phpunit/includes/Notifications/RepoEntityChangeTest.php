<?php

use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\User\UserIdentityValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Tests\Changes\ChangeRowTest;
use Wikibase\Lib\Tests\Changes\TestChanges;
use Wikibase\Repo\Notifications\RepoEntityChange;

/**
 * Class RepoEntityChangeTest
 * @group Wikibase
 * @license GPL-2.0-or-later
 */
class RepoEntityChangeTest extends ChangeRowTest {
	private function newEntityChange( EntityId $entityId ): RepoEntityChange {
		$change = new RepoEntityChange;
		$change->setEntityId( $entityId );
		$change->setEntityId( $entityId );
		$type = 'wikibase-' . $entityId->getEntityType() . '~' . EntityChange::UPDATE;
		$change->setField( 'type', $type );
		return $change;
	}

	public function testGivenEntityChangeWithoutObjectId_setRevisionInfoThrowsException() {
		$revision = $this->createMock( RevisionRecord::class );

		$change = new RepoEntityChange( [ 'info' => [], 'type' => '~' ] );
		$this->assertFalse( $change->hasField( 'object_id' ), 'precondition' );
		$this->expectException( Exception::class );
		$change->setRevisionInfo( $revision, 3 );
	}

	public function testSetMetadataFromRC() {
		$timestamp = '20140523' . '174422';

		$row = (object)[
			'rc_last_oldid' => 3,
			'rc_this_oldid' => 5,
			'rc_user' => 7,
			'rc_user_text' => 'Mr. Kittens',
			'rc_timestamp' => $timestamp,
			'rc_cur_id' => 6,
			'rc_bot' => 1,
			'rc_deleted' => 0,
			// The faked-up RecentChange row needs to have the proper fields for
			// MediaWiki core change Ic3a434c0.
			'rc_comment' => 'Test!',
			'rc_comment_text' => 'Test!',
			'rc_comment_data' => null,
		];

		$rc = RecentChange::newFromRow( $row );

		$entityChange = $this->newEntityChange( new ItemId( 'Q7' ) );
		$entityChange->setMetadataFromRC( $rc, 8 );

		$this->assertSame( 5, $entityChange->getField( 'revision_id' ), 'revision_id' );
		$this->assertSame( 7, $entityChange->getField( 'user_id' ), 'user_id' );
		$this->assertSame( 'Q7', $entityChange->getObjectId(), 'object_id' );
		$this->assertSame( $timestamp, $entityChange->getTime(), 'timestamp' );
		$this->assertSame( 'Test!', $entityChange->getComment(), 'comment' );

		$metadata = $entityChange->getMetadata();
		$this->assertSame( 8, $metadata['central_user_id'], 'central_user_id' );
		$this->assertSame( 3, $metadata['parent_id'], 'parent_id' );
		$this->assertSame( 6, $metadata['page_id'], 'page_id' );
		$this->assertSame( 5, $metadata['rev_id'], 'rev_id' );
		$this->assertSame( 1, $metadata['bot'], 'bot' );
		$this->assertSame( 'Mr. Kittens', $metadata['user_text'], 'user_text' );
	}

	public function testSetRevisionInfo() {
		$id = new ItemId( 'Q7' );
		$entityChange = $this->newEntityChange( $id );
		$timestamp = '20140523' . '174422';

		$revRecord = new MutableRevisionRecord( Title::newFromTextThrow( 'Required workaround' ) );
		$revRecord->setParentId( 3 );
		$revRecord->setComment( CommentStoreComment::newUnsavedComment( 'Test!' ) );
		$revRecord->setTimestamp( $timestamp );
		$revRecord->setId( 5 );
		$revRecord->setUser( new UserIdentityValue( 7, 'Mr. Kittens' ) );
		$revRecord->setPageId( 6 );

		$entityChange->setRevisionInfo( $revRecord, 8 );

		$this->assertSame( 5, $entityChange->getField( 'revision_id' ), 'revision_id' );
		$this->assertSame( 7, $entityChange->getField( 'user_id' ), 'user_id' );
		$this->assertSame( 'Q7', $entityChange->getObjectId(), 'object_id' );
		$this->assertSame( $timestamp, $entityChange->getTime(), 'timestamp' );
		$this->assertSame( 'Test!', $entityChange->getComment(), 'comment' );

		$metadata = $entityChange->getMetadata();
		$this->assertSame( 8, $metadata['central_user_id'], 'central_user_id' );
		$this->assertSame( 3, $metadata['parent_id'], 'parent_id' );
		$this->assertSame( 6, $metadata['page_id'], 'page_id' );
		$this->assertSame( 5, $metadata['rev_id'], 'rev_id' );
		$this->assertSame( 'Mr. Kittens', $metadata['user_text'], 'user_text' );
	}

	public function testSetTimestamp() {
		$q7 = new ItemId( 'Q7' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newForEntity( EntityChange::UPDATE, $q7 );

		$timestamp = '20140523' . '174422';
		$change->setTimestamp( $timestamp );
		$this->assertSame( $timestamp, $change->getTime() );
	}
}
