<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use Generator;
use MediaWiki\Block\AnonIpBlockTarget;
use MediaWiki\Block\AutoBlockTarget;
use MediaWiki\Block\BlockTarget;
use MediaWiki\Block\RangeBlockTarget;
use MediaWiki\Block\SystemBlock;
use MediaWiki\Block\UserBlockTarget;
use MediaWiki\Permissions\PermissionStatus;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentityValue;
use PHPUnit\Framework\TestCase;
use User as MediaWikiUser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Domains\Crud\Domain\Model\User;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\PermissionCheckResult;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\WikibaseEntityPermissionChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityPermissionCheckerTest extends TestCase {

	/**
	 * @dataProvider providePermissionStatusForCreatingAnEntity
	 */
	public function testCanCreateAnItemAsRegisteredUser( PermissionStatus $permissionStatus, PermissionCheckResult $result ): void {
		$user = User::withUsername( 'user123' );

		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newFromName' )
			->with( $user->getUsername() )
			->willReturn( $mwUser );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntity' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, new Item() )
			->willReturn( $permissionStatus );

		$permissionChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertEquals( $result, $permissionChecker->canCreateItem( $user ) );
	}

	/**
	 * @dataProvider providePermissionStatusForCreatingAnEntity
	 */
	public function testCanCreateAnItemAsAnonymousUser( PermissionStatus $permissionStatus, PermissionCheckResult $result ): void {
		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newAnonymous' )
			->willReturn( $mwUser );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntity' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, new Item() )
			->willReturn( $permissionStatus );

		$permissionChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertEquals( $result, $permissionChecker->canCreateItem( User::newAnonymous() ) );
	}

	/**
	 * @dataProvider providePermissionStatusForCreatingAnEntity
	 */
	public function testCanCreatePropertyAsRegisteredUser( PermissionStatus $permissionStatus, PermissionCheckResult $result ): void {
		$user = User::withUsername( 'user123' );

		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newFromName' )
			->with( $user->getUsername() )
			->willReturn( $mwUser );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntity' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, new Property( null, null, 'string' ) )
			->willReturn( $permissionStatus );

		$permissionChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertEquals( $result, $permissionChecker->canCreateProperty( $user ) );
	}

	/**
	 * @dataProvider providePermissionStatusForCreatingAnEntity
	 */
	public function testCanCreatePropertyAsAnonymousUser( PermissionStatus $permissionStatus, PermissionCheckResult $result ): void {
		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newAnonymous' )
			->willReturn( $mwUser );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntity' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, new Property( null, null, 'string' ) )
			->willReturn( $permissionStatus );

		$permissionChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertEquals( $result, $permissionChecker->canCreateProperty( User::newAnonymous() ) );
	}

	/**
	 * @dataProvider provideEntityIdAndPermissionStatus
	 */
	public function testCanEditAsRegisteredUser(
		EntityId $entityIdToEdit,
		PermissionStatus $permissionStatus,
		PermissionCheckResult $result
	): void {
		$user = User::withUsername( 'potato' );

		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newFromName' )
			->with( $user->getUsername() )
			->willReturn( $mwUser );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntityId' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, $entityIdToEdit )
			->willReturn( $permissionStatus );

		$permissionChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertEquals( $result, $permissionChecker->canEdit( $user, $entityIdToEdit ) );
	}

	/**
	 * @dataProvider provideEntityIdAndPermissionStatus
	 */
	public function testCanEditAsAnonymousUser(
		EntityId $entityIdToEdit,
		PermissionStatus $permissionStatus,
		PermissionCheckResult $result
	): void {
		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newAnonymous' )
			->willReturn( $mwUser );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntityId' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, $entityIdToEdit )
			->willReturn( $permissionStatus );

		$permissionChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertEquals( $result, $permissionChecker->canEdit( User::newAnonymous(), $entityIdToEdit ) );
	}

	public static function provideEntityIdAndPermissionStatus(): array {
		$entityIds = [
			'item id' => [ new ItemId( 'Q123' ) ],
			'property id' => [ new NumericPropertyId( 'P123' ) ],
		];

		$permissionStatuses = [
			'denied, unknown reason' => [
				PermissionStatus::newFatal( 'insufficient permissions' ),
				PermissionCheckResult::newDenialForUnknownReason(),
			],
			'denied, page protected' => [
				PermissionStatus::newFatal( 'protectedpagetext' ),
				PermissionCheckResult::newPageProtected(),
			],
			'denied, user blocked' => [
				self::newBlockedStatus( new UserBlockTarget( new UserIdentityValue( 0, 'test' ) ) ),
				PermissionCheckResult::newUserBlocked(),
			],
			'denied, ip blocked' => [
				self::newBlockedStatus( new AnonIpBlockTarget( '1.2.3.4' ) ),
				PermissionCheckResult::newIpBlocked(),
			],
			'denied, ip range blocked' => [
				self::newBlockedStatus( new RangeBlockTarget( '1.2.3.4/16', [] ) ),
				PermissionCheckResult::newIpBlocked(),
			],
			'denied, auto-blocked' => [
				self::newBlockedStatus( new AutoBlockTarget( 0 ) ),
				PermissionCheckResult::newIpBlocked(),
			],
			'good status' => [ PermissionStatus::newGood(), PermissionCheckResult::newAllowed() ],
		];

		$dataSet = [];
		foreach ( $entityIds as $entityIdType => $entityId ) {
			foreach ( $permissionStatuses as $statusType => $status ) {
				$dataSet["$entityIdType with $statusType"] = array_merge( $entityId, $status );
			}
		}

		return $dataSet;
	}

	public static function providePermissionStatusForCreatingAnEntity(): Generator {
		yield [ PermissionStatus::newFatal( 'insufficient permissions' ), PermissionCheckResult::newDenialForUnknownReason() ];

		yield [ PermissionStatus::newGood(), PermissionCheckResult::newAllowed() ];
	}

	public static function newBlockedStatus( BlockTarget $blockTarget ): PermissionStatus {
		$block = new SystemBlock();
		$block->setTarget( $blockTarget );

		$status = PermissionStatus::newEmpty();
		$status->setBlock( $block );

		return $status;
	}

}
