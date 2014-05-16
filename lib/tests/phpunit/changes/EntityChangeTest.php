<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityChange;
use Wikibase\EntityContent;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityRedirect;

/**
 * @covers Wikibase\EntityChange
 *
 * @since 0.3
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class EntityChangeTest extends DiffChangeTest {

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		// don't include entity data, it's skipped during serialization!
		// $this->allowedInfoKeys[] = 'entity';

		$this->allowedChangeKeys = array( // see TestChanges::getChanges()
			'property-creation',
			'property-deletion',
			'property-set-label',
			'item-creation',
			'item-deletion',
			'set-dewiki-sitelink',
			'set-enwiki-sitelink',
			'change-dewiki-sitelink',
			'change-enwiki-sitelink',
			'remove-dewiki-sitelink',
			'set-de-label',
			'set-en-label',
			'set-en-aliases',
			'add-claim',
			'remove-claim',
			'item-deletion-linked',
			'remove-enwiki-sitelink',
		);
	}

	/**
	 * @see ORMRowTest::getRowClass
	 * @since 0.4
	 * @return string
	 */
	protected function getRowClass() {
		return 'Wikibase\EntityChange';
	}

	/**
	 * Returns the name of the class of the entities under test.
	 *
	 * @since 0.4
	 * @return string
	 */
	protected function getEntityClass() {
		return 'Wikibase\Entity';
	}


	public function entityProvider() {
		$entityClass = $this->getEntityClass(); // PHP fail

		$entities = array_filter(
			TestChanges::getEntities(),
			function( Entity $entity ) use ( $entityClass ) {
				return is_a( $entity, $entityClass );
			}
		);

		$cases = array_map(
			function( Entity $entity ) {
				return array( $entity );
			},
			$entities
		);

		return $cases;
	}

	public function changeProvider() {
		$rowClass = $this->getRowClass(); // PHP fail

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

	/**
	 * @dataProvider entityProvider
	 *
	 * @param Entity $entity
	 */
	public function testSetAndGetEntity( Entity $entity ) {
		$class = $this->getRowClass();
        $entityChange = $class::newFromUpdate( EntityChange::UPDATE, null, $entity );
		$entityChange->setEntity( $entity );
		$this->assertEquals( $entity, $entityChange->getEntity() );
	}

	/**
	 * @dataProvider changeProvider
	 * @since 0.3
	 */
	public function testMetadata( EntityChange $entityChange ) {
		$entityChange->setMetadata( array(
			'kittens' => 3,
			'rev_id' => 23,
			'user_text' => '171.80.182.208',
		) );
		$this->assertEquals(
			array(
				'rev_id' => 23,
				'user_text' => '171.80.182.208',
				'comment' => $entityChange->getComment(),
			),
			$entityChange->getMetadata()
		);
	}

	/**
	 * @dataProvider changeProvider
	 * @since 0.3
	 */
	public function testGetEmptyMetadata( EntityChange $entityChange ) {
		$entityChange->setField( 'info', array() );
		$this->assertEquals(
			false,
			$entityChange->getMetadata()
		);
	}

	/**
	 * @dataProvider changeProvider
	 * @since 0.4
	 */
	public function testToString( EntityChange $entityChange ) {
		$s = "$entityChange"; // magically calls __toString()

		$id = $entityChange->getEntityId()->getPrefixedId();
		$type = $entityChange->getType();

		$this->assertTrue( stripos( $s, $id ) !== false, "missing entity ID $id" );
		$this->assertTrue( stripos( $s, $type ) !== false, "missing type $type" );
	}

	/**
	 * @dataProvider entityProvider
	 */
	public function testNewForEntity( Entity $entity ) {
		$actual = EntityChange::newForEntity( EntityChange::ADD, $entity->getId() );

		$this->assertInstanceOf( $this->getRowClass(), $actual );
	}

	public function provideUpdates() {
		$e1 = new Item( array() );
		$e1->setId( new ItemId( 'Q10' ) );
		$e1->setLabel( 'en', 'Foo' );
		$e1->setLabel( 'ja', '\u30d3\u30fc\u30eb' );

		$e2 = new Item( array() );
		$e2->setId( new ItemId( 'Q10' ) );
		$e2->setLabel( 'en', 'Boo' );

		return array(
			'add' => array(
				'$action' => EntityChange::ADD,
				'$oldEntity' => null,
				'$newEntity' => $e2,
				'$fields' => array( 'info' => array(
					'test' => 'test',
				) ),
				'$expected' => EntityChange::newForEntity(
						EntityChange::ADD,
						$e2->getId(),
						array()
					),
			),
			'remove' => array(
				'$action' => EntityChange::REMOVE,
				'$oldEntity' => $e1,
				'$newEntity' => null,
				'$fields' => array( 'info' => array(
					'test' => 'test',
				) ),
				'$expected' => EntityChange::newForEntity(
						EntityChange::REMOVE,
						$e1->getId(),
						array()
					),
			),
			'update' => array(
				'$action' => EntityChange::UPDATE,
				'$oldEntity' => $e1,
				'$newEntity' => $e2,
				'$fields' => array( 'info' => array(
					'test' => 'test',
				) ),
				'$expected' => EntityChange::newForEntity(
						EntityChange::UPDATE,
						$e1->getId(),
						array()
					),
			),
		);
	}

	/**
	 * @dataProvider provideUpdates
	 */
	public function testNewFromUpdate(
		$action,
		Entity $oldEntity = null,
		Entity $newEntity = null,
		array $fields = null,
		EntityChange $expected
	) {
		$actual = EntityChange::newFromUpdate( $action, $oldEntity, $newEntity, $fields, $expected );
		$this->assertEntityChangeEquals( $expected, $actual );
	}

	public function provideContentUpdates() {
		$q10 = new ItemId( 'Q10' );

		$e1 = new Item( array() );
		$e1->setId( $q10 );
		$e1->setLabel( 'en', 'Foo' );
		$c1 = ItemContent::newFromItem( $e1 );

		$e2 = new Item( array() );
		$e2->setId( $q10 );
		$e2->setLabel( 'en', 'Boo' );
		$c2 = ItemContent::newFromItem( $e2 );

		$r1 = ItemContent::newFromRedirect(
			new EntityRedirect( $q10, new ItemId( 'Q21' ) ),
			$c2->getContentHandler()->getTitleForId( $q10 )
		);

		$r2 = ItemContent::newFromRedirect(
			new EntityRedirect( $q10, new ItemId( 'Q22' ) ),
			$c2->getContentHandler()->getTitleForId( $q10 )
		);

		return array(
			'update' => array(
				'$action' => EntityChange::UPDATE,
				'$oldContent' => $c1,
				'$newContent' => $c2,
				'$fields' => array( 'info' => array(
					'test' => 'test',
				) ),
				'$expected' => EntityChange::newForEntity(
						EntityChange::UPDATE,
						$e1->getId(),
						array()
					),
			),
			'to redirect' => array(
				'$action' => EntityChange::UPDATE,
				'$oldContent' => $c1,
				'$newContent' => $r2,
				'$fields' => array( 'info' => array(
					'test' => 'test',
				) ),
				'$expected' => EntityChange::newForEntity(
						EntityChange::REMOVE,
						$r2->getEntityId(),
						array()
					),
			),
			'from redirect' => array(
				'$action' => EntityChange::UPDATE,
				'$oldContent' => $r1,
				'$newContent' => $c2,
				'$fields' => array( 'info' => array(
					'test' => 'test',
				) ),
				'$expected' => EntityChange::newForEntity(
						EntityChange::RESTORE,
						$e2->getId(),
						array()
					),
			),
			'change redirect' => array(
				'$action' => EntityChange::UPDATE,
				'$oldContent' => $r1,
				'$newContent' => $r2,
				'$fields' => array( 'info' => array(
					'test' => 'test',
				) ),
				'$expected' => null,
			),
		);
	}

	/**
	 * @dataProvider provideContentUpdates
	 */
	public function testNewFromContentUpdate(
		$action,
		EntityContent $oldContent = null,
		EntityContent $newContent = null,
		array $fields = null,
		EntityChange $expected = null
	) {
		$actual = EntityChange::newFromContentUpdate( $action, $oldContent, $newContent, $fields, $expected );
		$this->assertEntityChangeEquals( $expected, $actual );
	}

	protected function assertEntityChangeEquals( EntityChange $expected = null, EntityChange $actual = null ) {
		if ( $expected === null ) {
			// redundant...
			$this->assertSame( $expected, $actual );
		} else {
			$this->assertNotNull( $actual );
			$this->assertEquals( $expected->getEntityType(), $actual->getEntityType(), 'getEntityType' );
			$this->assertEquals( $expected->getId(), $actual->getId(), 'getId' );
			$this->assertEquals( $expected->getAction(), $actual->getAction(), 'getAction' );
			$this->assertEquals( $expected->getComment(), $actual->getComment(), 'getComment' );
			$this->assertEquals( $expected->getEntityId(), $actual->getEntityId(), 'getEntityId' );
			$this->assertEquals( $expected->getMetadata(), $actual->getMetadata(), 'getMetadata' );
			$this->assertEquals( $expected->getType(), $actual->getType(), 'getType' );
		}
	}
}
