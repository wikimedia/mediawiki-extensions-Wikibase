<?php

namespace Wikibase\Repo\Tests\Specials;

use Exception;
use FauxRequest;
use PHPUnit_Framework_Error;
use RawMessage;
use SpecialPageTestBase;
use Status;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\MessageException;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Interactors\TokenCheckException;
use Wikibase\Repo\Interactors\TokenCheckInteractor;
use Wikibase\Repo\Specials\SpecialRedirectEntity;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Test\EntityModificationTestHelper;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\Repo\Specials\SpecialRedirectEntity
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class SpecialRedirectEntityTest extends SpecialPageTestBase {

	/**
	 * @var MockRepository|null
	 */
	private $mockRepository = null;

	/**
	 * @var User|null
	 */
	private $user = null;

	/**
	 * @var EntityModificationTestHelper|null
	 */
	private $entityModificationTestHelper = null;

	protected function setUp() {
		parent::setUp();

		$this->entityModificationTestHelper = new EntityModificationTestHelper();

		$this->mockRepository = $this->entityModificationTestHelper->getMockRepository();

		$this->entityModificationTestHelper->putEntities( array(
			'Q1' => array(),
			'Q2' => array(),
			'P1' => array( 'datatype' => 'string' ),
		) );
	}

	protected function newSpecialPage() {
		$specialPage = new SpecialRedirectEntity();
		$this->overrideServices( $specialPage, $this->user );

		return $specialPage;
	}

	public function getMockEditFilterHookRunner() {
		$mock = $this->getMockBuilder( 'Wikibase\Repo\Hooks\EditFilterHookRunner' )
			->setMethods( array( 'run' ) )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'run' )
			->will( $this->returnValue( Status::newGood() ) );
		return $mock;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockEntityTitleLookup() {
		$titleLookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );

		$titleLookup->expects( $this->any() )
			->method( 'getTitleForID' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$title = $this->getMock( 'Title' );
				$title->expects( $this->any() )
					->method( 'isDeleted' )
					->will( $this->returnValue( false ) );
				return $title;
			} ) );

		return $titleLookup;
	}

	/**
	 * @param SpecialRedirectEntity $page
	 * @param User $user
	 */
	private function overrideServices( SpecialRedirectEntity $page, User $user ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$exceptionLocalizer = $this->getMock( 'Wikibase\Repo\Localizer\ExceptionLocalizer' );
		$exceptionLocalizer->expects( $this->any() )
			->method( 'getExceptionMessage' )
			->will( $this->returnCallback( function( Exception $ex ) {
				if ( $ex instanceof PHPUnit_Framework_Error ) {
					throw $ex;
				}

				$text = get_class( $ex );

				if ( $ex instanceof MessageException ) {
					$text .= ':' . $ex->getKey();
				} elseif ( $ex instanceof RedirectCreationException ) {
					$text .= ':' . $ex->getErrorCode();
				} elseif ( $ex instanceof TokenCheckException ) {
					$text .= ':' . $ex->getErrorCode();
				} else {
					$text .= ':"' . $ex->getMessage() . '"';
				}

				return new RawMessage( '(@' . $text . '@)' );
			} ) );

		$page->initServices(
			$wikibaseRepo->getEntityIdParser(),
			$exceptionLocalizer,
			new TokenCheckInteractor( $user ),
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
	}

	private function executeSpecialEntityRedirect( array $params, User $user = null ) {
		if ( !$user ) {
			$user = $GLOBALS['wgUser'];
			$this->setMwGlobals(
				'wgGroupPermissions',
				array( '*' => array( 'item-redirect' => true, 'edit' => true ) )
			);
		}

		// HACK: we need this in newSpecialPage, but executeSpecialPage doesn't pass the context on.
		$this->user = $user;

		if ( !isset( $params['wpEditToken'] ) ) {
			$params['wpEditToken'] = $user->getEditToken();
		}

		$request = new FauxRequest( $params, true );
		list( $html, ) = $this->executeSpecialPage( '', $request, 'qqx' );
		return $html;
	}

	public function testForm() {
		$matchers['fromid'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'wb-redirectentity-fromid',
			),
			'child' => array(
				'tag' => 'input',
				'attributes' => array(
					'name' => 'fromid',
				)
			) );
		$matchers['toid'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'wb-redirectentity-toid',
			),
			'child' => array(
				'tag' => 'input',
				'attributes' => array(
					'name' => 'toid',
				)
			) );
		$matchers['submit'] = array(
			'tag' => 'div',
			'attributes' => array(
				'id' => 'wb-redirectentity-submit',
			),
			'child' => array(
				'tag' => 'button',
				'attributes' => array(
					'type' => 'submit',
					'name' => 'wikibase-redirectentity-submit',
				)
			) );

		$output = $this->executeSpecialEntityRedirect( array() );

		$this->assertNoError( $output );

		foreach ( $matchers as $key => $matcher ) {
			$this->assertTag( $matcher, $output, "Failed to match html output with tag '{$key}''" );
		}
	}

	private function getPermissionCheckers() {
		$permissionChecker = $this->getMock( 'Wikibase\Repo\Store\EntityPermissionChecker' );

		$permissionChecker->expects( $this->any() )
			->method( 'getPermissionStatusForEntityId' )
			->will( $this->returnCallback( function( User $user, $permission, EntityId $id ) {
				$name = 'UserWithoutPermission-' . $permission;
				if ( $user->getName() === $name ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} ) );

		return $permissionChecker;
	}

	private function assertError( $error, $html ) {
		// Match error strings as generated by the ExceptionLocalizer mock!
		if ( !preg_match( '!<p class="error">\(@(.*?)@\)</p>!', $html, $m ) ) {
			$this->fail( 'Expected error ' . $error . '. No error found in page!' );
		}

		$this->assertEquals( $error, $m[1], 'Expected error ' . $error );
	}

	private function assertNoError( $html ) {
		$pattern = '!<p class="error"!';
		$this->assertNotRegExp( $pattern, $html, 'Expected no error!' );
	}

	public function testRedirectRequest() {
		$params = array(
			'fromid' => 'Q1',
			'toid' => 'Q2',
		);

		$targetItemContent = array( 'labels' => array(
			'en' => array( 'language' => 'en', 'value' => 'Item 2' )
		) );

		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( array(), 'Q1' );
		$this->entityModificationTestHelper->putEntity( $targetItemContent, 'Q2' );

		// -- do the request --------------------------------------------
		$html = $this->executeSpecialEntityRedirect( $params );

		$this->assertNoError( $html );
		$this->assertContains( '(wikibase-redirectentity-success: Q1, Q2)', $html,
			'Expected success message' );

		// -- check the items --------------------------------------------
		$actualFrom = $this->entityModificationTestHelper->getEntity( 'Q1', true );
		$this->entityModificationTestHelper->assertEntityEquals( $targetItemContent, $actualFrom );

		$actualTo = $this->entityModificationTestHelper->getEntity( 'Q2' );
		$this->entityModificationTestHelper->assertEntityEquals( $targetItemContent, $actualTo );
	}

	public function provideExceptionParamsData() {
		return array(
			array( //toid bad
				'p' => array( 'fromid' => 'Q1', 'toid' => 'ABCDE' ),
				'e' => 'Wikibase\Lib\UserInputException:wikibase-wikibaserepopage-invalid-id' ),
			array( //fromid bad
				'p' => array( 'fromid' => 'ABCDE', 'toid' => 'Q1' ),
				'e' => 'Wikibase\Lib\UserInputException:wikibase-wikibaserepopage-invalid-id' ),
			array( //from id is property
				'p' => array( 'fromid' => 'P1', 'toid' => 'Q1' ),
				'e' => 'Wikibase\Repo\Interactors\RedirectCreationException:target-is-incompatible' ),
			array( //to id is property
				'p' => array( 'fromid' => 'Q1', 'toid' => 'P1' ),
				'e' => 'Wikibase\Repo\Interactors\RedirectCreationException:target-is-incompatible' ),
			array( //bad token
				'p' => array( 'fromid' => 'Q1', 'toid' => 'Q2', 'wpEditToken' => 'BAD' ),
				'e' => 'Wikibase\Repo\Interactors\TokenCheckException:wikibase-tokencheck-badtoken' ),
		);
	}

	/**
	 * @dataProvider provideExceptionParamsData
	 */
	public function testEntityRedirectParamsExceptions( array $params, $expected ) {
		$html = $this->executeSpecialEntityRedirect( $params );
		$this->assertError( $expected, $html );
	}

	public function testEntityRedirectNonExistingEntities() {
		$params = array(
			'fromid' => 'Q60457977',
			'toid' => 'Q60457978'
		);

		$html = $this->executeSpecialEntityRedirect( $params );
		$this->assertError( 'Wikibase\Repo\Interactors\RedirectCreationException:no-such-entity', $html );
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
	public function testNoPermission( $permission ) {
		$params = array(
			'fromid' => 'Q1',
			'toid' => 'Q2'
		);
		$this->setMwGlobals( 'wgGroupPermissions', array( '*' => array(
			'item-redirect' => $permission !== 'item-redirect',
			'edit' => $permission !== 'edit'
		) ) );

		$user = User::newFromName( 'UserWithoutPermission-' . $permission );

		$html = $this->executeSpecialEntityRedirect( $params, $user );
		$this->assertError( 'Wikibase\Repo\Interactors\RedirectCreationException:permissiondenied', $html );
	}

}
