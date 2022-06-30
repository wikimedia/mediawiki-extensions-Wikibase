<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use Generator;
use MediaWiki\User\UserFactory;
use PHPUnit\Framework\TestCase;
use Status;
use User as MediaWikiUser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\WikibaseEntityPermissionChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityPermissionCheckerTest extends TestCase {

	/**
	 * @dataProvider permissionStatusProvider
	 */
	public function testCanEditAsRegisteredUser( Status $permissionStatus, bool $canEdit ): void {
		$user = User::withUsername( 'potato' );
		$itemToEdit = new ItemId( 'Q123' );

		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newFromName' )
			->with( $user->getUsername() )
			->willReturn( $mwUser );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntityId' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, $itemToEdit )
			->willReturn( $permissionStatus );

		$permissionChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertSame( $canEdit, $permissionChecker->canEdit( $user, $itemToEdit ) );
	}

	/**
	 * @dataProvider permissionStatusProvider
	 */
	public function testCanEditAsAnonymousUser( Status $permissionStatus, bool $canEdit ): void {
		$itemToEdit = new ItemId( 'Q123' );

		$mwUser = $this->createStub( MediaWikiUser::class );
		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->expects( $this->once() )
			->method( 'newAnonymous' )
			->willReturn( $mwUser );

		$wbPermissionChecker = $this->createMock( EntityPermissionChecker::class );
		$wbPermissionChecker->expects( $this->once() )
			->method( 'getPermissionStatusForEntityId' )
			->with( $mwUser, EntityPermissionChecker::ACTION_EDIT, $itemToEdit )
			->willReturn( $permissionStatus );

		$permissionChecker = new WikibaseEntityPermissionChecker( $wbPermissionChecker, $userFactory );

		$this->assertSame( $canEdit, $permissionChecker->canEdit( User::newAnonymous(), $itemToEdit ) );
	}

	public function permissionStatusProvider(): Generator {
		yield [
			Status::newFatal( 'insufficient permissions' ),
			false,
		];
		yield [
			Status::newGood(),
			true,
		];
	}

}
