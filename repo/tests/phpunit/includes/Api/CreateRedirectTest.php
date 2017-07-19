<?php

namespace Wikibase\Repo\Tests\Api;

use ApiMain;
use FauxRequest;
use Language;
use RequestContext;
use Status;
use Title;
use ApiUsageException;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\CreateRedirect;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\Tests\MockRepository;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers Wikibase\Repo\Api\CreateRedirect
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class CreateRedirectTest extends \MediaWikiTestCase {

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
	private function getPermissionCheckers() {
		$permissionChecker = $this->getMock( EntityPermissionChecker::class );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnCallback( function( User $user ) {
				if ( $user->getName() === 'UserWithoutPermission' ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} ) );

		return $permissionChecker;
	}

	/**
	 * @return EditFilterHookRunner
	 */
	public function getMockEditFilterHookRunner() {
		$mock = $this->getMockBuilder( EditFilterHookRunner::class )
			->disableOriginalConstructor()
			->getMock();

		$mock->expects( $this->any() )
			->method( 'run' )
			->will( $this->returnValue( Status::newGood() ) );

		return $mock;
	}

	/**
	 * @param array $params
	 * @param User $user
	 * @param RedirectCreationInteractor|null $interactor RedirectCreationInteractor to use, mock interactor
	 * will be used if null provided.
	 *
	 * @return CreateRedirect
	 */
	private function newApiModule(
		array $params,
		User $user,
		RedirectCreationInteractor $interactor = null
	) {
		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request, true );
		$main->getContext()->setUser( $user );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$errorReporter = new ApiErrorReporter(
			$main,
			$wikibaseRepo->getExceptionLocalizer(),
			Language::factory( 'en' )
		);

		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );

		if ( !$interactor ) {
			$interactor = new RedirectCreationInteractor(
				$this->mockRepository,
				$this->mockRepository,
				$this->getPermissionCheckers(),
				$wikibaseRepo->getSummaryFormatter(),
				$user,
				$this->getMockEditFilterHookRunner(),
				$this->mockRepository,
				$this->getMockEntityTitleLookup()
			);
		}

		return new CreateRedirect(
			$main,
			'wbcreateredirect',
			new BasicEntityIdParser(),
			$errorReporter,
			$interactor
		);
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
					->will( $this->returnValue( false ) );
				return $title;
			} ) );

		return $titleLookup;
	}

	private function callApiModule(
		array $params,
		User $user = null,
		RedirectCreationInteractor $interactor = null
	) {
		global $wgUser;

		if ( !$user ) {
			$user = $wgUser;
		}

		if ( !isset( $params['token'] ) ) {
			$params['token'] = $user->getToken();
		}

		$module = $this->newApiModule( $params, $user, $interactor );
		$module->execute();

		return $module->getResult()->getResultData( null, [
			'BC' => [],
			'Types' => [],
			'Strip' => 'all',
		] );
	}

	private function assertSuccess( $result ) {
		$this->assertArrayHasKey( 'success', $result );
		$this->assertEquals( 1, $result['success'] );
	}

	public function setRedirectProvider_success() {
		return [
			'redirect empty entity' => [ 'Q11', 'Q12' ],
			'update redirect' => [ 'Q22', 'Q11' ],
		];
	}

	/**
	 * @dataProvider setRedirectProvider_success
	 */
	public function testSetRedirect_success( $from, $to ) {
		$params = [ 'from' => $from, 'to' => $to ];
		$result = $this->callApiModule( $params );

		$this->assertSuccess( $result );
	}

	public function setRedirectProvider_failure() {
		return [
			'bad source id' => [ 'xyz', 'Q12', 'invalid-entity-id' ],
			'bad target id' => [ 'Q11', 'xyz', 'invalid-entity-id' ],

			'source not found' => [ 'Q77', 'Q12', 'no-such-entity' ],
			'target not found' => [ 'Q11', 'Q77', 'no-such-entity' ],
			'target is a redirect' => [ 'Q11', 'Q22', 'target-is-redirect' ],
			'target is incompatible' => [ 'Q11', 'P11', 'target-is-incompatible' ],

			'source not empty' => [ 'Q12', 'Q11', 'origin-not-empty' ],
			'can\'t redirect' => [ 'P11', 'P12', 'cant-redirect' ],
		];
	}

	/**
	 * @dataProvider setRedirectProvider_failure
	 */
	public function testSetRedirect_failure( $from, $to, $expectedCode ) {
		$params = [ 'from' => $from, 'to' => $to ];

		try {
			$this->callApiModule( $params );
			$this->fail( 'API did not fail with error ' . $expectedCode . ' as expected!' );
		} catch ( ApiUsageException $ex ) {
			$msg = TestingAccessWrapper::newFromObject( $ex )->getApiMessage();
			$this->assertEquals( $expectedCode, $msg->getApiCode() );
		}
	}

	public function testGivenSourceHasDeletedRevisionsButExists_sourcePageIsUpdatedAsRedirect() {
		global $wgUser;

		$sourceId = new ItemId( 'Q11' );
		$sourceItem = new Item( $sourceId );
		$targetId = new ItemId( 'Q12' );
		$targetItem = new Item( $targetId );

		$params = [ 'from' => $sourceId->getSerialization(), 'to' => $targetId->getSerialization() ];

		$main = new ApiMain( new FauxRequest( $params, true ), true );
		$main->getContext()->setUser( $wgUser );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$interactor = $wikibaseRepo->newRedirectCreationInteractor( $wgUser, $main->getContext() );
		$store = $wikibaseRepo->getEntityStore();

		$store->saveEntity( $sourceItem, 'Created the source item', $wgUser );
		$store->deleteEntity( $sourceId, 'test reason', $wgUser );
		$store->saveEntity( $sourceItem, 'Recreated the source item', $wgUser );

		$store->saveEntity( $targetItem, 'Created the target item', $wgUser );

		$result = $this->callApiModule( $params, $wgUser, $interactor );

		$this->assertSuccess( $result );
	}

	public function testGivenSourceHasDeletedRevisionsAndDoesNotExist_sourcePageIsCreatedAsRedirect() {
		global $wgUser;

		$sourceId = new ItemId( 'Q11' );
		$sourceItem = new Item( $sourceId );
		$targetId = new ItemId( 'Q12' );
		$targetItem = new Item( $targetId );

		$params = [ 'from' => $sourceId->getSerialization(), 'to' => $targetId->getSerialization() ];

		$main = new ApiMain( new FauxRequest( $params, true ), true );
		$main->getContext()->setUser( $wgUser );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$interactor = $wikibaseRepo->newRedirectCreationInteractor( $wgUser, $main->getContext() );
		$store = $wikibaseRepo->getEntityStore();

		$store->saveEntity( $sourceItem, 'Created the source item', $wgUser );
		$store->deleteEntity( $sourceId, 'test reason', $wgUser );

		$store->saveEntity( $targetItem, 'Created the target item', $wgUser );

		$result = $this->callApiModule( $params, $wgUser, $interactor );

		$this->assertSuccess( $result );
	}

	public function testSetRedirect_noPermission() {
		$this->setExpectedException( ApiUsageException::class );

		$user = User::newFromName( 'UserWithoutPermission' );

		$params = [ 'from' => 'Q11', 'to' => 'Q12' ];
		$this->callApiModule( $params, $user );
	}

	public function testModuleFlags() {
		global $wgUser;

		$module = $this->newApiModule( [], $wgUser );

		$this->assertTrue( $module->mustBePosted(), 'mustBePosted' );
		$this->assertTrue( $module->isWriteMode(), 'isWriteMode' );
		$this->assertEquals( $module->needsToken(), 'csrf', 'needsToken' );

		//NOTE: Would be nice to test the token check directly, but that is done via
		//      ApiMain::execute, which is bypassed by callApiModule().
	}

}
