<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use MediaWiki\User\UserFactory;
use PHPUnit\Framework\TestCase;
use Status;
use User as MediaWikiUser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Domain\Model\User;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityPermissionChecker;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\WikibaseEntityPermissionChecker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityPermissionCheckerTest extends TestCase {

	/**
	 * @dataProvider provideEntityIdAndPermissionStatus
	 */
	public function testCanEditAsRegisteredUser( EntityId $entityIdToEdit, Status $permissionStatus, bool $canEdit ): void {
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

		$this->assertSame( $canEdit, $permissionChecker->canEdit( $user, $entityIdToEdit ) );
	}

	/**
	 * @dataProvider provideEntityIdAndPermissionStatus
	 */
	public function testCanEditAsAnonymousUser( EntityId $entityIdToEdit, Status $permissionStatus, bool $canEdit ): void {
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

		$this->assertSame( $canEdit, $permissionChecker->canEdit( User::newAnonymous(), $entityIdToEdit ) );
	}

	public function provideEntityIdAndPermissionStatus(): array {
		$entityIds = [
			'item id' => [ new ItemId( 'Q123' ) ],
			'property id' => [ new NumericPropertyId( 'P123' ) ],
		];

		$permissionStatuses = [
			'fatal status' => [ Status::newFatal( 'insufficient permissions' ), false ],
			'good status' => [ Status::newGood(), true ],
		];

		$dataSet = [];
		foreach ( $entityIds as $entityIdType => $entityId ) {
			foreach ( $permissionStatuses as $statusType => $status ) {
				$dataSet["$entityIdType with $statusType"] = array_merge( $entityId, $status );
			}
		}

		return $dataSet;
	}

}
