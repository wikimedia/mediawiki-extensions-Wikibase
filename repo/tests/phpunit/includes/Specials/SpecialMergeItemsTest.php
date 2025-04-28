<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Specials;

use Exception;
use MediaWiki\Language\RawMessage;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Request\WebResponse;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Status\Status;
use MediaWiki\Tests\Site\TestSites;
use MediaWiki\Tests\User\TempUser\TempUserTestTrait;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use PermissionsError;
use PHPUnit\Framework\Error\Error;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\MessageException;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\EditEntity\MediaWikiEditEntityFactory;
use Wikibase\Repo\Interactors\ItemMergeException;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\Interactors\TokenCheckException;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Merge\MergeFactory;
use Wikibase\Repo\Specials\SpecialMergeItems;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Tests\EntityModificationTestHelper;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Stats\StatsFactory;

/**
 * @covers \Wikibase\Repo\Specials\SpecialMergeItems
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
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Daniel Kinzler
 * @author Lucie-Aimée Kaffee
 */
class SpecialMergeItemsTest extends SpecialPageTestBase {

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
			'P2' => [ 'datatype' => 'string' ],
		] );

		$this->entityModificationTestHelper->putRedirects( [
			'Q11' => 'Q1',
			'Q12' => 'Q2',
		] );
	}

	public function getMockEditFilterHookRunner(): EditFilterHookRunner {
		$mock = $this->createMock( EditFilterHookRunner::class );

		$mock->method( 'run' )
			->willReturn( Status::newGood() );

		return $mock;
	}

	private function getEntityTitleLookup(): EntityTitleStoreLookup {
		$entityTitleLookup = $this->createMock( EntityTitleStoreLookup::class );
		$entityTitleLookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $entityId ) {
				return Title::newFromTextThrow( $entityId->getSerialization() );
			} );

		return $entityTitleLookup;
	}

	protected function newSpecialPage(): SpecialMergeItems {
		$services = MediaWikiServices::getInstance();
		$summaryFormatter = WikibaseRepo::getSummaryFormatter( $services );

		$mergeFactory = new MergeFactory(
			WikibaseRepo::getEntityConstraintProvider( $services ),
			WikibaseRepo::getChangeOpFactoryProvider( $services ),
			new HashSiteStore( TestSites::getSites() )
		);

		$exceptionLocalizer = $this->createMock( ExceptionLocalizer::class );
		$exceptionLocalizer->method( 'getExceptionMessage' )
			->willReturnCallback( function( Exception $ex ) {
				if ( $ex instanceof Error ) {
					throw $ex;
				}

				$text = get_class( $ex );

				if ( $ex instanceof MessageException ) {
					$text .= ':' . $ex->getKey();
				} elseif ( $ex instanceof ItemMergeException ) {
					$text .= ':' . $ex->getErrorCode();
				} elseif ( $ex instanceof TokenCheckException ) {
					$text .= ':' . $ex->getErrorCode();
				} else {
					$text .= ':"' . $ex->getMessage() . '"';
				}

				return new RawMessage( '(@' . $text . '@)' );
			} );

		$titleLookup = $this->getEntityTitleLookup();
		$editFilterHookRunner = $this->getMockEditFilterHookRunner();
		$permissionChecker = $this->getPermissionCheckers();
		$tempUserCreator = $services->getTempUserCreator();
		$editEntityFactory = new MediaWikiEditEntityFactory(
			$titleLookup,
			$this->mockRepository,
			$this->mockRepository,
			$permissionChecker,
			WikibaseRepo::getEntityDiffer( $services ),
			WikibaseRepo::getEntityPatcher( $services ),
			$editFilterHookRunner,
			StatsFactory::newNull(),
			$services->getUserOptionsLookup(),
			$tempUserCreator,
			4096,
			[ 'item' ]
		);
		$specialPage = new SpecialMergeItems(
			WikibaseRepo::getAnonymousEditWarningBuilder( $services ),
			WikibaseRepo::getEntityIdParser( $services ),
			$titleLookup,
			$exceptionLocalizer,
			new ItemMergeInteractor(
				$mergeFactory,
				$this->mockRepository,
				$editEntityFactory,
				$permissionChecker,
				$summaryFormatter,
				new ItemRedirectCreationInteractor(
					$this->mockRepository,
					$this->mockRepository,
					$permissionChecker,
					$summaryFormatter,
					$editFilterHookRunner,
					$this->mockRepository,
					$this->getMockEntityTitleLookup(),
					$tempUserCreator
				),
				$titleLookup,
				$services->getPermissionManager()
			),
			false,
			WikibaseRepo::getTokenCheckInteractor( $services )
		);

		$linkRenderer = $this->createMock( LinkRenderer::class );
		$linkRenderer->method( 'makeKnownLink' )
			->willReturnArgument( 1 );
		$linkRenderer->method( 'makePreloadedLink' )
			->willReturnArgument( 1 );
		$specialPage->setLinkRenderer( $linkRenderer );

		return $specialPage;
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

	private function executeSpecialMergeItems( array $params, ?User $user = null ): string {
		if ( !$user ) {
			// TODO Matching the token of a non-anonymous user is complicated.
			$user = new User;
			$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [ 'item-merge' => true, 'edit' => true ] ] );
		}

		if ( !isset( $params['wpEditToken'] ) ) {
			$params['wpEditToken'] = $user->getEditToken();
		}

		$request = new FauxRequest( $params, true );

		[ $html ] = $this->executeSpecialPage( '', $request, null, $user );
		return $html;
	}

	public function testAllFormFieldsAreRendered() {
		$output = $this->executeSpecialMergeItems( [] );

		$this->assertNoError( $output );

		$this->assertHtmlContainsInputWithName( $output, 'fromid' );
		$this->assertHtmlContainsInputWithName( $output, 'toid' );
		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testSuccessMessageShown(): void {
		$output = $this->executeSpecialMergeItems( [
			'fromid' => 'Q1',
			'toid' => 'Q2',
			'success' => '3|4',
		] );

		$this->assertNoError( $output );
		$this->assertStringContainsString( '(wikibase-mergeitems-success: Q1, 3, Q2, 4)', $output );
	}

	private function getPermissionCheckers(): EntityPermissionChecker {
		$permissionChecker = $this->createMock( EntityPermissionChecker::class );

		$callback = function ( User $user ) {
			$name = 'UserWithoutPermission';
			if ( $user->getName() === $name ) {
				return Status::newFatal( 'permissiondenied' );
			} else {
				return Status::newGood();
			}
		};
		$permissionChecker->method( 'getPermissionStatusForEntityId' )
			->willReturnCallback( $callback );
		$permissionChecker->method( 'getPermissionStatusForEntity' )
			->willReturnCallback( $callback );

		return $permissionChecker;
	}

	private function assertError( string $error, string $html ): void {
		$this->assertStringContainsString( '<p class="error">(@' . $error . '@)</p>', $html );
	}

	private function assertNoError( string $html ): void {
		$this->assertStringNotContainsString( 'class="error"', $html );
	}

	public static function mergeRequestProvider(): iterable {
		$testCases = [];
		$testCases['labelMerge'] = [
			[ 'labels' => [
				'en' => [ 'language' => 'en', 'value' => 'foo' ],
			] ],
			[],
			[],
			[ 'labels' => [
				'en' => [ 'language' => 'en', 'value' => 'foo' ],
			] ],
		];
		$testCases['IgnoreConflictSitelinksMerge'] = [
			[ 'sitelinks' => [
				'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainFrom' ],
				'enwiki' => [ 'site' => 'enwiki', 'title' => 'PlFrom' ],
			] ],
			[ 'sitelinks' => [
				'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainTo' ],
			] ],
			[ 'sitelinks' => [
				'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainFrom' ],
			] ],
			[ 'sitelinks' => [
				'dewiki' => [ 'site' => 'dewiki', 'title' => 'RemainTo' ],
				'enwiki' => [ 'site' => 'enwiki', 'title' => 'PlFrom' ],
			] ],
			'sitelink|foo',
		];

		$statement = [
			'mainsnak' => [
				'snaktype' => 'value',
				'property' => 'P1',
				'datavalue' => [ 'value' => 'imastring', 'type' => 'string' ],
			],
			'type' => 'statement',
			'rank' => 'normal',
			'id' => 'deadbeefdeadbeefdeadbeefdeadbeef',
		];

		$statementWithoutId = $statement;
		unset( $statementWithoutId['id'] );

		$testCases['claimMerge'] = [
			[ 'claims' => [ 'P1' => [ $statement ] ] ],
			[],
			[],
			[ 'claims' => [ 'P1' => [ $statementWithoutId ] ] ],
		];

		return $testCases;
	}

	/**
	 * @dataProvider mergeRequestProvider
	 */
	public function testMergeRequest(
		array $fromBefore,
		array $toBefore,
		array $fromAfter,
		array $toAfter,
		string $ignoreConflicts = ''
	): void {

		// -- set up params ---------------------------------
		$params = [
			'fromid' => 'Q1',
			'toid' => 'Q2',
			'summary' => 'CustomSummary!',
			'ignoreconflicts' => $ignoreConflicts,
		];

		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( $fromBefore, 'Q1' );
		$this->entityModificationTestHelper->putEntity( $toBefore, 'Q2' );

		// -- do the request --------------------------------------------
		$html = $this->executeSpecialMergeItems( $params );

		// -- check the result --------------------------------------------
		$this->assertNoError( $html );
		$this->assertMatchesRegularExpression( '!\(wikibase-mergeitems-success: Q1, \d+, Q2, \d+\)!', $html, 'Expected success message' );

		// -- check the items --------------------------------------------
		$actualFrom = $this->entityModificationTestHelper->getEntity( 'Q1', true );
		$this->entityModificationTestHelper->assertEntityEquals( $fromAfter, $actualFrom );

		$actualTo = $this->entityModificationTestHelper->getEntity( 'Q2', true );
		$this->entityModificationTestHelper->assertEntityEquals( $toAfter, $actualTo );
	}

	public static function provideExceptionParamsData(): iterable {
		return [
			[ //3 toid bad
				'p' => [ 'fromid' => 'Q1', 'toid' => 'ABCDE' ],
				'e' => 'Wikibase\Lib\UserInputException:wikibase-wikibaserepopage-invalid-id' ],
			[ //4 fromid bad
				'p' => [ 'fromid' => 'ABCDE', 'toid' => 'Q1' ],
				'e' => 'Wikibase\Lib\UserInputException:wikibase-wikibaserepopage-invalid-id' ],
			[ //5 both same id
				'p' => [ 'fromid' => 'Q1', 'toid' => 'Q1' ],
				'e' => 'Wikibase\Repo\Interactors\ItemMergeException:wikibase-itemmerge-cant-merge-self' ],
			[ //6 from id is property
				'p' => [ 'fromid' => 'P1', 'toid' => 'Q1' ],
				'e' => 'Wikibase\Lib\UserInputException:wikibase-itemmerge-not-item' ],
			[ //7 to id is property
				'p' => [ 'fromid' => 'Q1', 'toid' => 'P1' ],
				'e' => 'Wikibase\Lib\UserInputException:wikibase-itemmerge-not-item' ],
			[ //10 bad token
				'p' => [ 'fromid' => 'Q1', 'toid' => 'Q2', 'wpEditToken' => 'BAD' ],
				'e' => 'Wikibase\Repo\Interactors\TokenCheckException:wikibase-tokencheck-badtoken' ],
		];
	}

	/**
	 * @dataProvider provideExceptionParamsData
	 */
	public function testMergeItemsParamsExceptions( array $params, string $expected ): void {
		$html = $this->executeSpecialMergeItems( $params );
		$this->assertError( $expected, $html );
	}

	public static function provideExceptionConflictsData(): iterable {
		return [
			[
				[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo' ] ] ],
				[ 'descriptions' => [ 'en' => [ 'language' => 'en', 'value' => 'foo2' ] ] ],
			],
			[
				[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'Foo' ] ] ],
				[ 'sitelinks' => [ 'dewiki' => [ 'site' => 'dewiki', 'title' => 'Foo2' ] ] ],
			],
		];
	}

	/**
	 * @dataProvider provideExceptionConflictsData
	 */
	public function testMergeItemsConflictsExceptions( array $pre1, array $pre2 ): void {
		// -- prefill the entities --------------------------------------------
		$this->entityModificationTestHelper->putEntity( $pre1, 'Q1' );
		$this->entityModificationTestHelper->putEntity( $pre2, 'Q2' );

		$params = [
			'fromid' => 'Q1',
			'toid' => 'Q2',
		];

		// -- do the request --------------------------------------------
		$html = $this->executeSpecialMergeItems( $params );
		$this->assertError( 'Wikibase\Repo\Interactors\ItemMergeException:wikibase-itemmerge-failed-modify', $html );
	}

	public function testMergeNonExistingItem() {
		$params = [
			'fromid' => 'Q60457977',
			'toid' => 'Q60457978',
		];

		$html = $this->executeSpecialMergeItems( $params );
		$this->assertError( 'Wikibase\Repo\Interactors\ItemMergeException:wikibase-itemmerge-no-such-entity', $html );
	}

	public function testCanNotMergeRedirects() {
		$params = [
			'fromid' => 'Q11',
			'toid' => 'Q2',
		];

		$html = $this->executeSpecialMergeItems( $params );
		$this->assertStringContainsString( '<p class="error">(wikibase-itemmerge-redirect)</p>', $html );
	}

	public function testNoSpecialPagePermission() {
		$params = [
			'fromid' => 'Q1',
			'toid' => 'Q2',
		];
		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [
			'item-merge' => false,
		] ] );

		$this->expectException( PermissionsError::class );

		$html = $this->executeSpecialMergeItems( $params, $this->getTestUser()->getUser() );
	}

	public function testMergePermission() {
		$params = [
			'fromid' => 'Q1',
			'toid' => 'Q2',
		];

		$user = User::newFromName( 'UserWithoutPermission' );

		$html = $this->executeSpecialMergeItems( $params, $user );
		$this->assertError( 'Wikibase\Repo\Interactors\ItemMergeException:wikibase-itemmerge-permissiondenied', $html );
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
			$this->assertSame( 'Special:MergeItems', $returnTo );
			$this->assertStringStartsWith( 'fromid=Q1&toid=Q2&success=', $returnToQuery );
			$this->assertSame( '', $returnToAnchor );
			$redirectUrl = 'https://wiki.example/';
		} );

		/** @var WebResponse $response */
		[ , $response ] = $this->executeSpecialPage( '', $request );

		$this->assertSame( 'https://wiki.example/', $response->getHeader( 'location' ) );
	}

}
