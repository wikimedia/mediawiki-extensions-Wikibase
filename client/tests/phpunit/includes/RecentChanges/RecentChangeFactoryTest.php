<?php

namespace Wikibase\Client\Tests\RecentChanges;

use Diff\DiffOp\Diff\Diff;
use Diff\MapDiffer;
use Language;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Diff\ItemDiffer;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Changes\EntityDiffChangedAspects;
use Wikibase\Lib\Tests\Changes\MockRepoClientCentralIdLookup;
use SiteLookup;
use Wikimedia\TestingAccessWrapper;
use Title;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\EntityChange;
use Wikibase\ItemChange;
use Wikibase\Client\RecentChanges\SiteLinkCommentCreator;

/**
 * @covers Wikibase\Client\RecentChanges\RecentChangeFactory
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class RecentChangeFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return RecentChangeFactory
	 */
	private function newRecentChangeFactoryHelper( $centralIdLookup ) {
		$siteLookup = $this->getMock( SiteLookup::class );

		$lang = Language::factory( 'qqx' );
		$siteLinkCommentCreator = new SiteLinkCommentCreator( $lang, $siteLookup, 'testwiki' );
		return new RecentChangeFactory( $lang, $siteLinkCommentCreator, $centralIdLookup );
	}

	private function newRecentChangeFactory() {
		return $this->newRecentChangeFactoryHelper(
			new MockRepoClientCentralIdLookup( /** isRepo= */ false )
		);
	}

	/**
	 * @param string $action
	 * @param EntityId $entityId
	 * @param Diff $diff
	 * @param array $fields
	 *
	 * @return EntityChange
	 */
	private function newEntityChange( $action, EntityId $entityId, Diff $diff, array $fields ) {
		/** @var EntityChange $instance  */
		$instance = new ItemChange( $fields );

		$instance->setEntityId( $entityId );

		if ( !$instance->hasField( 'info' ) ) {
			$instance->setField( 'info', [] );
		}

		// Note: the change type determines how the client will
		// instantiate and handle the change
		$type = 'wikibase-' . $entityId->getEntityType() . '~' . $action;
		$instance->setField( 'type', $type );
		$instance->setCompactDiff( EntityDiffChangedAspects::newFromEntityDiff( $diff ) );

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
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( $ns ) );

		$title->expects( $this->any() )
			->method( 'getDBkey' )
			->will( $this->returnValue( str_replace( ' ', '_', $text ) ) );

		// XXX: This assumes NS_MAIN. Getting namespace names right nicely is hard, they depend on the lang.
		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( $text ) );

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

		$fields = [
			'id' => '13',
			'user_id' => 3,
			'time' => '20150202030303',
		];
		$metadata = [
			'rev_id' => 2,
			'parent_id' => 3,
			'user_text' => 'RecentChangeFactoryTestUser',
			'comment' => 'Actual Comment'
		];

		$emptyDiff = new ItemDiff();
		$change = $this->newEntityChange( 'change', new ItemId( 'Q17' ), $emptyDiff, $fields );
		$change->setMetadata( $metadata );

		$itemDiffer = new ItemDiffer();
		$emptyItem = new Item( new ItemId( 'Q17' ), null, null, null );
		$oldItem = $emptyItem->copy();
		$oldItem->addSiteLink( new SiteLink( 'testwiki', 'RecentChangeFactoryTest' ) );

		$newItem = $emptyItem->copy();
		$newItem->addSiteLink( new SiteLink( 'testwiki', 'Bar' ) );

		$siteLinkDiff = $itemDiffer->diffItems( $oldItem, $newItem );
		$siteLinkChange = $this->newEntityChange( 'change', new ItemId( 'Q17' ), $siteLinkDiff, $fields );
		$siteLinkChange->setMetadata( $metadata );

		$targetAttr = [
			'rc_namespace' => $target->getNamespace(),
			'rc_title' => $target->getDBkey(),
			'rc_old_len' => $target->getLength(),
			'rc_new_len' => $target->getLength(),
			'rc_this_oldid' => $target->getLatestRevID(),
			'rc_last_oldid' => $target->getLatestRevID(),
			'rc_cur_id' => $target->getArticleID(),
		];

		$changeAttr = [
			'rc_user' => 0,
			'rc_user_text' => 'RecentChangeFactoryTestUser',
			'rc_type' => RC_EXTERNAL,
			'rc_minor' => true, // for now, always consider these minor
			'rc_bot' => false,
			'rc_patrolled' => true,
			'rc_params' => serialize( [
				'wikibase-repo-change' => $metadata + $fields + [
					'object_id' => 'Q17',
					'type' => 'wikibase-item~change',
					'entity_type' => 'item',
				],
				// 'comment-html' => 'Generated Comment HTML', // later
			] ),
			'rc_comment' => $metadata['comment'],
			'rc_timestamp' => $fields['time'],
			'rc_log_action' => '',
			'rc_log_type' => null,
			'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			'rc_deleted' => false,
			'rc_new' => false,
		];

		$preparedAttr = [
			'rc_user' => 0,
			'rc_user_text' => 'HungryKitten',
			'rc_type' => RC_EXTERNAL,
			'rc_minor' => true, // for now, always consider these minor
			'rc_bot' => false,
			'rc_patrolled' => true,
			'rc_params' => serialize( [
				'wikibase-repo-change' => [
					'rev_id' => 7,
					'parent_id' => 5,
					'time' => '20150606050505',
				],
				'comment-html' => 'Override Comment HTML',
			] ),
			'rc_comment' => 'prepared Comment',
			'rc_timestamp' => '20150606050505',
			'rc_log_action' => '',
			'rc_log_type' => null,
			'rc_source' => RecentChangeFactory::SRC_WIKIBASE,
			'rc_deleted' => false,
		];

		$siteLinkChangeExpected_currentPage = array_merge( $preparedAttr, $targetAttr );
		$siteLinkChangeExpected_currentPage['rc_comment'] = '(wikibase-comment-unlink)';

		$siteLinkChangeExpected_otherPage = array_merge( $preparedAttr, $targetAttr );
		$siteLinkChangeExpected_otherPage['rc_title'] = 'RecentChangeFactoryTest-OtherPage';

		return [
			'no prepared' => [
				array_merge( $changeAttr, $targetAttr ),
				$change,
				$target,
				null
			],

			'use prepared' => [
				array_merge( $preparedAttr, $targetAttr ),
				$change,
				$target,
				$preparedAttr
			],

			'sitelink change, affects current page' => [
				$siteLinkChangeExpected_currentPage,
				$siteLinkChange,
				$target,
				$preparedAttr
			],

			'sitelink change, does not affect current page' => [
				$siteLinkChangeExpected_otherPage,
				$siteLinkChange,
				$this->newTitle( NS_MAIN, 'RecentChangeFactoryTest-OtherPage', 7, 77, 210 ),
				$preparedAttr
			],

			// 'composite change' => array(),
		];
	}

	/**
	 * @dataProvider provideNewRecentChange
	 */
	public function testNewRecentChange( array $expected, EntityChange $change, Title $target, array $preparedAttribs = null ) {
		$factory = $this->newRecentChangeFactory();

		$rc = $factory->newRecentChange( $change, $target, $preparedAttribs );

		$this->assertRCEquals( $expected, array_intersect_key( $rc->getAttributes(), $expected ) );
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

	/**
	 * @param string $action
	 * @param Diff $diff
	 * @param array $fields
	 * @param array $metadata
	 *
	 * @return EntityChange
	 */
	private function makeItemChangeFromMetaData(
		$action,
		Diff $diff,
		array $fields,
		array $metadata
	) {
		$fields = array_merge( [
			'id' => '13',
			'time' => '20150202030303',
			'user_id' => 0,
		], $fields );

		$metadata = array_merge( [
			'rev_id' => 2,
			'parent_id' => 3,
			'bot' => false,
			'user_text' => 'RecentChangeFactoryTestUser',
			'comment' => 'Actual Comment'
		], $metadata );

		if ( isset( $fields['info']['changes'] ) ) {
			foreach ( $fields['info']['changes'] as &$innerChange ) {
				if ( is_array( $innerChange ) ) {
					$innerDiff = new ItemDiff();
					$innerChange = $this->makeItemChangeFromMetaData(
						$action,
						$innerDiff,
						$innerChange['fields'],
						$innerChange['metadata']
					);
				}
			}
		}

		$change = $this->newEntityChange( $action, new ItemId( 'Q17' ), $diff, $fields );
		$change->setMetadata( $metadata );

		return $change;
	}

	/**
	 * @dataProvider provideNewRecentChange_summary
	 */
	public function testNewRecentChange_summary(
		$expectedComment,
		$action,
		Diff $diff,
		array $fields,
		array $metadata
	) {
		// @todo: also check pre-generated HTML when I5439a76c is merged

		$change = $this->makeItemChangeFromMetaData( $action, $diff, $fields, $metadata );

		$target = $this->newTitle( NS_MAIN, 'RecentChangeFactoryTest', 7, 77, 210 );

		$factory = $this->newRecentChangeFactory();
		$rc = $factory->newRecentChange( $change, $target );

		$this->assertEquals( $expectedComment, $rc->getAttribute( 'rc_comment' ) );
	}

	private function makeItemDiff( array $from, array $to ) {
		$differ = new MapDiffer( true );
		$diffOps = $differ->doDiff(
			$from,
			$to
		);

		return new ItemDiff( $diffOps );
	}

	public function provideNewRecentChange_summary() {
		$emptyDiff = new ItemDiff();

		// TODO: special cases:
		//   page connected by edit
		//   page connected by creation
		//   page connected by undeletion
		//   page disconnected by edit
		//   page disconnected by deletion

		$linksEmpty = [
			'links' => []
		];

		$linksDewikiDummy = [
			'links' => [
				'dewiki' => [ 'name' => 'Dummy' ]
			]
		];

		$linksDewikiBummy = [
			'links' => [
				'dewiki' => [ 'name' => 'Bummy' ]
			]
		];

		return [
			'repo comment' => [
				'/* set-de-label:1| */ bla bla',
				'change',
				$emptyDiff,
				[ 'user_id' => 1 ],
				[
					'comment' => '/* set-de-label:1| */ bla bla',
				]
			],
			'sitelink update' => [
				'(wikibase-comment-sitelink-change: dewiki:Dummy, dewiki:Bummy)',
				'change',
				$this->makeItemDiff( $linksDewikiDummy, $linksDewikiBummy ),
				[ 'user_id' => 1 ],
				[
					'comment' => '/* IGNORE-KITTENS:1| */ SILLY KITTENS',
				]
			],
			'sitelink added' => [
				'(wikibase-comment-sitelink-add: dewiki:Bummy)',
				'change',
				$this->makeItemDiff( $linksEmpty, $linksDewikiBummy ),
				[ 'user_id' => 1 ],
				[
					'comment' => '/* IGNORE-KITTENS:1| */ SILLY KITTENS',
				]
			],
			'sitelink removed' => [
				'(wikibase-comment-sitelink-remove: dewiki:Dummy)',
				'change',
				$this->makeItemDiff( $linksDewikiDummy, $linksEmpty ),
				[ 'user_id' => 1 ],
				[
					'comment' => '/* IGNORE-KITTENS:1| */ SILLY KITTENS',
				]
			],
			'composite change' => [
				'/* set-de-description:1| */ Fuh(semicolon-separator)/* set-en-description:1| */ Foo',
				'change',
				$emptyDiff,
				[
					'info' => [ 'changes' => [
						[
							'fields' => [],
							'metadata' => [
								'comment' => '/* set-de-description:1| */ Fuh',
							],
						],
						[
							'fields' => [],
							'metadata' => [
								'comment' => '/* set-en-description:1| */ Foo',
							],
						],
					] ],
					'user_id' => 1,
				],
				[]
			],
		];
	}

	public function testNewRecentChange_no_summary() {
		$change = $this->makeItemChangeFromMetaData(
			'change',
			new ItemDiff(),
			[],
			[
				'comment' => ''  // repo sent no comment
			]
		);

		$target = $this->newTitle( NS_MAIN, 'RecentChangeFactoryTest', 7, 77, 210 );

		$factory = $this->newRecentChangeFactory();

		\MediaWiki\suppressWarnings();
		$rc = $factory->newRecentChange( $change, $target );
		\MediaWiki\restoreWarnings();

		$expectedComment = '(wikibase-comment-update)';
		$this->assertEquals( $expectedComment, $rc->getAttribute( 'rc_comment' ) );
	}

	/**
	 * @dataProvider provideGetClientUserId
	 */
	public function testGetClientUserId( $expectedClientUserId, $centralIdLookup, $repoUserId, $metadata ) {
		$recentChangeFactory = $this->newRecentChangeFactoryHelper( $centralIdLookup );

		$recentChangeFactory = TestingAccessWrapper::newFromObject( $recentChangeFactory );
		$clientUserId = $recentChangeFactory->getClientUserId( $repoUserId, $metadata );
		$this->assertSame(
			$expectedClientUserId,
			$clientUserId
		);
	}

	//  * central = -1, repo = 1, client = 2

	public function provideGetClientUserId() {
		$centralIdLookup = new MockRepoClientCentralIdLookup(
			/** isRepo= */ false
		);

		return [
			'Logged out on repo' => [
				0,
				$centralIdLookup,
				0,
				[ 'central_user_id' => 0 ],
			],

			'No central ID lookup (client wiki is not connected to central ' .
			'user system' => [
				0,
				null,
				3,
				[ 'central_user_id' => -3 ],
			],

			'0 central user ID although there is a repo user ID, e.g.' .
			' Wikibase repo user not attached' => [
				0,
				$centralIdLookup,
				5,
				[ 'central_user_id' => 0 ],
			],

			'No central user ID because it is from a row created before ' .
			'central_user_id was saved' =>
			[
				0,
				$centralIdLookup,
				7,
				[],
			],

			'Invalid central ID so client user ID is 0' => [
				0,
				$centralIdLookup,
				8,
				[ 'central_user_id' => 3 ],
			],

			'Happy path; user ID is fully mapped' => [
				8,
				$centralIdLookup,

				// Would be 4 for mock, but it doesn't use
				// this other than == or != 0.
				9,

				[ 'central_user_id' => -4 ]
			],
		];
	}

}
