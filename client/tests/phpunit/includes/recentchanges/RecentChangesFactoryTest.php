<?php

namespace Wikibase\Client\Tests\RecentChanges;

use Diff\DiffOp\Diff\Diff;
use Language;
use Title;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\EntityChange;
use Wikibase\ItemChange;
use Wikibase\SiteLinkCommentCreator;

/**
 * @covers Wikibase\Client\RecentChanges\RecentChangeFactory
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RecentChangeFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return RecentChangeFactory
	 */
	private function newRecentChangeFactory() {
		$lang = Language::factory( 'qqx' );
		$siteLinkCommentCreator = new SiteLinkCommentCreator( $lang, 'testwiki' );
		return new RecentChangeFactory( $lang, $siteLinkCommentCreator );
	}

	/**
	 * @param string $action
	 * @param EntityId $entityId
	 * @param array $fields
	 *
	 * @return EntityChange
	 */
	private function newEntityChange( $action, EntityId $entityId, Diff $diff, array $fields = null ) {
		$table = $this->getMock( 'IORMTable' );

		/** @var EntityChange $instance  */
		$instance = new ItemChange(
			$table,
			$fields,
			false
		);

		if ( !$instance->hasField( 'object_id' ) ) {
			$instance->setField( 'object_id', $entityId->getSerialization() );
		}

		if ( !$instance->hasField( 'info' ) ) {
			$instance->setField( 'info', array() );
		}

		// Note: the change type determines how the client will
		// instantiate and handle the change
		$type = 'wikibase-' . $entityId->getEntityType() . '~' . $action;
		$instance->setField( 'type', $type );
		$instance->setDiff( $diff );

		return $instance;
	}

	/**
	 * @param int $ns
	 * @param string $text
	 * @param int $pageId
	 * @param int $revId
	 * @param int $length
	 *
	 * @return Title
	 */
	private function newTitle( $ns, $text, $pageId, $revId, $length ) {
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $ns ) );

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( str_replace( ' ', '_', $text ) ) );

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( $pageId ) );

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( $revId ) );

		$title->expects( $this->any() )
			->method( 'getLength' )
			->will( $this->returnValue( $length ) );

		return $title;
	}

	public function provideNewRecentChange() {
		$target = $this->newTitle( NS_MAIN, 'RecentChangeFactoryTest', 7, 77, 210 );

		$fields = array(
			'id' => '13',
			'time' => '20150202030303',
		);
		$metadata = array(
			'rev_id' => 2,
			'parent_id' => 3,
			'bot' => false,
			'user_text' => 'RecentChangeFactoryTestUser',
			'comment' => 'Actual Comment'
		);

		$emptyDiff = new ItemDiff();
		$change = $this->newEntityChange( 'change', new ItemId( 'Q17' ), $emptyDiff, $fields );
		$change->setMetadata( $metadata );

		$fields = $change->getFields();
		unset( $fields['info'] );

		$metadata = array_merge( $fields, $change->getMetadata() );
		$metadata['entity_type'] = 'item';

		$targetAttr = array(
			'rc_namespace' => $target->getNamespace(),
			'rc_title' => $target->getDBkey(),
			'rc_old_len' => $target->getLength(),
			'rc_new_len' => $target->getLength(),
			'rc_this_oldid' => $target->getLatestRevID(),
			'rc_last_oldid' => $target->getLatestRevID(),
			'rc_cur_id' => $target->getArticleID(),
			'rc_deleted' => false,
		);

		$changeAttr = array(
			'rc_user' => 0,
			'rc_user_text' => 'RecentChangeFactoryTestUser',
			'rc_type' => RC_EXTERNAL,
			'rc_minor' => true, // for now, always consider these minor
			'rc_bot' => $metadata['bot'],
			'rc_patrolled' => true,
			'rc_params' => serialize( array(
				'wikibase-repo-change' => $metadata,
				//'comment-html' => 'Generated Comment HTML', // later
			) ),
			'rc_comment' => $metadata['comment'],
			'rc_timestamp' => $metadata['time'],
			'rc_log_action' => '',
			'rc_source' => 'wb'
		);

		$preparedAttr = array(
			'rc_user' => 0,
			'rc_user_text' => 'HungryKitten',
			'rc_type' => RC_EXTERNAL,
			'rc_minor' => true, // for now, always consider these minor
			'rc_bot' => false,
			'rc_patrolled' => true,
			'rc_params' => serialize( array(
				'wikibase-repo-change' => array(
					'rev_id' => 7,
					'parent_id' => 5,
					'time' => '20150606050505',
				),
				//'comment-html' => 'Override Comment HTML', // later
			) ),
			'rc_comment' => 'Override Comment',
			'rc_timestamp' => '20150606050505',
			'rc_log_action' => '',
			'rc_source' => 'wb'
		);

		return array(
			'no prepared' => array(
				array_merge( $changeAttr, $targetAttr ),
				$change,
				$target,
				null
			),

			'use prepared' => array(
				array_merge( $preparedAttr, $targetAttr ),
				$change,
				$target,
				$preparedAttr
			),

			//TODO:
			//'sitelink change' => array(),
			//'composite change' => array(),
		);
	}

	/**
	 * @dataProvider provideNewRecentChange
	 */
	public function testNewRecentChange( array $expected, EntityChange $change, Title $target, array $preparedAttribs = null ) {
		$factory = $this->newRecentChangeFactory();

		$rc = $factory->newRecentChange( $change, $target, $preparedAttribs );

		$this->assertRCEquals( $expected, $rc->getAttributes() );
	}

	private function assertRCEquals( array $expected, array $actual ) {
		if ( isset( $expected['rc_params'] ) ) {
			$this->assertArrayHasKey( 'rc_params', $actual );

			$expectedParams = unserialize( $expected['rc_params'] );
			$actualParams = unserialize( $actual['rc_params'] );

			unset( $expected['rc_params'] );
			unset( $actual['rc_params'] );

			ksort( $expectedParams );
			ksort( $actualParams );
			$this->assertEquals( $expectedParams, $actualParams, 'rc_params' );
		} else {
			$this->assertArrayNotHasKey( 'rc_params', $actual );
		}

		ksort( $expected );
		ksort( $actual );
		$this->assertEquals( $expected, $actual, 'attributes' );
	}
}
