<?php

namespace Wikibase\Repo\Tests\Interactors;

use FauxRequest;
use PHPUnit_Framework_MockObject_Matcher_InvokedRecorder;
use RequestContext;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\Tests\MockRepository;

/**
 * @covers Wikibase\Repo\Interactors\RedirectCreationInteractor
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
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
		$item = new Item( new ItemId( 'Q11' ) );
		$this->mockRepository->putEntity( $item );

		// non-empty item
		$item->setLabel( 'en', 'Foo' );
		$item->setId( new ItemId( 'Q12' ) );
		$this->mockRepository->putEntity( $item );

		// a property
		$prop = Property::newFromType( 'string' );
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
	private function getPermissionChecker() {
		$permissionChecker = $this->getMock( EntityPermissionChecker::class );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnCallback( function( User $user ) {
				$userWithoutPermissionName = 'UserWithoutPermission';

				if ( $user->getName() === $userWithoutPermissionName ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} ) );

		return $permissionChecker;
	}

	/**
	 * @param PHPUnit_Framework_MockObject_Matcher_InvokedRecorder|null $invokeCount
	 * @param Status|null $hookReturn
	 *
	 * @return EditFilterHookRunner
	 */
	public function getMockEditFilterHookRunner(
		PHPUnit_Framework_MockObject_Matcher_InvokedRecorder $invokeCount = null,
		Status $hookReturn = null
	) {
		if ( $invokeCount === null ) {
			$invokeCount = $this->any();
		}
		if ( $hookReturn === null ) {
			$hookReturn = Status::newGood();
		}
		$mock = $this->getMockBuilder( EditFilterHookRunner::class )
			->setMethods( [ 'run' ] )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $invokeCount )
			->method( 'run' )
			->will( $this->returnValue( $hookReturn ) );
		return $mock;
	}

	/**
	 * @param PHPUnit_Framework_MockObject_Matcher_InvokedRecorder|null $efHookCalls
	 * @param Status|null $efHookStatus
	 * @param User|null $user
	 *
	 * @return RedirectCreationInteractor
	 */
	private function newInteractor(
		PHPUnit_Framework_MockObject_Matcher_InvokedRecorder $efHookCalls = null,
		Status $efHookStatus = null,
		User $user = null
	) {
		if ( !$user ) {
			$user = $GLOBALS['wgUser'];
		}

		$summaryFormatter = WikibaseRepo::getDefaultInstance()->getSummaryFormatter();

		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );

		$interactor = new RedirectCreationInteractor(
			$this->mockRepository,
			$this->mockRepository,
			$this->getPermissionChecker(),
			$summaryFormatter,
			$user,
			$this->getMockEditFilterHookRunner( $efHookCalls, $efHookStatus ),
			$this->mockRepository,
			$this->getMockEntityTitleLookup()
		);

		return $interactor;
	}

	/**
	 * @return EntityTitleStoreLookup
	 */
	private function getMockEntityTitleLookup() {
		$titleLookup = $this->getMock( EntityTitleStoreLookup::class );

		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$title = $this->getMock( Title::class );
				$title->expects( $this->any() )
					->method( 'isDeleted' )
					->will( $this->returnValue( $id->getSerialization() === 'Q666' ) );
				return $title;
			} ) );

		return $titleLookup;
	}

	public function createRedirectProvider_success() {
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

		$interactor->createRedirect( $fromId, $toId, false );

		try {
			$this->mockRepository->getEntity( $fromId );
			$this->fail( 'getEntity( ' . $fromId->getSerialization() . ' ) did not throw an UnresolvedRedirectException' );
		} catch ( RevisionedUnresolvedRedirectException $ex ) {
			$this->assertEquals( $toId->getSerialization(), $ex->getRedirectTargetId()->getSerialization() );
		}
	}

	public function createRedirectProvider_failure() {
		return [
			'source not found' => [ new ItemId( 'Q77' ), new ItemId( 'Q12' ), 'no-such-entity' ],
			'target not found' => [ new ItemId( 'Q11' ), new ItemId( 'Q77' ), 'no-such-entity' ],
			'target is a redirect' => [ new ItemId( 'Q11' ), new ItemId( 'Q22' ), 'target-is-redirect' ],
			'target is incompatible' => [ new ItemId( 'Q11' ), new PropertyId( 'P11' ), 'target-is-incompatible' ],

			'source not empty' => [ new ItemId( 'Q12' ), new ItemId( 'Q11' ), 'origin-not-empty' ],
			'can\'t redirect' => [ new PropertyId( 'P11' ), new PropertyId( 'P12' ), 'cant-redirect' ],
			'can\'t redirect EditFilter' => [ new ItemId( 'Q11' ), new ItemId( 'Q12' ), 'cant-redirect', Status::newFatal( 'EF' ) ],
		];
	}

	/**
	 * @dataProvider createRedirectProvider_failure
	 */
	public function testCreateRedirect_failure( EntityId $fromId, EntityId $toId, $expectedCode, Status $efStatus = null ) {
		$interactor = $this->newInteractor( null, $efStatus );

		try {
			$interactor->createRedirect( $fromId, $toId, false );
			$this->fail( 'createRedirect not fail with error ' . $expectedCode . ' as expected!' );
		} catch ( RedirectCreationException $ex ) {
			$this->assertEquals( $expectedCode, $ex->getErrorCode() );
		}
	}

	public function testSetRedirect_noPermission() {
		$this->setExpectedException( RedirectCreationException::class );

		$user = User::newFromName( 'UserWithoutPermission' );

		$interactor = $this->newInteractor( null, null, $user );
		$interactor->createRedirect( new ItemId( 'Q11' ), new ItemId( 'Q12' ), false );
	}

}
