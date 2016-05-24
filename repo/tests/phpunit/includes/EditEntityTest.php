<?php

namespace Wikibase\Test;

use FauxRequest;
use MediaWikiTestCase;
use ObjectCache;
use PHPUnit_Framework_MockObject_Matcher_Invocation;
use ReflectionMethod;
use RequestContext;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\EditEntity;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers Wikibase\EditEntity
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EditEntity
 *
 * @group Database
 *        ^--- needed just because we are using Title objects.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EditEntityTest extends MediaWikiTestCase {

	private function getUser( $name ) {
		$user = User::newFromName( $name );

		if ( $user->getId() === 0 ) {
			$user = User::createNew( $user->getName() );
		}

		return $user;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookup() {
		$titleLookup = $this->getMock( EntityTitleLookup::class );

		$titleLookup->expects( $this->any() )
			->method( 'getTitleForID' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::makeTitle(
					NS_MAIN,
					$id->getEntityType() . '/' . $id->getSerialization()
				);
			} ) );

		$titleLookup->expects( $this->any() )
			->method( 'getNamespaceForType' )
			->will( $this->returnValue( NS_MAIN ) );

		return $titleLookup;
	}

	/**
	 * @param bool[]|null $permissions
	 *
	 * @return EntityPermissionChecker
	 */
	private function getEntityPermissionChecker( array $permissions = null ) {
		$permissionChecker = $this->getMock( EntityPermissionChecker::class );

		$checkAction = function( $user, $action ) use ( $permissions ) {
			if ( $permissions === null
				|| ( isset( $permissions[$action] ) && $permissions[$action] )
			) {
				return Status::newGood( true );
			} else {
				return Status::newFatal( 'badaccess-group0' );
			}
		};

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntity' )
			->will( $this->returnCallback( $checkAction ) );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityType' )
			->will( $this->returnCallback( $checkAction ) );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnCallback( $checkAction ) );

		return $permissionChecker;
	}

	private function getMockEditFitlerHookRunner(
		Status $status = null,
		PHPUnit_Framework_MockObject_Matcher_Invocation $expects = null
	) {
		if ( is_null( $status ) ) {
			$status = Status::newGood();
		}
		if ( is_null( $expects ) ) {
			$expects = $this->any();
		}
		$runner = $this->getMockBuilder( EditFilterHookRunner::class )
			->setMethods( array( 'run' ) )
			->disableOriginalConstructor()
			->getMock();
		$runner->expects( $expects )
			->method( 'run' )
			->will( $this->returnValue( $status ) );
		return $runner;
	}

	/**
	 * @param MockRepository $mockRepository
	 * @param EntityDocument $entity
	 * @param EntityTitleLookup $titleLookup
	 * @param User|null $user
	 * @param bool $baseRevId
	 * @param bool[]|null $permissions map of actions to bool, indicating which actions are allowed.
	 * @param EditFilterHookRunner|null $editFilterHookRunner
	 *
	 * @return EditEntity
	 */
	private function makeEditEntity(
		MockRepository $mockRepository,
		EntityDocument $entity,
		EntityTitleLookup $titleLookup,
		User $user = null,
		$baseRevId = false,
		array $permissions = null,
		$editFilterHookRunner = null
	) {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );

		if ( $user === null ) {
			$user = User::newFromName( 'EditEntityTestUser' );
		}
		if ( $editFilterHookRunner === null ) {
			$editFilterHookRunner = $this->getMockEditFitlerHookRunner();
		}

		$permissionChecker = $this->getEntityPermissionChecker( $permissions );

		return new EditEntity(
			$titleLookup,
			$mockRepository,
			$mockRepository,
			$permissionChecker,
			new EntityDiffer(),
			$entity,
			$user,
			$editFilterHookRunner,
			$baseRevId,
			$context
		);
	}

	/**
	 * @return MockRepository
	 */
	private function getMockRepository() {
		$repo = new MockRepository();

		$user = $this->getUser( 'EditEntityTestUser1' );
		$otherUser = $this->getUser( 'EditEntityTestUser2' );

		/* @var Item $item */
		$item = new Item( new ItemId( 'Q17' ) );
		$item->setLabel( 'en', 'foo' );
		$repo->putEntity( $item, 10, 0, $user );

		$item = new Item( new ItemId( 'Q17' ) );
		$item->setLabel( 'en', 'bar' );
		$repo->putEntity( $item, 11, 0, $otherUser );

		$item = new Item( new ItemId( 'Q17' ) );
		$item->setLabel( 'en', 'bar' );
		$item->setLabel( 'de', 'bar' );
		$repo->putEntity( $item, 12, 0, $user );

		$item = new Item( new ItemId( 'Q17' ) );
		$item->setLabel( 'en', 'test' );
		$item->setLabel( 'de', 'bar' );
		$item->setDescription( 'en', 'more testing' );
		$repo->putEntity( $item, 13, 0, $user );

		return $repo;
	}

	public function provideHasEditConflict() {
		/*
		 * Test Revisions:
		 * #0: label: array( 'en' => 'foo' );
		 * #1: label: array( 'en' => 'bar' ); // by other user
		 * #2: label: array( 'en' => 'bar', 'de' => 'bar' );
		 * #3: label: array( 'en' => 'test', 'de' => 'bar' ), description: array( 'en' => 'more testing' );
		*/

		return array(
			array( // #0: case I: no base rev given.
				null,  // input data
				EntityRevisionLookup::LATEST_FROM_MASTER,  // base rev
				false, // expected conflict
				false, // expected fix
			),
			array( // #1: case II: base rev == current
				null,  // input data
				13,     // base rev
				false, // expected conflict
				false, // expected fix
			),
			array( // #2: case IIIa: user was last to edit
				array( // input data
					'label' => array( 'de' => 'yarrr' ),
				),
				12,     // base rev
				true,  // expected conflict
				true,  // expected fix
				array( // expected data
					'label' => array( 'en' => 'test', 'de' => 'yarrr' ),
				)
			),
			array( // #3: case IIIb: user was last to edit, but intoduces a new operand
				array( // input data
					'label' => array( 'de' => 'yarrr' ),
				),
				11,     // base rev
				true,  // expected conflict
				false, // expected failure, diff operand change
				null
			),
			array( // #4: case IV: patch applied
				array( // input data
					'label' => array( 'nl' => 'test', 'fr' => 'frrrrtt' ),
				),
				10,     // base rev
				true,  // expected conflict
				true,  // expected fix
				array( // expected data
					'label' => array( 'de' => 'bar', 'en' => 'test',
					                  'nl' => 'test', 'fr' => 'frrrrtt' ),
				)
			),
			array( // #5: case V: patch failed, expect a conflict
				array( // input data
					'label' => array( 'nl' => 'test', 'de' => 'bar' ),
				),
				10,     // base rev
				true,  // expected conflict
				false, // expected fix
				null   // expected data
			),
			array( // #6: case VI: patch is empty, keep current (not base)
				array( // input data
					'label' => array( 'en' => 'bar', 'de' => 'bar' ),
				),
				12,     // base rev
				true,  // expected conflict
				true,  // expected fix
				array( // expected data
					'label' => array( 'en' => 'test', 'de' => 'bar' ),
					'description' => array( 'en' => 'more testing' )
				)
			),
		);
	}

	/**
	 * @dataProvider provideHasEditConflict
	 */
	public function testHasEditConflict(
		array $inputData = null,
		$baseRevisionId,
		$expectedConflict,
		$expectedFix,
		array $expectedData = null
	) {
		$repo = $this->getMockRepository();

		$entityId = new ItemId( 'Q17' );
		$revision = $repo->getEntityRevision( $entityId, $baseRevisionId );
		/** @var Item $item */
		$item = $revision->getEntity();

		// NOTE: The user name must be the one used in getMockRepository().
		$user = $this->getUser( 'EditEntityTestUser1' );

		// change entity ----------------------------------
		if ( $inputData === null ) {
			$item = new Item( $item->getId() );
		} else {
			if ( !empty( $inputData['label'] ) ) {
				foreach ( $inputData['label'] as $k => $v ) {
					$item->setLabel( $k, $v );
				}
			}

			if ( !empty( $inputData['description'] ) ) {
				foreach ( $inputData['description'] as $k => $v ) {
					$item->setDescription( $k, $v );
				}
			}

			if ( !empty( $inputData['aliases'] ) ) {
				foreach ( $inputData['aliases'] as $k => $v ) {
					$item->setAliases( $k, $v );
				}
			}
		}

		// save entity ----------------------------------
		$titleLookup = $this->getEntityTitleLookup();
		$editEntity = $this->makeEditEntity( $repo, $item, $titleLookup, $user, $baseRevisionId );

		$conflict = $editEntity->hasEditConflict();
		$this->assertEquals( $expectedConflict, $conflict, 'hasEditConflict()' );

		if ( $conflict ) {
			$fixed = $editEntity->fixEditConflict();
			$this->assertEquals( $expectedFix, $fixed, 'fixEditConflict()' );
		}

		if ( $expectedData !== null ) {
			/** @var Item $newEntity */
			$newEntity = $editEntity->getNewEntity();
			$data = $this->fingerprintToPartialArray( $newEntity->getFingerprint() );

			foreach ( $expectedData as $key => $expectedValue ) {
				$actualValue = $data[$key];
				$this->assertArrayEquals( $expectedValue, $actualValue, false, true );
			}
		}
	}

	private function fingerprintToPartialArray( Fingerprint $fingerprint ) {
		return array(
			'label' => $fingerprint->getLabels()->toTextArray(),
			'description' => $fingerprint->getDescriptions()->toTextArray(),
		);
	}

	public function provideAttemptSaveWithLateConflict() {
		return array(
			array( true, true ),
			array( false, false ),
		);
	}

	/**
	 * @dataProvider provideAttemptSaveWithLateConflict
	 */
	public function testAttemptSaveWithLateConflict( $baseRevId, $expectedConflict ) {
		$repo = $this->getMockRepository();

		$user = $this->getUser( 'EditEntityTestUser' );

		// create item
		$entity = new Item( new ItemId( 'Q42' ) );
		$entity->setLabel( 'en', 'Test' );

		$repo->putEntity( $entity, 0, 0, $user );

		// begin editing the entity
		$entity = new Item( new ItemId( 'Q42' ) );
		$entity->setLabel( 'en', 'Trust' );

		$titleLookup = $this->getEntityTitleLookup();
		$editEntity = $this->makeEditEntity( $repo, $entity, $titleLookup, $user, $baseRevId );
		$editEntity->getLatestRevision(); // make sure EditEntity has page and revision

		$this->assertEquals( $baseRevId !== false, $editEntity->doesCheckForEditConflicts(), 'doesCheckForEditConflicts()' );

		// create independent Entity instance for the same entity, and modify and save it
		$entity2 = new Item( new ItemId( 'Q42' ) );
		$entity2->setLabel( 'en', 'Toast' );

		$user2 = $this->getUser( 'EditEntityTestUser2' );
		$repo->putEntity( $entity2, 0, 0, $user2 );

		// now try to save the original edit. The conflict should still be detected
		$token = $user->getEditToken();
		$status = $editEntity->attemptSave( "Testing", EDIT_UPDATE, $token );

		$id = $entity->getId()->getSerialization();

		if ( $status->isOK() ) {
			$statusMessage = "Status ($id): OK";
		} else {
			$statusMessage = "Status ($id): " . $status->getWikiText();
		}

		$this->assertNotEquals( $expectedConflict, $status->isOK(),
			"Saving should have failed late if and only if a base rev was provided.\n$statusMessage" );

		$this->assertEquals( $expectedConflict, $editEntity->hasError(),
			"Saving should have failed late if and only if a base rev was provided.\n$statusMessage" );

		$this->assertEquals( $expectedConflict, $status->hasMessage( 'edit-conflict' ),
			"Saving should have failed late if and only if a base rev was provided.\n$statusMessage" );

		$this->assertEquals( $expectedConflict, $editEntity->showErrorPage(),
			"If and only if there was an error, an error page should be shown.\n$statusMessage" );
	}

	public function testErrorPage_DoesNotDoubleEscapeHtmlCharacters() {
		$repo = $this->getMockRepository();
		$permissions = array();
		$context = new RequestContext();
		// Cannot reuse makeEditEntity because we need the access the context
		$editEntity = new EditEntity(
			$this->getEntityTitleLookup(),
			$repo,
			$repo,
			$this->getEntityPermissionChecker( $permissions ),
			new EntityDiffer(),
			new Item(),
			$this->getUser( 'EditEntityTestUser' ),
			$this->getMockEditFitlerHookRunner(),
			false,
			$context
		);

		$editEntity->checkEditPermissions();
		$editEntity->showErrorPage();
		$html = $context->getOutput()->getHTML();

		$this->assertContains( '<li>', $html, 'Unescaped HTML' );
		$this->assertNotContains( '&amp;lt;', $html, 'No double escaping' );
	}

	public function dataCheckEditPermissions() {
		return array(
			array( #0: edit allowed for new item
				array( 'read' => true, 'edit' => true, 'createpage' => true ),
				false,
				true,
			),
			array( #3: edit not allowed for existing item
				array( 'read' => true, 'edit' => false ),
				true,
				false,
			),
		);
	}

	private function prepareItemForPermissionCheck( User $user, MockRepository $mockRepository, $create ) {
		$item = new Item();

		if ( $create ) {
			$item->setLabel( 'de', 'Test' );
			$mockRepository->putEntity( $item, 0, 0, $user );
		}

		return $item;
	}

	/**
	 * @dataProvider dataCheckEditPermissions
	 */
	public function testCheckEditPermissions( $permissions, $create, $expectedOK ) {
		$repo = $this->getMockRepository();

		$user = $this->getUser( 'EditEntityTestUser' );
		$item = $this->prepareItemForPermissionCheck( $user, $repo, $create );

		$titleLookup = $this->getEntityTitleLookup();
		$edit = $this->makeEditEntity( $repo, $item, $titleLookup, $user, false, $permissions );
		$edit->checkEditPermissions();

		$this->assertEquals( $expectedOK, $edit->getStatus()->isOK() );
		$this->assertNotEquals( $expectedOK, $edit->hasError( EditEntity::PERMISSION_ERROR ) );
	}

	/**
	 * @dataProvider dataCheckEditPermissions
	 */
	public function testAttemptSavePermissions( $permissions, $create, $expectedOK ) {
		$repo = $this->getMockRepository();
		$titleLookup = $this->getEntityTitleLookup();

		$user = $this->getUser( 'EditEntityTestUser' );
		$item = $this->prepareItemForPermissionCheck( $user, $repo, $create );

		$token = $user->getEditToken();
		$edit = $this->makeEditEntity( $repo, $item, $titleLookup, $user, false, $permissions );

		$edit->attemptSave( "testing", ( $item->getId() === null ? EDIT_NEW : EDIT_UPDATE ), $token );

		$this->assertEquals( $expectedOK, $edit->getStatus()->isOK(), var_export( $edit->getStatus()->getErrorsArray(), true ) );
		$this->assertNotEquals( $expectedOK, $edit->hasError( EditEntity::PERMISSION_ERROR ) );
	}

	/**
	 * Forces the group membership of the given user
	 *
	 * @param User $user
	 * @param array $groups
	 */
	private function setUserGroups( User $user, array $groups ) {
		if ( $user->getId() === 0 ) {
			$user = User::createNew( $user->getName() );
		}

		$remove = array_diff( $user->getGroups(), $groups );
		$add = array_diff( $groups, $user->getGroups() );

		foreach ( $remove as $group ) {
			$user->removeGroup( $group );
		}

		foreach ( $add as $group ) {
			$user->addGroup( $group );
		}
	}

	public function dataAttemptSaveRateLimit() {
		return array(

			array( // #0: no limits
				array(), // limits: none
				array(), // groups: none
				array( // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'bar', 'ok' => true ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'Bar', 'ok' => true ),
				)
			),

			array( // #1: limits bypassed with noratelimit permission
				array( // limits:
					'edit' => array(
						'user' => array( 1, 60 ), // one edit per minute
					)
				),
				array( // groups:
					'sysop' // sysop has the noratelimit permission set in the test case
				),
				array( // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'bar', 'ok' => true ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'Bar', 'ok' => true ),
				)
			),

			array( // #2: per-group limit overrides with less restrictive limit
				array( // limits:
					'edit' => array(
						'user' => array( 1, 60 ), // one edit per minute
						'kittens' => array( 10, 60 ), // one edit per minute
					)
				),
				array( // groups:
					'kittens'
				),
				array( // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'bar', 'ok' => true ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'Bar', 'ok' => true ),
				)
			),

			array( // #3: edit limit applies
				array( // limits:
					'edit' => array(
						'user' => array( 1, 60 ), // one edit per minute
					),
				),
				array(), // groups: none
				array( // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => false ),
				)
			),

			array( // #4: edit limit also applies to creations
				array( // limits:
					'edit' => array(
						'user' => array( 1, 60 ), // one edit per minute
					),
					'create' => array(
						'user' => array( 10, 60 ), // ten creations per minute
					),
				),
				array(), // groups: none
				array( // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'bar', 'ok' => false ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => false ),
				)
			),

			array( // #5: creation limit applies in addition to edit limit
				array( // limits:
					'edit' => array(
						'user' => array( 10, 60 ), // ten edits per minute
					),
					'create' => array(
						'user' => array( 1, 60 ), // ...but only one creation
					),
				),
				array(), // groups: none
				array( // edits:
					array( 'item' => 'foo', 'label' => 'foo', 'ok' => true ),
					array( 'item' => 'foo', 'label' => 'Foo', 'ok' => true ),
					array( 'item' => 'bar', 'label' => 'bar', 'ok' => false ),
				)
			)

		);
	}

	/**
	 * @dataProvider dataAttemptSaveRateLimit
	 */
	public function testAttemptSaveRateLimit( array $limits, array $groups, array $edits ) {
		$repo = $this->getMockRepository();

		$this->setMwGlobals(
			'wgRateLimits',
			$limits
		);

		$this->setMwGlobals(
			'wgGroupPermissions',
			array(
				'*' => array( 'edit' => true ),
				'sysop' => array( 'noratelimit' => true )
			)
		);

		// make sure we have a working cache
		$this->setMwGlobals(
			'wgMainCacheType',
			CACHE_ANYTHING
		);

		// make sure we have a fresh cache
		ObjectCache::clear();

		$user = $this->getUser( 'UserForTestAttemptSaveRateLimit' );
		$this->setUserGroups( $user, $groups );

		$items = array();
		$titleLookup = $this->getEntityTitleLookup();

		foreach ( $edits as $e ) {
			$name = $e[ 'item' ];
			$label = $e[ 'label' ];
			$expectedOK = $e[ 'ok' ];

			if ( isset( $items[$name] ) ) {
				// re-use item
				$item = $items[$name];
			} else {
				// create item
				$item = new Item();
				$items[$name] = $item;
			}

			$item->setLabel( 'en', $label );

			$edit = $this->makeEditEntity( $repo, $item, $titleLookup, $user );
			$edit->attemptSave( "testing", ( $item->getId() === null ? EDIT_NEW : EDIT_UPDATE ), false );

			$this->assertEquals( $expectedOK, $edit->getStatus()->isOK(), var_export( $edit->getStatus()->getErrorsArray(), true ) );
			$this->assertNotEquals( $expectedOK, $edit->hasError( EditEntity::RATE_LIMIT ) );
		}

		// make sure nobody else has to work with our cache
		ObjectCache::clear();
	}

	public function provideIsTokenOk() {
		return array(
			array( //0
				true, // use a newly generated valid token
				true, // should work
			),
			array( //1
				"xyz", // use an invalid token
				false, // should fail
			),
			array( //2
				"", // use an empty token
				false, // should fail
			),
			array( //3
				null, // use no token
				false, // should fail
			),
		);
	}

	/**
	 * @dataProvider provideIsTokenOk
	 */
	public function testIsTokenOk( $token, $shouldWork ) {
		$repo = $this->getMockRepository();
		$user = $this->getUser( 'EditEntityTestUser' );

		$item = new Item();
		$titleLookup = $this->getEntityTitleLookup();
		$edit = $this->makeEditEntity( $repo, $item, $titleLookup, $user );

		// check valid token --------------------
		if ( $token === true ) {
			$token = $user->getEditToken();
		}

		$this->assertEquals( $shouldWork, $edit->isTokenOK( $token ) );

		$this->assertEquals( $shouldWork, $edit->getStatus()->isOK() );
		$this->assertNotEquals( $shouldWork, $edit->hasError( EditEntity::TOKEN_ERROR ) );
		$this->assertNotEquals( $shouldWork, $edit->showErrorPage() );
	}

	public function provideAttemptSaveWatch() {
		// $watchdefault, $watchcreations, $new, $watched, $watch, $expected

		return array(
			array( true, true, true, false, null, true ), // watch new
			array( true, true, true, false, false, false ), // override watch new

			array( true, true, false, false, null, true ), // watch edit
			array( true, true, false, false, false, false ), // override watch edit

			array( false, false, false, false, null, false ), // don't watch edit
			array( false, false, false, false, true, true ), // override don't watch edit

			array( false, false, false, true, null, true ), // watch watched
			array( false, false, false, true, false, false ), // override don't watch edit
		);
	}

	/**
	 * @dataProvider provideAttemptSaveWatch
	 */
	public function testAttemptSaveWatch( $watchdefault, $watchcreations, $new, $watched, $watch, $expected ) {
		$repo = $this->getMockRepository();

		$user = $this->getUser( 'EditEntityTestUser2' );

		if ( $user->getId() === 0 ) {
			$user->addToDatabase();
		}

		$user->setOption( 'watchdefault', $watchdefault );
		$user->setOption( 'watchcreations', $watchcreations );

		$item = new Item();
		$item->setLabel( "en", "Test" );

		if ( !$new ) {
			$repo->putEntity( $item );
			$repo->updateWatchlist( $user, $item->getId(), $watched );
		}

		$titleLookup = $this->getEntityTitleLookup();
		$edit = $this->makeEditEntity( $repo, $item, $titleLookup, $user );
		$status = $edit->attemptSave( "testing", $new ? EDIT_NEW : EDIT_UPDATE, false, $watch );

		$this->assertTrue( $status->isOK(), "edit failed: " . $status->getWikiText() ); // sanity

		$this->assertEquals( $expected, $repo->isWatching( $user, $item->getId() ), "watched" );
	}

	public function testIsNew() {
		$repo = $this->getMockRepository();
		$titleLookup = $this->getEntityTitleLookup();
		$item = new Item();

		$isNew = new ReflectionMethod( EditEntity::class, 'isNew' );
		$isNew->setAccessible( true );

		$edit = $this->makeEditEntity( $repo, $item, $titleLookup );
		$this->assertTrue( $isNew->invoke( $edit ), 'New entity: No id' );

		$repo->assignFreshId( $item );
		$edit = $this->makeEditEntity( $repo, $item, $titleLookup );
		$this->assertTrue( $isNew->invoke( $edit ), "New entity: Has an id, but doesn't exist, yet" );

		$repo->saveEntity( $item, 'testIsNew', $this->getUser( 'EditEntityTestUser1' ) );
		$edit = $this->makeEditEntity( $repo, $item, $titleLookup );
		$this->assertFalse( $isNew->invoke( $edit ), "Entity exists" );
	}

	public function provideHookRunnerReturnStatus() {
		return array(
			array( Status::newGood() ),
			array( Status::newFatal( 'OMG' ) ),
		);
	}

	/**
	 * @dataProvider provideHookRunnerReturnStatus
	 */
	public function testEditFilterHookRunnerInteraction( Status $hookReturnStatus ) {
		$edit = $this->makeEditEntity(
			$this->getMockRepository(),
			new Item(),
			$this->getEntityTitleLookup(),
			null,
			false,
			null,
			$this->getMockEditFitlerHookRunner( $hookReturnStatus, $this->once() )
		);
		$user = $this->getUser( 'EditEntityTestUser' );

		$saveStatus = $edit->attemptSave(
			'some Summary',
			EDIT_MINOR,
			$user->getEditToken()
		);

		$this->assertEquals( $hookReturnStatus->isGood(), $saveStatus->isGood() );
	}

}
