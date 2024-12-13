<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Specials;

use Exception;
use MediaWiki\Language\RawMessage;
use MediaWiki\MainConfigNames;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Request\WebResponse;
use MediaWiki\Status\Status;
use MediaWiki\Tests\User\TempUser\TempUserTestTrait;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PHPUnit\Framework\Error\Error;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\MessageException;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\Interactors\RedirectCreationException;
use Wikibase\Repo\Interactors\TokenCheckException;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Specials\SpecialRedirectEntity;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Tests\EntityModificationTestHelper;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\SpecialRedirectEntity
 * @covers \Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class SpecialRedirectEntityTest extends SpecialPageTestBase {

	use HtmlAssertionHelpers;
	use TempUserTestTrait;

	private ?MockRepository $mockRepository = null;
	private ?EntityModificationTestHelper $entityModificationTestHelper = null;

	protected function setUp(): void {
		parent::setUp();

		$this->entityModificationTestHelper = new EntityModificationTestHelper();

		$this->mockRepository = $this->entityModificationTestHelper->getMockRepository();

		$this->entityModificationTestHelper->putEntities( [
			'Q1' => [],
			'Q2' => [],
			'P1' => [ 'datatype' => 'string' ],
		] );
	}

	public function getMockEditFilterHookRunner(): EditFilterHookRunner {
		$mock = $this->getMockBuilder( EditFilterHookRunner::class )
			->onlyMethods( [ 'run' ] )
			->disableOriginalConstructor()
			->getMock();
		$mock->method( 'run' )
			->willReturn( Status::newGood() );
		return $mock;
	}

	private function getMockEntityTitleLookup(): EntityTitleStoreLookup {
		$titleLookup = $this->createMock( EntityTitleStoreLookup::class );

		$titleLookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				$title = $this->createMock( Title::class );
				$title->method( 'isDeleted' )
					->willReturn( false );
				return $title;
			} );

		return $titleLookup;
	}

	protected function newSpecialPage(): SpecialRedirectEntity {
		$exceptionLocalizer = $this->createMock( ExceptionLocalizer::class );
		$exceptionLocalizer->method( 'getExceptionMessage' )
			->willReturnCallback( function( Exception $ex ) {
				if ( $ex instanceof Error ) {
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
			} );

		return new SpecialRedirectEntity(
			WikibaseRepo::getAnonymousEditWarningBuilder(),
			WikibaseRepo::getEntityIdParser(),
			$exceptionLocalizer,
			new ItemRedirectCreationInteractor(
				$this->mockRepository,
				$this->mockRepository,
				$this->getPermissionCheckers(),
				WikibaseRepo::getSummaryFormatter(),
				$this->getMockEditFilterHookRunner(),
				$this->mockRepository,
				$this->getMockEntityTitleLookup(),
				$this->getServiceContainer()->getTempUserCreator()
			),
			WikibaseRepo::getTokenCheckInteractor()
		);
	}

	private function executeSpecialEntityRedirect( array $params, ?User $user = null ): string {
		if ( !$user ) {
			// TODO Matching the token of a non-anonymous user is complicated.
			$user = new User;
			$this->setMwGlobals(
				'wgGroupPermissions',
				[ '*' => [ 'item-redirect' => true, 'edit' => true ] ]
			);
		}

		if ( !isset( $params['wpEditToken'] ) ) {
			$params['wpEditToken'] = $user->getEditToken();
		}

		$request = new FauxRequest( $params, true );

		[ $html ] = $this->executeSpecialPage( '', $request, null, $user );
		return $html;
	}

	public function testAllFormFieldsAreRendered() {
		$output = $this->executeSpecialEntityRedirect( [] );

		$this->assertNoError( $output );
		$this->assertHtmlContainsInputWithName( $output, 'fromid' );
		$this->assertHtmlContainsInputWithName( $output, 'toid' );
		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testSuccessMessageShown(): void {
		$output = $this->executeSpecialEntityRedirect( [
			'fromid' => 'Q1',
			'toid' => 'Q2',
			'success' => 1,
		] );

		$this->assertNoError( $output );
		$this->assertStringContainsString( '(wikibase-redirectentity-success: Q1, Q2)', $output );
	}

	private function getPermissionCheckers(): EntityPermissionChecker {
		$permissionChecker = $this->createMock( EntityPermissionChecker::class );

		$permissionChecker->method( 'getPermissionStatusForEntityId' )
			->willReturnCallback( function( User $user ) {
				$name = 'UserWithoutPermission';
				if ( $user->getName() === $name ) {
					return Status::newFatal( 'permissiondenied' );
				} else {
					return Status::newGood();
				}
			} );

		return $permissionChecker;
	}

	private function assertError( string $error, string $html ): void {
		$this->assertStringContainsString( '<p class="error">(@' . $error . '@)</p>', $html );
	}

	private function assertNoError( string $html ): void {
		$this->assertStringNotContainsString( 'class="error"', $html );
	}

	public function testRedirectRequest() {
		$params = [
			'fromid' => 'Q1',
			'toid' => 'Q2',
		];

		$targetItemContent = [ 'labels' => [
			'en' => [ 'language' => 'en', 'value' => 'Item 2' ],
		] ];

		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( [], 'Q1' );
		$this->entityModificationTestHelper->putEntity( $targetItemContent, 'Q2' );

		// -- do the request --------------------------------------------
		$html = $this->executeSpecialEntityRedirect( $params );

		$this->assertNoError( $html );
		$this->assertStringContainsString( '(wikibase-redirectentity-success: Q1, Q2)', $html,
			'Expected success message' );

		// -- check the items --------------------------------------------
		$actualFrom = $this->entityModificationTestHelper->getEntity( 'Q1', true );
		$this->entityModificationTestHelper->assertEntityEquals( $targetItemContent, $actualFrom );

		$actualTo = $this->entityModificationTestHelper->getEntity( 'Q2' );
		$this->entityModificationTestHelper->assertEntityEquals( $targetItemContent, $actualTo );
	}

	public static function provideExceptionParamsData(): iterable {
		return [
			[ //toid bad
				'p' => [ 'fromid' => 'Q1', 'toid' => 'ABCDE' ],
				'e' => 'Wikibase\Lib\UserInputException:wikibase-wikibaserepopage-invalid-id' ],
			[ //fromid bad
				'p' => [ 'fromid' => 'ABCDE', 'toid' => 'Q1' ],
				'e' => 'Wikibase\Lib\UserInputException:wikibase-wikibaserepopage-invalid-id' ],
			[ //from id is property
				'p' => [ 'fromid' => 'P1', 'toid' => 'Q1' ],
				'e' => 'Wikibase\Repo\Interactors\RedirectCreationException:wikibase-redirect-target-is-incompatible' ],
			[ //to id is property
				'p' => [ 'fromid' => 'Q1', 'toid' => 'P1' ],
				'e' => 'Wikibase\Repo\Interactors\RedirectCreationException:wikibase-redirect-target-is-incompatible' ],
			[ //bad token
				'p' => [ 'fromid' => 'Q1', 'toid' => 'Q2', 'wpEditToken' => 'BAD' ],
				'e' => 'Wikibase\Repo\Interactors\TokenCheckException:wikibase-tokencheck-badtoken' ],
		];
	}

	/**
	 * @dataProvider provideExceptionParamsData
	 */
	public function testEntityRedirectParamsExceptions( array $params, string $expected ): void {
		$html = $this->executeSpecialEntityRedirect( $params );
		$this->assertError( $expected, $html );
	}

	public function testEntityRedirectNonExistingEntities() {
		$params = [
			'fromid' => 'Q60457977',
			'toid' => 'Q60457978',
		];

		$html = $this->executeSpecialEntityRedirect( $params );
		$this->assertError( 'Wikibase\Repo\Interactors\RedirectCreationException:wikibase-redirect-no-such-entity', $html );
	}

	public function testNoPermission() {
		$params = [
			'fromid' => 'Q1',
			'toid' => 'Q2',
		];

		$user = User::newFromName( 'UserWithoutPermission' );

		$html = $this->executeSpecialEntityRedirect( $params, $user );
		$this->assertError( 'Wikibase\Repo\Interactors\RedirectCreationException:wikibase-redirect-permissiondenied', $html );
	}

	public function testTempUserCreatedRedirect(): void {
		$this->enableAutoCreateTempUser();
		$this->overrideConfigValue( MainConfigNames::LanguageCode, 'en' );

		$params = [
			'fromid' => 'Q1',
			'toid' => 'Q2',
		];
		$request = new FauxRequest( $params, true );
		$this->setTemporaryHook( 'TempUserCreatedRedirect', function (
			$session,
			$user,
			string $returnTo,
			string $returnToQuery,
			string $returnToAnchor,
			&$redirectUrl
		) {
			$this->assertSame( 'Special:RedirectEntity', $returnTo );
			$this->assertSame( 'fromid=Q1&toid=Q2&success=1', $returnToQuery );
			$this->assertSame( '', $returnToAnchor );
			$redirectUrl = 'https://wiki.example/';
		} );

		/** @var WebResponse $response */
		[ , $response ] = $this->executeSpecialPage( '', $request );

		$this->assertSame( 'https://wiki.example/', $response->getHeader( 'location' ) );
	}

}
