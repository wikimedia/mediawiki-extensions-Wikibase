<?php

namespace Wikibase\Lib\Tests\Changes;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use RecentChange;
use Revision;
use RuntimeException;
use stdClass;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
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
 * @since 0.3
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityChangeTest extends ChangeRowTest {

	/**
	 * @since 0.4
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

	public function entityProvider() {
		return array_map(
			function( EntityDocument $entity ) {
				return array( $entity );
			},
			TestChanges::getEntities()
		);
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
				return array( $change );
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

		$entityChange->setMetadata( array(
			'kittens' => 3,
			'rev_id' => 23,
			'user_text' => '171.80.182.208',
		) );
		$this->assertEquals(
			array(
				'rev_id' => 23,
				'user_text' => '171.80.182.208',
				'comment' => $entityChange->getComment(), // the comment field is magically initialized
			),
			$entityChange->getMetadata()
		);

		// override some fields, keep others
		$entityChange->setMetadata( array(
			'rev_id' => 25,
			'comment' => 'foo',
		) );
		$this->assertEquals(
			array(
				'rev_id' => 25,
				'user_text' => '171.80.182.208',
				'comment' => 'foo', // the comment field is not magically initialized
			),
			$entityChange->getMetadata()
		);
	}

	public function testGetEmptyMetadata() {
		$entityChange = $this->newEntityChange( new ItemId( 'Q13' ) );

		$entityChange->setMetadata( array(
			'kittens' => 3,
			'rev_id' => 23,
			'user_text' => '171.80.182.208',
		) );

		$entityChange->setField( 'info', array() );
		$this->assertEquals(
			array(),
			$entityChange->getMetadata()
		);
	}

	/**
	 * @dataProvider changeProvider
	 * @since 0.4
	 */
	public function testToString( EntityChange $entityChange ) {
		$string = $entityChange->__toString();

		$id = strtolower( $entityChange->getEntityId()->getSerialization() );
		$type = $entityChange->getType();

		$this->assertContains( "'object_id' => '$id'", $string, "missing entity ID $id" );
		$this->assertContains( "'type' => '$type'", $string, "missing type $type" );
	}

	public function testGetComment() {
		$entityChange = $this->newEntityChange( new ItemId( 'Q13' ) );

		$this->assertEquals( 'wikibase-comment-update', $entityChange->getComment(), 'comment' );

		$entityChange->setMetadata( array(
			'comment' => 'Foo!',
		) );

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
		$this->assertEquals( 'q7', $entityChange->getObjectId(), 'object_id' );
		$this->assertEquals( $timestamp, $entityChange->getTime(), 'timestamp' );
		$this->assertEquals( 'Test!', $entityChange->getComment(), 'comment' );

		$metadata = $entityChange->getMetadata();
		$this->assertEquals( 3, $metadata['parent_id'], 'parent_id' );
		$this->assertEquals( 6, $metadata['page_id'], 'page_id' );
		$this->assertEquals( 5, $metadata['rev_id'], 'rev_id' );
		$this->assertEquals( 1, $metadata['bot'], 'bot' );
		$this->assertEquals( 'Mr. Kittens', $metadata['user_text'], 'user_text' );
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

		$entityChange->setMetadata( array(
			'user_text' => 'Dobby', // will be overwritten
			'page_id' => 5, // will NOT be overwritten
		) );

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

		$revision = new Revision( array(
			'id' => 5,
			'page' => 6,
			'user' => 7,
			'parent_id' => 3,
			'user_text' => 'Mr. Kittens',
			'timestamp' => $timestamp,
			'content' => ItemContent::newFromItem( $item ),
			'comment' => 'Test!',
		) );

		$entityChange->setRevisionInfo( $revision );

		$this->assertEquals( 5, $entityChange->getField( 'revision_id' ), 'revision_id' );
		$this->assertEquals( 7, $entityChange->getField( 'user_id' ), 'user_id' );
		$this->assertEquals( 'q7', $entityChange->getObjectId(), 'object_id' );
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

		$change = new EntityChange( array( 'info' => array(), 'type' => '~' ) );
		$this->assertFalse( $change->hasField( 'object_id' ), 'precondition' );
		$change->setRevisionInfo( $revision );
		$this->assertSame( 'q1', $change->getObjectId() );
	}

	public function testSetTimestamp() {
		$q7 = new ItemId( 'Q7' );

		$changeFactory = TestChanges::getEntityChangeFactory();
		$change = $changeFactory->newForEntity( EntityChange::UPDATE, $q7 );

		$timestamp = '20140523' . '174422';
		$change->setTimestamp( $timestamp );
		$this->assertEquals( $timestamp, $change->getTime() );
	}

	public function testSerializeAndUnserializeInfo() {
		$info = array( 'diff' => new DiffOpAdd( '' ) );
		$change = new EntityChange();
		$this->assertEquals( $info, $change->unserializeInfo( $change->serializeInfo( $info ) ) );
	}

	public function testGivenStatement_arrayalizeObjectsReturnsSerialization() {
		$statement = new Statement( new PropertyNoValueSnak( 1 ) );
		$expected = array(
			'mainsnak' => array(
				'snaktype' => 'novalue',
				'property' => 'P1',
				'hash' => '2d7ef41c913ec99eb249645e154e77670090db68',
			),
			'type' => 'statement',
			'rank' => 'normal',
			'_claimclass_' => Statement::class,
		);

		$change = new EntityChange();

		if ( !defined( 'WB_VERSION' ) ) {
			$this->setExpectedException( RuntimeException::class );
		}

		$array = $change->arrayalizeObjects( $statement );
		$this->assertSame( $expected, $array );
	}

	public function testGivenNonStatement_arrayalizeObjectsReturnsOriginal() {
		$data = 'foo';
		$change = new EntityChange();
		$this->assertSame( $data, $change->arrayalizeObjects( $data ) );
	}

	public function testGivenStatementSerialization_objectifyArraysReturnsStatement() {
		$data = array(
			'mainsnak' => array(
				'snaktype' => 'novalue',
				'property' => 'P1',
			),
			'type' => 'statement',
			'_claimclass_' => Statement::class,
		);

		$change = new EntityChange();
		$statement = $change->objectifyArrays( $data );
		$this->assertInstanceOf( Statement::class, $statement );
	}

	public function testGivenNonStatementSerialization_objectifyArraysReturnsOriginal() {
		$data = array( 'foo' );
		$change = new EntityChange();
		$this->assertSame( $data, $change->objectifyArrays( $data ) );
	}

}
