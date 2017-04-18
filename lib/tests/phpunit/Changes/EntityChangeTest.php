<?php

namespace Wikibase\Lib\Tests\Changes;

use Diff\DiffOp\DiffOpAdd;
use MWException;
use RecentChange;
use Revision;
use RuntimeException;
use stdClass;
use TestingAccessWrapper;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\EntityChange;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\EntityChange
 * @covers Wikibase\DiffChange
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseChange
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityChangeTest extends ChangeRowTest {
	public function setUp() {
		parent::setUp();

		$wrappedStatic = TestingAccessWrapper::newFromClass( EntityChange::class );
		$wrappedStatic->centralIdLookup = null;
		$wrappedStatic->isCentralIdLookupInitialized = false;
	}

	/**
	 * @return string
	 */
	protected function getRowClass() {
		return EntityChange::class;
	}

	protected function newEntityChange( EntityId $entityId ) {
		$changeFactory = TestChanges::getEntityChangeFactory();
		$entityChange = $changeFactory->newForEntity( EntityChange::UPDATE, $entityId );

		return $entityChange;
	}

	public function changeProvider() {
		$rowClass = $this->getRowClass();

		$changes = array_filter(
			TestChanges::getChanges(),
			function( EntityChange $change ) use ( $rowClass ) {
				return is_a( $change, $rowClass );
			}
		);

		$cases = array_map(
			function( EntityChange $change ) {
				return [ $change ];
			},
			$changes );

		return $cases;
	}

	/**
	 * @dataProvider changeProvider
	 *
	 * @param EntityChange $entityChange
	 */
	public function testGetType( EntityChange $entityChange ) {
		$this->assertInternalType( 'string', $entityChange->getType() );
	}

	public function testMetadata() {
		$entityChange = $this->newEntityChange( new ItemId( 'Q13' ) );

		$entityChange->setMetadata( [
			'kittens' => 3,
			'rev_id' => 23,
			'user_text' => '171.80.182.208',
		] );
		$this->assertEquals(
			[
				'rev_id' => 23,
				'user_text' => '171.80.182.208',
				'comment' => $entityChange->getComment(), // the comment field is magically initialized
			],
			$entityChange->getMetadata()
		);

		// override some fields, keep others
		$entityChange->setMetadata( [
			'rev_id' => 25,
			'comment' => 'foo',
		] );
		$this->assertEquals(
			[
				'rev_id' => 25,
				'user_text' => '171.80.182.208',
				'comment' => 'foo', // the comment field is not magically initialized
			],
			$entityChange->getMetadata()
		);
	}

	public function testGetEmptyMetadata() {
		$entityChange = $this->newEntityChange( new ItemId( 'Q13' ) );

		$entityChange->setMetadata( [
			'kittens' => 3,
			'rev_id' => 23,
			'user_text' => '171.80.182.208',
		] );

		$entityChange->setField( 'info', [] );
		$this->assertEquals(
			[],
			$entityChange->getMetadata()
		);
	}

	public function testGetComment() {
		$entityChange = $this->newEntityChange( new ItemId( 'Q13' ) );

		$this->assertEquals( 'wikibase-comment-update', $entityChange->getComment(), 'comment' );

		$entityChange->setMetadata( [
			'comment' => 'Foo!',
		] );

		$this->assertEquals( 'Foo!', $entityChange->getComment(), 'comment' );
	}

	public function testSetMetadataFromRC() {
		$timestamp = '20140523' . '174422';

		$row = new stdClass();
		$row->rc_last_oldid = 3;
		$row->rc_this_oldid = 5;
		$row->rc_user = 7;
		$row->rc_user_text = 'Mr. Kittens';
		$row->rc_timestamp = $timestamp;
		$row->rc_cur_id = 6;
		$row->rc_bot = 1;
		$row->rc_deleted = 0;
		$row->rc_comment = 'Test!';

		$rc = RecentChange::newFromRow( $row );

		$entityChange = $this->newEntityChange( new ItemId( 'Q7' ) );
		$entityChange->setMetadataFromRC( $rc );

		$this->assertEquals( 5, $entityChange->getField( 'revision_id' ), 'revision_id' );
		$this->assertEquals( 7, $entityChange->getField( 'user_id' ), 'user_id' );
		$this->assertEquals( 'Q7', $entityChange->getObjectId(), 'object_id' );
		$this->assertEquals( $timestamp, $entityChange->getTime(), 'timestamp' );
		$this->assertEquals( 'Test!', $entityChange->getComment(), 'comment' );

		$metadata = $entityChange->getMetadata();
		$this->assertEquals( 3, $metadata['parent_id'], 'parent_id' );
		$this->assertEquals( 6, $metadata['page_id'], 'page_id' );
		$this->assertEquals( 5, $metadata['rev_id'], 'rev_id' );
		$this->assertEquals( 1, $metadata['bot'], 'bot' );
		$this->assertEquals( 'Mr. Kittens', $metadata['user_text'], 'user_text' );
	}

	/**
	 * @dataProvider provideTestAddUserMetadata
	 */
	public function testAddUserMetadata( $expectedCentralId, $repoUserId, $repoUserText ) {
		$entityChange = $this->getMockBuilder( EntityChange::class )
			->setMethods( [
				'getCentralIdLookup',
				'getType',
				'setFields',
				'setMetadata',
			] )
			->getMock();

		$entityChange->expects( $this->once() )
			->method( 'setFields' )
			->with( [
				'user_id' => $repoUserId,
			] );

		$entityChange->expects( $this->once() )
			->method( 'setMetadata' )
			->with( [
				'user_text' => $repoUserText,
				'central_user_id' => $expectedCentralId,
			] );

		$entityChange->expects( $this->any() )
			->method( 'getType' )
			->will( $this->returnValue(
				'wikibase-item~update'
			) );

		$entityChange->expects( $this->any() )
			->method( 'getCentralIdLookup' )
			->will( $this->returnValue(
				new MockRepoClientCentralIdLookup( /** isRepo= */ true )
			) );

		$entityChange = TestingAccessWrapper::newFromObject( $entityChange );
		$entityChange->addUserMetadata( $repoUserId, $repoUserText );
	}

	// See MockRepoClientCentralIdLookup
	public function provideTestAddUserMetadata() {
		return [
			[
				-3,
				3,
				'Foo'
			],

			[
				0,
				0,
				'10.11.12.13'
			],
		];
	}

	public function testSetMetadataFromUser() {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->atLeastOnce() )
			->method( 'getId' )
			->will( $this->returnValue( 7 ) );

		$user->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->will( $this->returnValue( 'Mr. Kittens' ) );

		$entityChange = $this->newEntityChange( new ItemId( 'Q7' ) );

		$entityChange->setMetadata( [
			'user_text' => 'Dobby', // will be overwritten
			'page_id' => 5, // will NOT be overwritten
		] );

		$entityChange->setMetadataFromUser( $user );

		$this->assertEquals( 7, $entityChange->getField( 'user_id' ), 'user_id' );

		$metadata = $entityChange->getMetadata();
		$this->assertEquals( 'Mr. Kittens', $metadata['user_text'], 'user_text' );
		$this->assertEquals( 5, $metadata['page_id'], 'page_id should be preserved' );
		$this->assertArrayHasKey( 'rev_id', $metadata, 'rev_id should be initialized' );
	}

	public function testSetRevisionInfo() {
		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped(
				'Need to be able to create entity content in order to test with Revision objects.'
			);
		}

		$id = new ItemId( 'Q7' );
		$item = new Item( $id );

		$entityChange = $this->newEntityChange( $id );

		$timestamp = '20140523' . '174422';

		$revision = new Revision( [
			'id' => 5,
			'page' => 6,
			'user' => 7,
			'parent_id' => 3,
			'user_text' => 'Mr. Kittens',
			'timestamp' => $timestamp,
			'content' => ItemContent::newFromItem( $item ),
			'comment' => 'Test!',
		] );

		$entityChange->setRevisionInfo( $revision );

		$this->assertEquals( 5, $entityChange->getField( 'revision_id' ), 'revision_id' );
		$this->assertEquals( 7, $entityChange->getField( 'user_id' ), 'user_id' );
		$this->assertEquals( 'Q7', $entityChange->getObjectId(), 'object_id' );
		$this->assertEquals( $timestamp, $entityChange->getTime(), 'timestamp' );
		$this->assertEquals( 'Test!', $entityChange->getComment(), 'comment' );

		$metadata = $entityChange->getMetadata();
		$this->assertEquals( 3, $metadata['parent_id'], 'parent_id' );
		$this->assertEquals( 6, $metadata['page_id'], 'page_id' );
		$this->assertEquals( 5, $metadata['rev_id'], 'rev_id' );
		$this->assertEquals( 'Mr. Kittens', $metadata['user_text'], 'user_text' );
	}

	public function testGivenEntityChangeWithoutObjectId_setRevisionInfoSetsObjectId() {
		$content = $this->getMockBuilder( ItemContent::class )
			->disableOriginalConstructor()
			->getMock();
		$content->expects( $this->once() )
			->method( 'getEntityId' )
			->will( $this->returnValue( new ItemId( 'Q1' ) ) );

		$revision = $this->getMockBuilder( Revision::class )
			->disableOriginalConstructor()
			->getMock();
		$revision->expects( $this->once() )
			->method( 'getContent' )
			->will( $this->returnValue( $content ) );

		$change = new EntityChange( [ 'info' => [], 'type' => '~' ] );
		$this->assertFalse( $change->hasField( 'object_id' ), 'precondition' );
		$change->setRevisionInfo( $revision );
		$this->assertSame( 'Q1', $change->getObjectId() );
	}

	public function testSetTimestamp() {
		$q7 = new ItemId( 'Q7' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newForEntity( EntityChange::UPDATE, $q7 );

		$timestamp = '20140523' . '174422';
		$change->setTimestamp( $timestamp );
		$this->assertEquals( $timestamp, $change->getTime() );
	}

	public function testSerializes() {
		$info = [ 'field' => 'value' ];
		$expected = '{"field":"value"}';
		$change = new EntityChange( [ 'info' => $info ] );
		$this->assertSame( $expected, $change->getSerializedInfo() );
	}

	public function testDoesNotSerializeObjects() {
		$info = [ 'array' => [ 'object' => new EntityChange() ] ];
		$change = new EntityChange( [ 'info' => $info ] );
		$this->setExpectedException( MWException::class );
		$change->getSerializedInfo();
	}

	public function testSerializeAndUnserializeInfo() {
		$info = [ 'diff' => new DiffOpAdd( '' ) ];
		$change = new EntityChange( [ 'info' => $info ] );
		$change->setField( 'info', $change->getSerializedInfo() );
		$this->assertEquals( $info, $change->getInfo() );
	}

	public function testGivenStatement_serializeInfoSerializesStatement() {
		$statement = new Statement( new PropertyNoValueSnak( 1 ) );
		$info = [ 'diff' => new DiffOpAdd( $statement ) ];
		$expected = [
			'mainsnak' => [
				'snaktype' => 'novalue',
				'property' => 'P1',
				'hash' => 'any hash',
			],
			'type' => 'statement',
			'rank' => 'normal',
			'_claimclass_' => Statement::class,
		];

		$change = new EntityChange( [ 'info' => $info ] );

		if ( !defined( 'WB_VERSION' ) ) {
			$this->setExpectedException( RuntimeException::class );
		}

		$json = $change->getSerializedInfo();
		$array = json_decode( $json, true );
		$array['diff']['newvalue']['mainsnak']['hash'] = 'any hash';
		$this->assertSame( $expected, $array['diff']['newvalue'] );
	}

	public function testGivenStatementSerialization_getInfoDeserializesStatement() {
		$data = [
			'mainsnak' => [
				'snaktype' => 'novalue',
				'property' => 'P1',
			],
			'type' => 'statement',
			'_claimclass_' => Statement::class,
		];
		$json = json_encode( [ 'diff' => [ 'type' => 'add', 'newvalue' => $data ] ] );

		$change = new EntityChange( [ 'info' => $json ] );
		$info = $change->getInfo();
		$statement = $info['diff']->getNewValue();
		$this->assertInstanceOf( Statement::class, $statement );
	}
}
