<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use ApiMessage;
use Generator;
use MediaWiki\Block\Block;
use MediaWiki\Status\Status;
use MediaWiki\User\UserFactory;
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
	public function testCanCreateAnItemAsRegisteredUser( Status $permissionStatus, PermissionCheckResult $result ): void {
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
	public function testCanCreateAnItemAsAnonymousUser( Status $permissionStatus, PermissionCheckResult $result ): void {
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
	public function testCanCreatePropertyAsRegisteredUser( Status $permissionStatus, PermissionCheckResult $result ): void {
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
	public function testCanCreatePropertyAsAnonymousUser( Status $permissionStatus, PermissionCheckResult $result ): void {
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
	public function testCanEditAsRegisteredUser( EntityId $entityIdToEdit, Status $permissionStatus, PermissionCheckResult $result ): void {
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
	public function testCanEditAsAnonymousUser( EntityId $entityIdToEdit, Status $permissionStatus, PermissionCheckResult $result ): void {
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
				Status::newFatal( 'insufficient permissions' ),
				PermissionCheckResult::newDenialForUnknownReason(),
			],
			'denied, page protected' => [
				Status::newFatal( 'protectedpagetext' ),
				PermissionCheckResult::newPageProtected(),
			],
			'denied, user blocked' => [
				Status::newFatal( new ApiMessage(
					'blocked',
					'blocked-code',
					[ 'blockinfo' => [ 'blocktargettype' => Block::BLOCK_TYPES[ Block::TYPE_USER ] ] ]
				) ),
				PermissionCheckResult::newUserBlocked(),
			],
			'denied, ip blocked' => [
				Status::newFatal( new ApiMessage(
					'blocked',
					'blocked-code',
					[ 'blockinfo' => [ 'blocktargettype' => Block::BLOCK_TYPES[ Block::TYPE_IP ] ] ]
				) ),
				PermissionCheckResult::newIpBlocked(),
			],
			'denied, ip range blocked' => [
				Status::newFatal( new ApiMessage(
					'blocked',
					'blocked-code',
					[ 'blockinfo' => [ 'blocktargettype' => Block::BLOCK_TYPES[ Block::TYPE_RANGE ] ] ]
				) ),
				PermissionCheckResult::newIpBlocked(),
			],
			'denied, auto-blocked' => [
				Status::newFatal( new ApiMessage(
					'blocked',
					'blocked-code',
					[ 'blockinfo' => [ 'blocktargettype' => Block::BLOCK_TYPES[ Block::TYPE_AUTO ] ] ]
				) ),
				PermissionCheckResult::newIpBlocked(),
			],
			'good status' => [ Status::newGood(), PermissionCheckResult::newAllowed() ],
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
		yield [ Status::newFatal( 'insufficient permissions' ), PermissionCheckResult::newDenialForUnknownReason() ];

		yield [ Status::newGood(), PermissionCheckResult::newAllowed() ];
	}

}
