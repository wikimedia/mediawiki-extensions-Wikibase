<?php

namespace Wikibase\Test\Interactors;

use Status;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRedirect;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Repo\Interactors\RedirectCreationInteractor
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseInteractor
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class RedirectCreationInteractorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var MockRepository|null
	 */
	private $mockRepository = null;

	protected function setUp() {
		parent::setUp();

		$this->mockRepository = new MockRepository();

		// empty item
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q11' ) );
		$this->mockRepository->putEntity( $item );

		// non-empty item
		$item->setLabel( 'en', 'Foo' );
		$item->setId( new ItemId( 'Q12' ) );
		$this->mockRepository->putEntity( $item );

		// a property
		$prop = Property::newEmpty();
		$prop->setId( new PropertyId( 'P11' ) );
		$this->mockRepository->putEntity( $prop );

		// another property
		$prop->setId( new PropertyId( 'P12' ) );
		$this->mockRepository->putEntity( $prop );

		// redirect
		$redirect = new EntityRedirect( new ItemId( 'Q22' ), new ItemId( 'Q12' ) );
		$this->mockRepository->putRedirect( $redirect );
	}

	/**
	 * @return EntityPermissionChecker
	 */
	private function getPermissionCheckers() {
		$permissionChecker = $this->getMock( 'Wikibase\Repo\Store\EntityPermissionChecker' );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnCallback( function( User $user, $permission, EntityId $id ) {
				$userWithoutPermissionName = 'UserWithoutPermission-' . $permission;

				if ( $user->getName() === $userWithoutPermissionName ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} ) );

		return $permissionChecker;
	}

	/**
	 * @param User $user
	 *
	 * @return RedirectCreationInteractor
	 */
	private function newInteractor( User $user = null ) {
		if ( !$user ) {
			$user = $GLOBALS['wgUser'];
		}

		$summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();

		$interactor = new RedirectCreationInteractor(
			$this->mockRepository,
			$this->mockRepository,
			$this->getPermissionCheckers(),
			$summaryFormatter,
			$user
		);

		return $interactor;
	}

	public function createRedirectProvider_success() {
		return array(
			'redirect empty entity' => array( new ItemId( 'Q11' ), new ItemId( 'Q12' ) ),
			'update redirect' => array( new ItemId( 'Q22' ), new ItemId( 'Q11' ) ),
		);
	}

	/**
	 * @dataProvider createRedirectProvider_success
	 */
	public function testCreateRedirect_success( EntityId $fromId, EntityId $toId ) {
		$interactor = $this->newInteractor();

		$interactor->createRedirect( $fromId, $toId );

		try {
			$this->mockRepository->getEntity( $fromId );
			$this->fail( 'getEntity( ' . $fromId->getSerialization() . ' ) did not throw an UnresolvedRedirectException' );
		} catch ( UnresolvedRedirectException $ex ) {
			$this->assertEquals( $toId->getSerialization(), $ex->getRedirectTargetId()->getSerialization() );
		}
	}

	public function createRedirectProvider_failure() {
		return array(
			'source not found' => array( new ItemId( 'Q77' ), new ItemId( 'Q12' ), 'no-such-entity' ),
			'target not found' => array( new ItemId( 'Q11' ), new ItemId( 'Q77' ), 'no-such-entity' ),
			'target is a redirect' => array( new ItemId( 'Q11' ), new ItemId( 'Q22' ), 'target-is-redirect' ),
			'target is incompatible' => array( new ItemId( 'Q11' ), new PropertyId( 'P11' ), 'target-is-incompatible' ),

			'source not empty' => array( new ItemId( 'Q12' ), new ItemId( 'Q11' ), 'target-not-empty' ),
			'can\'t redirect' => array( new PropertyId( 'P11' ), new PropertyId( 'P12' ), 'cant-redirect' ),
		);
	}

	/**
	 * @dataProvider createRedirectProvider_failure
	 */
	public function testCreateRedirect_failure( EntityId $fromId, EntityId $toId, $expectedCode ) {
		$interactor = $this->newInteractor();

		try {
			$interactor->createRedirect( $fromId, $toId );
			$this->fail( 'createRedirect not fail with error ' . $expectedCode . ' as expected!' );
		} catch ( RedirectCreationException $ex ) {
			$this->assertEquals( $expectedCode, $ex->getErrorCode() );
		}
	}

	public function permissionProvider() {
		return array(
			'edit' => array( 'edit' ),
			'item-redirect' => array( 'item-redirect' ),
		);
	}

	/**
	 * @dataProvider permissionProvider
	 */
	public function testSetRedirect_noPermission( $permission ) {
		$this->setExpectedException( 'Wikibase\Repo\Interactors\RedirectCreationException' );

		$user = User::newFromName( 'UserWithoutPermission-' . $permission );

		$interactor = $this->newInteractor( $user );
		$interactor->createRedirect( new ItemId( 'Q11' ), new ItemId( 'Q12' ) );
	}

}
