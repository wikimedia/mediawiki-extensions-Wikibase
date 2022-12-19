<?php

namespace Wikibase\Repo\Tests;

use FauxRequest;
use MediaWiki\User\UserIdentity;
use MediaWikiIntegrationTestCase;
use ObjectCache;
use ReflectionMethod;
use RequestContext;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\EditEntity\EditEntity;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\EditEntity\MediawikiEditEntity;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\EditEntity\MediawikiEditEntity
 *
 * @group Wikibase
 *
 * @group Database
 *        ^--- needed just because we are using Title objects.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MediawikiEditEntityTest extends MediaWikiIntegrationTestCase {

	private function getUser( $name ) {
		$user = User::newFromName( $name );

		if ( $user->getId() === 0 ) {
			$user = User::createNew( $user->getName() );
		}

		return $user;
	}

	/**
	 * @return EntityTitleStoreLookup
	 */
	private function getEntityTitleLookup() {
		$titleLookup = $this->createMock( EntityTitleStoreLookup::class );

		$titleLookup->method( 'getTitleForId' )
			->willReturnCallback( static function ( EntityId $id ) {
				return Title::makeTitle(
					NS_MAIN,
					$id->getEntityType() . '/' . $id->getSerialization()
				);
			} );

		return $titleLookup;
	}

	/**
	 * @param bool[]|null $permissions
	 *
	 * @return EntityPermissionChecker
	 */
	private function getEntityPermissionChecker( array $permissions = null ) {
		$permissionChecker = $this->createMock( EntityPermissionChecker::class );

		$checkAction = static function ( $user, $action ) use ( $permissions ) {
			if ( $permissions === null
				|| ( isset( $permissions[$action] ) && $permissions[$action] )
			) {
				return Status::newGood( true );
			} else {
				return Status::newFatal( 'badaccess-group0' );
			}
		};

		$permissionChecker->method( 'getPermissionStatusForEntity' )
			->willReturnCallback( $checkAction );

		$permissionChecker->method( 'getPermissionStatusForEntityId' )
			->willReturnCallback( $checkAction );

		return $permissionChecker;
	}

	private function getMockEditFitlerHookRunner(
		Status $status = null,
		$expects = null
	) {
		if ( $status === null ) {
			$status = Status::newGood();
		}
		if ( $expects === null ) {
			$expects = $this->any();
		}
		$runner = $this->getMockBuilder( EditFilterHookRunner::class )
			->onlyMethods( [ 'run' ] )
			->disableOriginalConstructor()
			->getMock();
		$runner->expects( $expects )
			->method( 'run' )
			->willReturn( $status );
		return $runner;
	}

	/**
	 * @param MockRepository $mockRepository
	 * @param EntityId $entityId
	 * @param EntityTitleStoreLookup $titleLookup
	 * @param User|null $user
	 * @param bool $baseRevId
	 * @param bool[]|null $permissions map of actions to bool, indicating which actions are allowed.
	 * @param EditFilterHookRunner|null $editFilterHookRunner
	 * @param string[]|null $localEntityTypes
	 *
	 * @return MediawikiEditEntity
	 */
	private function makeEditEntity(
		MockRepository $mockRepository,
		?EntityId $entityId,
		EntityTitleStoreLookup $titleLookup,
		User $user = null,
		$baseRevId = 0,
		array $permissions = null,
		$editFilterHookRunner = null,
		$localEntityTypes = null
	) {
		if ( $user === null ) {
			$user = User::newFromName( 'EditEntityTestUser' );
		}

		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );
		$context->setUser( $user );

		if ( $editFilterHookRunner === null ) {
			$editFilterHookRunner = $this->getMockEditFitlerHookRunner();
		}

		$permissionChecker = $this->getEntityPermissionChecker( $permissions );
		$repoSettings = WikibaseRepo::getSettings();
		$localEntityTypes = $localEntityTypes ?: WikibaseRepo::getLocalEntityTypes();

		return new MediawikiEditEntity(
			$titleLookup,
			$mockRepository,
			$mockRepository,
			$permissionChecker,
			new EntityDiffer(),
			new EntityPatcher(),
			$entityId,
			$context,
			$editFilterHookRunner,
			$this->getServiceContainer()->getUserOptionsLookup(),
			$repoSettings['maxSerializedEntitySize'],
			$localEntityTypes,
			$baseRevId
		);
	}

	/**
	 * @return MockRepository
	 */
	private function getMockRepository() {
		$repo = new MockRepository();

		$user = $this->getUser( 'EditEntityTestUser1' );
		$otherUser = $this->getUser( 'EditEntityTestUser2' );

		/** @var Item $item */
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

		$redirect = new EntityRedirect(
			new ItemId( 'Q302' ),
			new ItemId( 'Q404' )
		);
		$repo->putRedirect( $redirect );

		return $repo;
	}

	public function provideEditConflict() {
		/*
		 * Test Revisions:
		 * #10: label: [ 'en' => 'foo' ];
		 * #11: label: [ 'en' => 'bar' ]; // by other user
		 * #12: label: [ 'en' => 'bar', 'de' => 'bar' ];
		 * #13: label: [ 'en' => 'test', 'de' => 'bar' ], description: [ 'en' => 'more testing' ];
		*/

		return [
			[ // #0: case I: no base rev given.
				null,  // input data
				0,  // base rev
				false, // expected conflict
				false, // expected fix
			],
			[ // #1: case II: base rev == current
				null,  // input data
				13,     // base rev
				false, // expected conflict
				false, // expected fix
			],
			[ // #2: case IIIa: user was last to edit
				[ // input data
					'label' => [ 'de' => 'yarrr' ],
				],
				12,     // base rev
				true,  // expected conflict
				true,  // expected fix
				[ // expected data
					'label' => [ 'en' => 'test', 'de' => 'yarrr' ],
				],
			],
			[ // #3: case IIIb: user was last to edit, but intoduces a new operand
				[ // input data
					'label' => [ 'de' => 'yarrr' ],
				],
				11,     // base rev
				true,  // expected conflict
				false, // expected failure, diff operand change
				null,
			],
			[ // #4: case IV: patch applied
				[ // input data
					'label' => [ 'nl' => 'test', 'fr' => 'frrrrtt' ],
				],
				10,     // base rev
				true,  // expected conflict
				true,  // expected fix
				[ // expected data
					'label' => [ 'de' => 'bar', 'en' => 'test', 'nl' => 'test', 'fr' => 'frrrrtt' ],
				],
			],
			[ // #5: case V: patch failed, expect a conflict
				[ // input data
					'label' => [ 'nl' => 'test', 'de' => 'bar' ],
				],
				10,     // base rev
				true,  // expected conflict
				false, // expected fix
				null,   // expected data
			],
			[ // #6: case VI: patch is empty, keep current (not base)
				[ // input data
					'label' => [ 'en' => 'bar', 'de' => 'bar' ],
				],
				12,     // base rev
				true,  // expected conflict
				true,  // expected fix
				[ // expected data
					'label' => [ 'en' => 'test', 'de' => 'bar' ],
					'description' => [ 'en' => 'more testing' ],
				],
			],
		];
	}

	/**
	 * @dataProvider provideEditConflict
	 */
	public function testEditConflict(
		?array $inputData,
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
		$editEntity = $this->makeEditEntity( $repo, $item->getId(), $titleLookup, $user, $baseRevisionId );

		if ( $baseRevisionId > 0 ) {
			$baseRevision = $editEntity->getBaseRevision();
			$this->assertSame( $baseRevisionId, $baseRevision->getRevisionId() );
			$this->assertEquals( $entityId, $baseRevision->getEntity()->getId() );
		}

		$conflict = $editEntity->hasEditConflict();
		$this->assertEquals( $expectedConflict, $conflict, 'hasEditConflict()' );

		$token = $user->getEditToken();
		$status = $editEntity->attemptSave( $item, "Testing", EDIT_UPDATE, $token );

		$expectedOk = !$expectedConflict || $expectedFix;
		$this->assertEquals( $expectedOk, $status->isOK(), 'unresolved conflict?' );

		if ( $expectedData !== null ) {
			$this->assertTrue( $status->isOK(), '$status->isOK()' );

			$result = $status->getValue();
			$this->assertArrayHasKey( 'revision', $result, '$status->getValue["revision"]' );

			$newEntity = $result['revision']->getEntity();
			$data = $this->fingerprintToPartialArray( $newEntity->getFingerprint() );

			foreach ( $expectedData as $key => $expectedValue ) {
				$actualValue = $data[$key];
				$this->assertArrayEquals( $expectedValue, $actualValue, false, true );
			}
		}
	}

	private function fingerprintToPartialArray( Fingerprint $fingerprint ) {
		return [
			'label' => $fingerprint->getLabels()->toTextArray(),
			'description' => $fingerprint->getDescriptions()->toTextArray(),
		];
	}

	public function testAttemptSaveWithLateConflict() {
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
		$editEntity = $this->makeEditEntity( $repo, $entity->getId(), $titleLookup, $user );
		$editEntity->getLatestRevision(); // make sure EditEntity has page and revision

		// create independent Entity instance for the same entity, and modify and save it
		$entity2 = new Item( new ItemId( 'Q42' ) );
		$entity2->setLabel( 'en', 'Toast' );

		$user2 = $this->getUser( 'EditEntityTestUser2' );
		$repo->putEntity( $entity2, 0, 0, $user2 );

		// now try to save the original edit. The conflict should still be detected
		$token = $user->getEditToken();
		$status = $editEntity->attemptSave( $entity, "Testing", EDIT_UPDATE, $token );

		$id = $entity->getId()->getSerialization();

		if ( $status->isOK() ) {
			$statusMessage = "Status ($id): OK";
		} else {
			$statusMessage = "Status ($id): " . $status->getWikiText();
		}

		$this->assertFalse( $status->isOK(),
			"Saving should have failed late\n$statusMessage" );

		$this->assertTrue( $editEntity->hasError(),
			"Saving should have failed late\n$statusMessage" );

		$this->assertTrue( $status->hasMessage( 'edit-conflict' ),
			"Saving should have failed late\n$statusMessage" );
	}

	public function dataCheckEditPermissions() {
		return [
			[ # 0: edit allowed for new item
				[ 'read' => true, 'edit' => true, 'createpage' => true ],
				false,
				true,
			],
			[ # 3: edit not allowed for existing item
				[ 'read' => true, 'edit' => false ],
				true,
				false,
			],
		];
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
		$edit = $this->makeEditEntity( $repo, $item->getId(), $titleLookup, $user, 0, $permissions );
		TestingAccessWrapper::newFromObject( $edit )->checkEditPermissions( $item );

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
		$edit = $this->makeEditEntity( $repo, $item->getId(), $titleLookup, $user, 0, $permissions );

		$edit->attemptSave( $item, "testing", ( $item->getId() === null ? EDIT_NEW : EDIT_UPDATE ), $token );

		$this->assertEquals( $expectedOK, $edit->getStatus()->isOK(), var_export( $edit->getStatus()->getErrorsArray(), true ) );
		$this->assertNotEquals( $expectedOK, $edit->hasError( EditEntity::PERMISSION_ERROR ) );
	}

	public function testCheckLocalEntityTypes() {
		$item = new Item();
		$user = $this->getUser( 'EditEntityTestUser' );
		$token = $user->getEditToken();

		$edit = $this->makeEditEntity(
			$this->getMockRepository(),
			$item->getId(),
			$this->getEntityTitleLookup(),
			$user,
			0,
			null,
			null,
			[ 'property' ]
		);

		$status = $edit->attemptSave( $item, 'testing', EDIT_NEW, $token );
		$this->assertStatusNotOK( $status );
		$this->assertStatusMessage( 'wikibase-error-entity-not-local', $status );
	}

	/**
	 * Forces the group membership of the given user
	 *
	 * @param UserIdentity $user
	 * @param array $groups
	 */
	private function setUserGroups( UserIdentity $user, array $groups ) {
		if ( $user->getId() === 0 ) {
			$user = User::createNew( $user->getName() );
		}

		$userGroupManager = $this->getServiceContainer()->getUserGroupManager();
		$remove = array_diff( $userGroupManager->getUserGroups( $user ), $groups );
		$add = array_diff( $groups, $userGroupManager->getUserGroups( $user ) );

		foreach ( $remove as $group ) {
			$userGroupManager->removeUserFromGroup( $user, $group );
		}

		foreach ( $add as $group ) {
			$userGroupManager->addUserToGroup( $user, $group );
		}
	}

	public function dataAttemptSaveRateLimit() {
		return [

			[ // #0: no limits
				[], // limits: none
				[], // groups: none
				[ // edits:
					[ 'item' => 'foo', 'label' => 'foo', 'ok' => true ],
					[ 'item' => 'bar', 'label' => 'bar', 'ok' => true ],
					[ 'item' => 'foo', 'label' => 'Foo', 'ok' => true ],
					[ 'item' => 'bar', 'label' => 'Bar', 'ok' => true ],
				],
			],

			[ // #1: limits bypassed with noratelimit permission
				[ // limits:
					'edit' => [
						'user' => [ 1, 60 ], // one edit per minute
					],
				],
				[ // groups:
					'sysop', // sysop has the noratelimit permission set in the test case
				],
				[ // edits:
					[ 'item' => 'foo', 'label' => 'foo', 'ok' => true ],
					[ 'item' => 'bar', 'label' => 'bar', 'ok' => true ],
					[ 'item' => 'foo', 'label' => 'Foo', 'ok' => true ],
					[ 'item' => 'bar', 'label' => 'Bar', 'ok' => true ],
				],
			],

			[ // #2: per-group limit overrides with less restrictive limit
				[ // limits:
					'edit' => [
						'user' => [ 1, 60 ], // one edit per minute
						'kittens' => [ 10, 60 ], // one edit per minute
					],
				],
				[ // groups:
					'kittens',
				],
				[ // edits:
					[ 'item' => 'foo', 'label' => 'foo', 'ok' => true ],
					[ 'item' => 'bar', 'label' => 'bar', 'ok' => true ],
					[ 'item' => 'foo', 'label' => 'Foo', 'ok' => true ],
					[ 'item' => 'bar', 'label' => 'Bar', 'ok' => true ],
				],
			],

			[ // #3: edit limit applies
				[ // limits:
					'edit' => [
						'user' => [ 1, 60 ], // one edit per minute
					],
				],
				[], // groups: none
				[ // edits:
					[ 'item' => 'foo', 'label' => 'foo', 'ok' => true ],
					[ 'item' => 'foo', 'label' => 'Foo', 'ok' => false ],
				],
			],

			[ // #4: edit limit also applies to creations
				[ // limits:
					'edit' => [
						'user' => [ 1, 60 ], // one edit per minute
					],
					'create' => [
						'user' => [ 10, 60 ], // ten creations per minute
					],
				],
				[], // groups: none
				[ // edits:
					[ 'item' => 'foo', 'label' => 'foo', 'ok' => true ],
					[ 'item' => 'bar', 'label' => 'bar', 'ok' => false ],
					[ 'item' => 'foo', 'label' => 'Foo', 'ok' => false ],
				],
			],

			[ // #5: creation limit applies in addition to edit limit
				[ // limits:
					'edit' => [
						'user' => [ 10, 60 ], // ten edits per minute
					],
					'create' => [
						'user' => [ 1, 60 ], // ...but only one creation
					],
				],
				[], // groups: none
				[ // edits:
					[ 'item' => 'foo', 'label' => 'foo', 'ok' => true ],
					[ 'item' => 'foo', 'label' => 'Foo', 'ok' => true ],
					[ 'item' => 'bar', 'label' => 'bar', 'ok' => false ],
				],
			],

		];
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
			[
				'*' => [ 'edit' => true ],
				'sysop' => [ 'noratelimit' => true ],
			]
		);

		// make sure we have a working cache
		$this->setMwGlobals( 'wgMainCacheType', 'hash' );
		// make sure we have a fresh cache
		ObjectCache::clear();

		$user = $this->getUser( 'UserForTestAttemptSaveRateLimit' );
		$this->setUserGroups( $user, $groups );

		$items = [];
		$titleLookup = $this->getEntityTitleLookup();

		foreach ( $edits as $e ) {
			$name = $e[ 'item' ];
			$label = $e[ 'label' ];
			$expectedOK = $e[ 'ok' ];

			if ( !isset( $items[$name] ) ) {
				$items[$name] = new Item();
			}
			$item = $items[$name];

			$item->setLabel( 'en', $label );

			$edit = $this->makeEditEntity( $repo, $item->getId(), $titleLookup, $user );
			$edit->attemptSave( $item, "testing", ( $item->getId() === null ? EDIT_NEW : EDIT_UPDATE ), false );

			$this->assertEquals( $expectedOK, $edit->getStatus()->isOK(), var_export( $edit->getStatus()->getErrorsArray(), true ) );
			$this->assertNotEquals( $expectedOK, $edit->hasError( EditEntity::RATE_LIMIT ) );
		}

		// make sure nobody else has to work with our cache
		ObjectCache::clear();
	}

	public function provideIsTokenOk() {
		return [
			[ // 0
				true, // use a newly generated valid token
				true, // should work
			],
			[ // 1
				"xyz", // use an invalid token
				false, // should fail
			],
			[ // 2
				"", // use an empty token
				false, // should fail
			],
			[ // 3
				null, // use no token
				false, // should fail
			],
		];
	}

	/**
	 * @dataProvider provideIsTokenOk
	 */
	public function testIsTokenOk( $token, $shouldWork ) {
		$repo = $this->getMockRepository();
		$user = $this->getUser( 'EditEntityTestUser' );

		$item = new Item();
		$titleLookup = $this->getEntityTitleLookup();
		$edit = $this->makeEditEntity( $repo, $item->getId(), $titleLookup, $user );

		// check valid token --------------------
		if ( $token === true ) {
			$token = $user->getEditToken();
		}

		$this->assertEquals( $shouldWork, $edit->isTokenOK( $token ) );

		$this->assertEquals( $shouldWork, $edit->getStatus()->isOK() );
		$this->assertNotEquals( $shouldWork, $edit->hasError( EditEntity::TOKEN_ERROR ) );
	}

	public function provideAttemptSaveWatch() {
		// $watchdefault, $watchcreations, $new, $watched, $watch, $expected

		return [
			[ true, true, true, false, null, true ], // watch new
			[ true, true, true, false, false, false ], // override watch new

			[ true, true, false, false, null, true ], // watch edit
			[ true, true, false, false, false, false ], // override watch edit

			[ false, false, false, false, null, false ], // don't watch edit
			[ false, false, false, false, true, true ], // override don't watch edit

			[ false, false, false, true, null, true ], // watch watched
			[ false, false, false, true, false, false ], // override don't watch edit
		];
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

		$userOptionsManager = $this->getServiceContainer()->getUserOptionsManager();
		$userOptionsManager->setOption( $user, 'watchdefault', $watchdefault );
		$userOptionsManager->setOption( $user, 'watchcreations', $watchcreations );

		$item = new Item();
		$item->setLabel( "en", "Test" );

		if ( !$new ) {
			$repo->putEntity( $item );
			$repo->updateWatchlist( $user, $item->getId(), $watched );
		}

		$titleLookup = $this->getEntityTitleLookup();
		$edit = $this->makeEditEntity( $repo, $item->getId(), $titleLookup, $user );
		$status = $edit->attemptSave( $item, "testing", $new ? EDIT_NEW : EDIT_UPDATE, false, $watch );

		$this->assertTrue( $status->isOK(), "edit failed: " . $status->getWikiText() ); // sanity

		$this->assertEquals( $expected, $repo->isWatching( $user, $item->getId() ), "watched" );
	}

	public function testAttemptSaveUnresolvedRedirect() {
		$repo = $this->getMockRepository();

		$user = $this->getUser( 'EditEntityTestUser2' );

		if ( $user->getId() === 0 ) {
			$user->addToDatabase();
		}

		$item = new Item( new ItemId( 'Q302' ) );
		$item->setLabel( "en", "Test" );

		$titleLookup = $this->getEntityTitleLookup();
		$edit = $this->makeEditEntity( $repo, $item->getId(), $titleLookup, $user );
		$status = $edit->attemptSave( $item, "testing", EDIT_UPDATE, false );

		$this->assertFalse( $status->isOK() );
		$this->assertSame(
			'(wikibase-save-unresolved-redirect: Q302, Q404)',
			$status->getWikiText( null, null, 'qqx' )
		);
	}

	public function testIsNew() {
		$repo = $this->getMockRepository();
		$titleLookup = $this->getEntityTitleLookup();
		$item = new Item();

		$isNew = new ReflectionMethod( MediawikiEditEntity::class, 'isNew' );
		$isNew->setAccessible( true );

		$edit = $this->makeEditEntity( $repo, $item->getId(), $titleLookup );
		$this->assertTrue( $isNew->invoke( $edit ), 'New entity: No id' );

		$repo->assignFreshId( $item );
		$edit = $this->makeEditEntity( $repo, $item->getId(), $titleLookup );
		$this->assertTrue( $isNew->invoke( $edit ), "New entity: Has an id, but doesn't exist, yet" );

		$repo->saveEntity( $item, 'testIsNew', $this->getUser( 'EditEntityTestUser1' ) );
		$edit = $this->makeEditEntity( $repo, $item->getId(), $titleLookup );
		$this->assertFalse( $isNew->invoke( $edit ), "Entity exists" );
	}

	public function provideHookRunnerReturnStatus() {
		return [
			[ Status::newGood() ],
			[ Status::newFatal( 'OMG' ) ],
		];
	}

	/**
	 * @dataProvider provideHookRunnerReturnStatus
	 */
	public function testEditFilterHookRunnerInteraction( Status $hookReturnStatus ) {
		$edit = $this->makeEditEntity(
			$this->getMockRepository(),
			null,
			$this->getEntityTitleLookup(),
			null,
			0,
			null,
			$this->getMockEditFitlerHookRunner( $hookReturnStatus, $this->once() )
		);
		$user = $this->getUser( 'EditEntityTestUser' );

		$saveStatus = $edit->attemptSave(
			new Item(),
			'some Summary',
			EDIT_MINOR,
			$user->getEditToken()
		);

		$this->assertEquals( $hookReturnStatus->isGood(), $saveStatus->isGood() );
	}

	public function testSaveWithTags() {
		$repo = $this->getMockRepository();
		$edit = $this->makeEditEntity(
			$repo,
			null,
			$this->getEntityTitleLookup()
		);
		$user = $this->getUser( 'EditEntityTestUser' );

		$status = $edit->attemptSave(
			new Item(),
			'summary',
			EDIT_MINOR,
			$user->getEditToken(),
			null,
			[ 'mw-replace' ]
		);

		$this->assertTrue( $status->isGood() );
		/** @var EntityRevision $entityRevision */
		$entityRevision = $status->getValue()['revision'];
		$tags = $repo->getLogEntry( $entityRevision->getRevisionId() )['tags'];
		$this->assertSame( [ 'mw-replace' ], $tags );
	}

}
