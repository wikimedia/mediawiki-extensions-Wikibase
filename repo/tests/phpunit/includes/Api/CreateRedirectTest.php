<?php

namespace Wikibase\Test\Repo\Api;

use ApiMain;
use FauxRequest;
use Language;
use RequestContext;
use Status;
use Title;
use UsageException;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\Repo\Api\CreateRedirect;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Repo\Api\CreateRedirect
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
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
			->will( $this->returnCallback( function( User $user, $permission ) {
				if ( $user->getName() === 'UserWithoutPermission' && $permission === 'edit' ) {
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
			->setMethods( array( 'run' ) )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'run' )
			->will( $this->returnValue( Status::newGood() ) );
		return $mock;
	}

	/**
	 * @param array $params
	 * @param User|null $user
	 *
	 * @return CreateRedirect
	 */
	private function newApiModule( array $params, User $user = null ) {
		if ( !$user ) {
			$user = $GLOBALS['wgUser'];
		}

		$request = new FauxRequest( $params, true );
		$main = new ApiMain( $request );
		$main->getContext()->setUser( $user );

		$module = new CreateRedirect( $main, 'wbcreateredirect' );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$errorReporter = new ApiErrorReporter(
			$module,
			$wikibaseRepo->getExceptionLocalizer(),
			Language::factory( 'en' )
		);

		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );

		$module->setServices(
			new BasicEntityIdParser(),
			$errorReporter,
			new RedirectCreationInteractor(
				$this->mockRepository,
				$this->mockRepository,
				$this->getPermissionCheckers(),
				$wikibaseRepo->getSummaryFormatter(),
				$user,
				$this->getMockEditFilterHookRunner(),
				$this->mockRepository,
				$this->getMockEntityTitleLookup()
			)
		);

		return $module;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockEntityTitleLookup() {
		$titleLookup = $this->getMock( EntityTitleLookup::class );

		$titleLookup->expects( $this->any() )
			->method( 'getTitleForID' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$title = $this->getMock( Title::class );
				$title->expects( $this->any() )
					->method( 'isDeleted' )
					->will( $this->returnValue( false ) );
				return $title;
			} ) );

		return $titleLookup;
	}

	private function callApiModule( array $params, User $user = null ) {
		global $wgUser;

		if ( !isset( $params['token'] ) ) {
			$params['token'] = $wgUser->getToken();
		}

		$module = $this->newApiModule( $params, $user );

		$module->execute();
		$result = $module->getResult();

		$data = $result->getResultData( null, array(
			'BC' => array(),
			'Types' => array(),
			'Strip' => 'all',
		) );
		return $data;
	}

	private function assertSuccess( $result ) {
		$this->assertArrayHasKey( 'success', $result );
		$this->assertEquals( 1, $result['success'] );
	}

	public function setRedirectProvider_success() {
		return array(
			'redirect empty entity' => array( 'Q11', 'Q12' ),
			'update redirect' => array( 'Q22', 'Q11' ),
		);
	}

	/**
	 * @dataProvider setRedirectProvider_success
	 */
	public function testSetRedirect_success( $from, $to ) {
		$params = array( 'from' => $from, 'to' => $to );
		$result = $this->callApiModule( $params );

		$this->assertSuccess( $result );
	}

	public function setRedirectProvider_failure() {
		return array(
			'bad source id' => array( 'xyz', 'Q12', 'invalid-entity-id' ),
			'bad target id' => array( 'Q11', 'xyz', 'invalid-entity-id' ),

			'source not found' => array( 'Q77', 'Q12', 'no-such-entity' ),
			'target not found' => array( 'Q11', 'Q77', 'no-such-entity' ),
			'target is a redirect' => array( 'Q11', 'Q22', 'target-is-redirect' ),
			'target is incompatible' => array( 'Q11', 'P11', 'target-is-incompatible' ),

			'source not empty' => array( 'Q12', 'Q11', 'origin-not-empty' ),
			'can\'t redirect' => array( 'P11', 'P12', 'cant-redirect' ),
		);
	}

	/**
	 * @dataProvider setRedirectProvider_failure
	 */
	public function testSetRedirect_failure( $from, $to, $expectedCode ) {
		$params = array( 'from' => $from, 'to' => $to );

		try {
			$this->callApiModule( $params );
			$this->fail( 'API did not fail with error ' . $expectedCode . ' as expected!' );
		} catch ( UsageException $ex ) {
			$this->assertEquals( $expectedCode, $ex->getCodeString() );
		}
	}

	public function testSetRedirect_noPermission() {
		$this->setExpectedException( UsageException::class );

		$user = User::newFromName( 'UserWithoutPermission' );

		$params = array( 'from' => 'Q11', 'to' => 'Q12' );
		$this->callApiModule( $params, $user );
	}

	public function testModuleFlags() {
		$module = $this->newApiModule( array() );

		$this->assertTrue( $module->mustBePosted(), 'mustBePosted' );
		$this->assertTrue( $module->isWriteMode(), 'isWriteMode' );
		$this->assertEquals( $module->needsToken(), 'csrf', 'needsToken' );
		$this->assertEquals( $module->getTokenSalt(), '', 'getTokenSalt' );

		//NOTE: Would be nice to test the token check directly, but that is done via
		//      ApiMain::execute, which is bypassed by callApiModule().
	}

}
