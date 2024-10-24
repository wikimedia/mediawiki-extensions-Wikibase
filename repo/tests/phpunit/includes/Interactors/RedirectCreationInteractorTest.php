<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Interactors;

use MediaWiki\Context\IContextSource;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\TempUser\CreateStatus;
use MediaWiki\User\TempUser\TempUserCreator;
use MediaWiki\User\User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Interactors\ItemRedirectCreationInteractor
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class RedirectCreationInteractorTest extends \PHPUnit\Framework\TestCase {

	private ?MockRepository $mockRepository = null;

	protected function setUp(): void {
		parent::setUp();

		$this->mockRepository = new MockRepository();

		// empty item
		$item = new Item( new ItemId( 'Q11' ) );
		$this->mockRepository->putEntity( $item );

		// non-empty item
		$item->setLabel( 'en', 'Foo' );
		$item->setId( new ItemId( 'Q12' ) );
		$this->mockRepository->putEntity( $item );

		// a property
		$prop = Property::newFromType( 'string' );
		$prop->setId( new NumericPropertyId( 'P11' ) );
		$this->mockRepository->putEntity( $prop );

		// another property
		$prop->setId( new NumericPropertyId( 'P12' ) );
		$this->mockRepository->putEntity( $prop );

		// redirect
		$redirect = new EntityRedirect( new ItemId( 'Q22' ), new ItemId( 'Q12' ) );
		$this->mockRepository->putRedirect( $redirect );
	}

	private function getPermissionChecker(): EntityPermissionChecker {
		$permissionChecker = $this->createMock( EntityPermissionChecker::class );

		$permissionChecker->method( 'getPermissionStatusForEntityId' )
			->willReturnCallback( function( User $user ) {
				$userWithoutPermissionName = 'UserWithoutPermission';

				if ( $user->getName() === $userWithoutPermissionName ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} );

		return $permissionChecker;
	}

	/**
	 * @param mixed|null $invokeCount
	 * @param Status|null $hookReturn
	 *
	 * @return EditFilterHookRunner
	 */
	public function getMockEditFilterHookRunner(
		$invokeCount = null,
		Status $hookReturn = null
	): EditFilterHookRunner {
		$mock = $this->getMockBuilder( EditFilterHookRunner::class )
			->onlyMethods( [ 'run' ] )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $invokeCount ?? $this->any() )
			->method( 'run' )
			->willReturn( $hookReturn ?? Status::newGood() );
		return $mock;
	}

	/**
	 * @param mixed|null $efHookCalls expected edit filter hook calls: either $this->once() or null
	 * @param Status|null $efHookStatus
	 * @param TempUserCreator|null $tempUserCreator
	 *
	 * @return ItemRedirectCreationInteractor
	 */
	private function newInteractor(
		$efHookCalls = null,
		Status $efHookStatus = null,
		TempUserCreator $tempUserCreator = null
	): ItemRedirectCreationInteractor {
		$summaryFormatter = WikibaseRepo::getSummaryFormatter();

		if ( $tempUserCreator === null ) {
			$tempUserCreator = $this->createMock( TempUserCreator::class );
			$tempUserCreator->method( 'shouldAutoCreate' )
				->willReturn( false );
			$tempUserCreator->expects( $this->never() )->method( 'create' );
		}

		return new ItemRedirectCreationInteractor(
			$this->mockRepository,
			$this->mockRepository,
			$this->getPermissionChecker(),
			$summaryFormatter,
			$this->getMockEditFilterHookRunner( $efHookCalls, $efHookStatus ),
			$this->mockRepository,
			$this->getMockEntityTitleLookup(),
			$tempUserCreator
		);
	}

	private function getMockEntityTitleLookup(): EntityTitleStoreLookup {
		$titleLookup = $this->createMock( EntityTitleStoreLookup::class );

		$titleLookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				$title = $this->createMock( Title::class );
				$title->method( 'isDeleted' )
					->willReturn( $id->getSerialization() === 'Q666' );
				return $title;
			} );

		return $titleLookup;
	}

	private function getContext( User $user = null ): IContextSource {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );
		$context->setUser( $user ?? $this->createMock( User::class ) );

		return $context;
	}

	public static function createRedirectProvider_success(): iterable {
		return [
			'redirect empty entity' => [ new ItemId( 'Q11' ), new ItemId( 'Q12' ) ],
			'update redirect' => [ new ItemId( 'Q22' ), new ItemId( 'Q11' ) ],
			'over deleted item' => [ new ItemId( 'Q666' ), new ItemId( 'Q11' ) ],
		];
	}

	/**
	 * @dataProvider createRedirectProvider_success
	 */
	public function testCreateRedirect_success( EntityId $fromId, EntityId $toId ) {
		$interactor = $this->newInteractor( $this->once() );

		$status = $interactor->createRedirect( $fromId, $toId, false, [ 'tag' ], $this->getContext() );

		try {
			$this->mockRepository->getEntity( $fromId );
			$this->fail( 'getEntity( ' . $fromId->getSerialization() . ' ) did not throw an UnresolvedRedirectException' );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			$this->assertEquals( $toId->getSerialization(), $ex->getRedirectTargetId()->getSerialization() );
			$this->assertSame( [ 'tag' ], $this->mockRepository->getLatestLogEntryFor( $fromId )['tags'] );
		}
		$entityRedirect = $status->getRedirect();
		$this->assertSame( $fromId, $entityRedirect->getEntityId() );
		$this->assertSame( $toId, $entityRedirect->getTargetId() );
		// assert that getContext() and getSavedTempUser() don’t throw a TypeError
		$status->getContext();
		$status->getSavedTempUser();
	}

	public static function createRedirectProvider_failure(): iterable {
		return [
			'source not found' => [
				new ItemId( 'Q77' ),
				new ItemId( 'Q12' ),
				'no-such-entity',
				[ 'Q77' ],
			],
			'target not found' => [
				new ItemId( 'Q11' ),
				new ItemId( 'Q77' ),
				'no-such-entity',
				[ 'Q77' ],
			],
			'target is a redirect' => [
				new ItemId( 'Q11' ),
				new ItemId( 'Q22' ),
				'target-is-redirect',
				[ 'Q22' ],
			],
			'target is incompatible' => [
				new ItemId( 'Q11' ),
				new NumericPropertyId( 'P11' ),
				'target-is-incompatible',
				[],
			],
			'target and source are the same' => [
				new ItemId( 'Q11' ),
				new ItemId( 'Q11' ),
				'source-and-target-are-the-same',
				[],
			],

			'source not empty' => [
				new ItemId( 'Q12' ),
				new ItemId( 'Q11' ),
				'origin-not-empty',
				[ 'Q12' ],
			],
			'can\'t redirect' => [
				new NumericPropertyId( 'P11' ),
				new NumericPropertyId( 'P12' ),
				'cant-redirect',
				[],
			],
			'can\'t redirect EditFilter' => [
				new ItemId( 'Q11' ),
				new ItemId( 'Q12' ),
				'cant-redirect-due-to-edit-filter-hook',
				[],
				Status::newFatal( 'EF' ),
			],
		];
	}

	/**
	 * @dataProvider createRedirectProvider_failure
	 */
	public function testCreateRedirect_failure(
		EntityId $fromId,
		EntityId $toId,
		string $expectedCode,
		array $messageParams,
		Status $efStatus = null
	): void {
		$interactor = $this->newInteractor( null, $efStatus );

		try {
			$interactor->createRedirect( $fromId, $toId, false, [], $this->getContext() );
			$this->fail( 'createRedirect not fail with error ' . $expectedCode . ' as expected!' );
		} catch ( RedirectCreationException $ex ) {
			$this->assertEquals( $expectedCode, $ex->getErrorCode() );
			$this->assertSame( 'wikibase-redirect-' . $expectedCode, $ex->getKey() );
			$this->assertSame( $messageParams, $ex->getParams() );
		}
	}

	public function testSetRedirect_noPermission() {
		$this->expectException( RedirectCreationException::class );

		$user = User::newFromName( 'UserWithoutPermission' );

		$interactor = $this->newInteractor( null, null );
		$interactor->createRedirect( new ItemId( 'Q11' ), new ItemId( 'Q12' ), false, [], $this->getContext( $user ) );
	}

	public function testCreateRedirect_createTempUser(): void {
		$anonUser = MediaWikiServices::getInstance()->getUserFactory()->newAnonymous();
		$originalContext = $this->getContext( $anonUser );
		$tempUser = $this->createMock( User::class );
		$tempUserCreator = $this->createMock( TempUserCreator::class );
		$tempUserCreator->method( 'shouldAutoCreate' )
			->willReturn( true );
		$tempUserCreator->method( 'create' )
			->willReturn( CreateStatus::newGood( $tempUser ) );
		$interactor = $this->newInteractor( null, null, $tempUserCreator );

		$status = $interactor->createRedirect(
			new ItemId( 'Q11' ),
			new ItemId( 'Q12' ),
			false,
			[],
			$originalContext
		);
		$context = $status->getContext();
		$savedTempUser = $status->getSavedTempUser();

		$this->assertNotSame( $originalContext, $context, 'new context created' );
		$this->assertNotNull( $savedTempUser, 'temp user saved' );
		$this->assertSame( $savedTempUser, $context->getUser(), 'new context has temp user' );
		$this->assertSame( $anonUser, $originalContext->getUser(), 'original context not changed' );
	}

}
