<?php

namespace Wikibase\Repo\Tests\Specials;

use Exception;
use FauxRequest;
use PHPUnit_Framework_Error;
use RawMessage;
use SpecialPageTestBase;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\MessageException;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikibase\Repo\Interactors\RedirectCreationInteractor;
use Wikibase\Repo\Interactors\TokenCheckException;
use Wikibase\Repo\Interactors\TokenCheckInteractor;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Specials\SpecialRedirectEntity;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Repo\Tests\EntityModificationTestHelper;
use Wikibase\Lib\Tests\MockRepository;

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

	use HtmlAssertionHelpers;

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

	/**
	 * @return SpecialRedirectEntity
	 */
	protected function newSpecialPage() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$exceptionLocalizer = $this->getMock( ExceptionLocalizer::class );
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

		return new SpecialRedirectEntity(
			$wikibaseRepo->getEntityIdParser(),
			$exceptionLocalizer,
			new TokenCheckInteractor( $this->user ),
			new RedirectCreationInteractor(
				$this->mockRepository,
				$this->mockRepository,
				$this->getPermissionCheckers(),
				$wikibaseRepo->getSummaryFormatter(),
				$this->user,
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

	public function testAllFormFieldsAreRendered() {
		$output = $this->executeSpecialEntityRedirect( array() );

		$this->assertNoError( $output );
		$this->assertHtmlContainsInputWithName( $output, 'fromid' );
		$this->assertHtmlContainsInputWithName( $output, 'toid' );
		$this->assertHtmlContainsSubmitControl( $output );
	}

	/**
	 * @return EntityPermissionChecker
	 */
	private function getPermissionCheckers() {
		$permissionChecker = $this->getMock( EntityPermissionChecker::class );

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

	/**
	 * @param string $error
	 * @param string $html
	 */
	private function assertError( $error, $html ) {
		$this->assertContains( '<p class="error">(@' . $error . '@)</p>', $html );
	}

	/**
	 * @param string $html
	 */
	private function assertNoError( $html ) {
		$this->assertNotContains( 'class="error"', $html );
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
