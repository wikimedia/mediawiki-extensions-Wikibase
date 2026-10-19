<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Specials;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Context\RequestContext;
use MediaWiki\Exception\PermissionsError;
use MediaWiki\Extension\ConfirmEdit\CaptchaTriggers;
use MediaWiki\Extension\ConfirmEdit\SimpleCaptcha\SimpleCaptcha;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaSessionStore;
use MediaWiki\Extension\ConfirmEdit\Store\CaptchaStore;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Http\MWHttpRequest;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Session\Session;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Status\Status;
use MediaWiki\Tests\Site\TestSites;
use MediaWiki\Tests\Specials\SpecialPageTestBase;
use MediaWiki\Tests\User\TempUser\TempUserTestTrait;
use MediaWiki\Title\Title;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\SiteLinkPageNormalizer;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;

/**
 * @covers \Wikibase\Repo\Specials\SpecialModifyEntity
 * @covers \Wikibase\Repo\Specials\SpecialModifyTerm
 * @covers \Wikibase\Repo\Specials\SpecialSetLabel
 * @covers \Wikibase\Repo\Specials\SpecialSetDescription
 * @covers \Wikibase\Repo\Specials\SpecialSetAliases
 * @covers \Wikibase\Repo\Specials\SpecialSetLabelDescriptionAliases
 * @covers \Wikibase\Repo\Specials\SpecialSetSiteLink
 * @covers \Wikibase\Repo\Hooks\ConfirmEditGetGlobalInstanceFromContextHookHandler
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class SpecialModifyEntityCaptchaTest extends SpecialPageTestBase {
	use TempUserTestTrait;

	private string $pageName = 'SetLabel';
	private Item $item;

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws PermissionsError
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->markTestSkippedIfExtensionNotLoaded( 'ConfirmEdit' );
		$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )->unsetGlobalInstancesForTests();
		$this->overrideConfigValues( [
			'GroupPermissions' => [ '*' => [ 'read' => true, 'edit' => true, 'item-term' => true ] ],
			'CaptchaClass' => SimpleCaptcha::class,
			'CaptchaTriggers' => [
				'edit' => [
					'trigger' => true,
					'class' => 'HCaptcha',
					'config' => [ 'HCaptchaSiteKey' => 'edit-site-key' ],
				],
				'create' => false,
				'addurl' => false,
			],
			'CaptchaTriggersOnNamespace' => [],
			'CaptchaBypassIPs' => [],
			'HCaptchaSiteKey' => 'default-site-key',
			'HCaptchaSecretKey' => 'test-secret',
			'HCaptchaInvisibleMode' => false,
			'HCaptchaEnterprise' => false,
			'HCaptchaSecureEnclave' => false,
			'HCaptchaVerifyUrl' => 'https://hcaptcha.test/siteverify',
		] );

		$settings = clone WikibaseRepo::getSettings( $this->getServiceContainer() );
		$settings->setSetting( 'siteLinkGroups', [ 'wikipedia' ] );
		$this->setService( 'WikibaseRepo.Settings', $settings );
		$this->setHCaptchaIntegration( true );
		$this->setService( 'SiteLookup', new HashSiteStore( TestSites::getSites() ) );
		$normalizer = $this->createStub( SiteLinkPageNormalizer::class );
		$normalizer->method( 'normalize' )->willReturnArgument( 1 );
		$this->setService( 'WikibaseRepo.SiteLinkPageNormalizer', $normalizer );

		$this->item = new Item();
		$this->item->setLabel( 'en', 'Original label' );
		$this->item->setDescription( 'en', 'Original description' );
		$this->item->setAliases( 'en', [ 'Original alias' ] );
		WikibaseRepo::getEntityStore()->saveEntity(
			$this->item, 'test', $this->getTestUser()->getUser(), EDIT_NEW
		);
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function tearDown(): void {
		if ( $this->getServiceContainer()->hasService( 'ConfirmEditCaptchaFactory' ) ) {
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )->unsetGlobalInstancesForTests();
			CaptchaStore::unsetInstanceForTests();
		}
		parent::tearDown();
	}

	protected function newSpecialPage() {
		return $this->getServiceContainer()->getSpecialPageFactory()->getPage( $this->pageName );
	}

	public static function providePages(): iterable {
		yield 'label' => [ 'SetLabel', [ 'value' => 'New value' ] ];
		yield 'description' => [ 'SetDescription', [ 'value' => 'New value' ] ];
		yield 'aliases' => [ 'SetAliases', [ 'value' => 'New value' ] ];
		yield 'combined terms' => [ 'SetLabelDescriptionAliases', [
			'label' => 'New value', 'description' => 'New description', 'aliases' => 'New alias',
		] ];
		yield 'sitelink' => [ 'SetSiteLink', [ 'page' => 'New page' ] ];
	}

	public static function provideRendering(): iterable {
		foreach ( self::providePages() as [ $page ] ) {
			yield "$page checkbox" => [ $page, false ];
		}
		yield 'SetLabel invisible' => [ 'SetLabel', true ];
	}

	/** @dataProvider provideRendering */
	public function testCaptchaWidgetAndPrivacyNotice( string $page, bool $invisible ): void {
		$this->pageName = $page;
		$this->overrideConfigValue( 'HCaptchaInvisibleMode', $invisible );
		[ $html ] = $this->executeSpecialPage( '', $this->newRequest() );

		$this->assertSelectorMatchesOneElement( $html, 'form #h-captcha[data-sitekey="edit-site-key"]' );
		$this->assertSelectorMatchesOneElement( $html, 'form .h-captcha-privacy-policy' );
		$this->assertSame( $invisible, str_contains( $html, 'data-size="invisible"' ) );
		$this->assertLessThan( strpos( $html, 'mw-htmlform-submit-buttons' ), strpos( $html, 'class="captcha"' ) );
		$this->assertCount( 0, DOMCompat::querySelectorAll( DOMUtils::parseHTML( $html ), 'label .captcha' ) );
	}

	public function testNoCaptchaWhenFeatureFlagDisabled(): void {
		$this->setHCaptchaIntegration( false );
		[ $html ] = $this->executeSpecialPage( '', $this->newRequest() );
		$this->assertStringNotContainsString( 'h-captcha', $html );
		$this->assertStringNotContainsString( 'class="captcha"', $html );
		$this->assertSelectorMatchesOneElement( $html, 'form button[type="submit"]' );
	}

	public function testNoCaptchaWhenTriggerDisabled(): void {
		$this->overrideConfigValue( 'CaptchaTriggers', [ 'edit' => false ] );
		[ $html ] = $this->executeSpecialPage( '', $this->newRequest() );
		$this->assertStringNotContainsString( 'h-captcha', $html );
		$this->assertStringNotContainsString( 'class="captcha"', $html );
		$this->assertSelectorMatchesOneElement( $html, 'form button[type="submit"]' );
	}

	public function testNoCaptchaWhenUserCanSkip(): void {
		$this->setGroupPermissions( [ '*' => [ 'skipcaptcha' => true ] ] );
		[ $html ] = $this->executeSpecialPage( '', $this->newRequest() );
		$this->assertStringNotContainsString( 'h-captcha', $html );
		$this->assertSelectorMatchesOneElement( $html, 'form button[type="submit"]' );
	}

	public function testNamespaceCanDisableCaptcha(): void {
		$title = WikibaseRepo::getEntityTitleLookup()->getTitleForId( $this->item->getId() );
		$this->overrideConfigValue( 'CaptchaTriggersOnNamespace', [ $title->getNamespace() => [ 'edit' => false ] ] );
		[ $html ] = $this->executeSpecialPage( '', $this->newRequest() );
		$this->assertStringNotContainsString( 'h-captcha', $html );
		$this->assertSelectorMatchesOneElement( $html, 'form button[type="submit"]' );
	}

	public function testCaptchaOnFormWithoutEntityId(): void {
		[ $html ] = $this->executeSpecialPage();
		$this->assertSelectorMatchesOneElement( $html, 'form #h-captcha[data-sitekey="edit-site-key"]' );
	}

	public static function provideSaves(): iterable {
		foreach ( self::providePages() as [ $page, $input ] ) {
			yield "$page valid" => [ $page, $input, 'valid-token', false ];
		}
		$input = [ 'value' => 'New value' ];
		yield 'missing' => [ 'SetLabel', $input, '', false ];
		yield 'invalid' => [ 'SetLabel', $input, 'invalid-token', false ];
		yield 'skipcaptcha' => [ 'SetLabel', $input, '', true ];
		yield 'rejected edit' => [ 'SetLabel', $input, 'valid-token', false, true ];
		yield 'anonymous valid' => [ 'SetLabel', $input, 'valid-token', false, false, true ];
		yield 'anonymous missing' => [ 'SetLabel', $input, '', false, false, true ];
	}

	/** @dataProvider provideSaves */
	public function testSaveRequiresValidCaptcha(
		string $page, array $input, string $token, bool $skip, bool $rejectEdit = false,
		bool $anonymous = false
	): void {
		$this->pageName = $page;
		if ( $anonymous ) {
			$this->disableAutoCreateTempUser();
		}
		$this->setGroupPermissions( [ '*' => [ 'skipcaptcha' => $skip ] ] );
		$success = $token === 'valid-token' || $skip;
		// Only create() is mocked so other services (e.g. EventBus) still get a real createMultiClient().
		$http = $this->getMockBuilder( HttpRequestFactory::class )
			->setConstructorArgs( [
				new ServiceOptions( HttpRequestFactory::CONSTRUCTOR_OPTIONS, $this->getServiceContainer()->getMainConfig() ),
				new NullLogger(),
			] )
			->onlyMethods( [ 'create' ] )
			->getMock();
		if ( $token !== '' ) {
			$response = $this->createStub( MWHttpRequest::class );
			$response->method( 'execute' )->willReturn( Status::newGood() );
			$response->method( 'getContent' )->willReturn( json_encode( [ 'success' => $success ] ) );
			$http->expects( $this->once() )->method( 'create' )->willReturnCallback(
				function ( $url, $options ) use ( $response, $token ) {
					$this->assertSame( 'https://hcaptcha.test/siteverify', $url );
					$this->assertSame( $token, $options['postData']['response'] );
					$this->assertSame( 'edit-site-key', $options['postData']['sitekey'] );
					return $response;
				}
			);
		} else {
			$http->expects( $this->never() )->method( 'create' );
		}
		$this->setService( 'HttpRequestFactory', $http );
		if ( $rejectEdit ) {
			$this->setTemporaryHook( 'EditFilterMergedContent', static function ( $context, $content, $status ) {
				$status->fatal( 'test-rejected-edit' );
			}, false );
		}
		$request = $this->newRequest( $input + [ 'h-captcha-response' => $token ], true );
		$this->setRequest( $request );
		$user = $anonymous
			? $this->getServiceContainer()->getUserFactory()->newAnonymous( '1.2.3.4' )
			: $this->getTestUser()->getUser();
		[ $html, $response ] = $this->executeSpecialPage( '', $request, null, $user );
		$actual = WikibaseRepo::getEntityRevisionLookup()->getEntityRevision( $this->item->getId() )->getEntity();

		if ( !$success || $rejectEdit ) {
			$this->assertNull( $response->getHeader( 'Location' ) );
			$this->assertStringContainsString( $rejectEdit ? 'test-rejected-edit' : 'captcha-edit-fail', $html );
			$this->assertSelectorMatchesOneElement( $html, 'form #h-captcha' );
			$this->assertEquals( $this->item, $actual );
			return;
		}

		$this->assertNotNull( $response->getHeader( 'Location' ), $html );
		$this->assertStringContainsString( $this->item->getId()->getSerialization(), $response->getHeader( 'Location' ) );
		$expected = $this->item->copy();
		switch ( $page ) {
			case 'SetLabel':
				$expected->setLabel( 'en', 'New value' );
				break;
			case 'SetDescription':
				$expected->setDescription( 'en', 'New value' );
				break;
			case 'SetAliases':
				$expected->setAliases( 'en', [ 'New value' ] );
				break;
			case 'SetLabelDescriptionAliases':
				$expected->setLabel( 'en', 'New value' );
				$expected->setDescription( 'en', 'New description' );
				$expected->setAliases( 'en', [ 'New alias' ] );
				break;
			case 'SetSiteLink':
				$expected->getSiteLinkList()->addNewSiteLink( 'enwiki', 'New page' );
				break;
		}
		$this->assertEquals( $expected, $actual );
	}

	public function testForceShowCaptchaOverridesSkipcaptcha(): void {
		$this->pageName = 'SetLabel';
		$this->setGroupPermissions( [ '*' => [ 'skipcaptcha' => true ] ] );
		$this->overrideConfigValue( 'CaptchaTriggers', [
			'edit' => [
				'trigger' => false,
				'class' => 'HCaptcha',
				'config' => [ 'HCaptchaSiteKey' => 'edit-site-key' ],
			],
			'create' => false,
			'addurl' => false,
		] );
		$this->setTemporaryHook( 'EditFilterMergedContent', function ( $context, $content, $status ) {
			$this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )
				->getGlobalInstanceFromContext( $context )->setForceShowCaptcha( true );
			$status->fatal( 'test-rejected-edit' );
		}, false );
		$request = $this->newRequest( [ 'value' => 'New value' ], true );
		$this->setRequest( $request );
		[ $html, $response ] = $this->executeSpecialPage( '', $request, 'en', $this->getTestUser()->getUser() );

		$this->assertNull( $response->getHeader( 'Location' ) );
		$this->assertStringContainsString( 'test-rejected-edit', $html );
		$this->assertSelectorMatchesOneElement( $html, 'form #h-captcha' );
		$this->assertSelectorMatchesOneElement( $html, 'form button[type="submit"]' );
	}

	public function testAnonymousSimpleCaptchaRoundTrip(): void {
		$this->pageName = 'SetLabel';
		$this->disableAutoCreateTempUser();
		$this->overrideConfigValue( 'CaptchaStorageClass', CaptchaSessionStore::class );
		CaptchaStore::unsetInstanceForTests();
		$this->overrideConfigValue( 'CaptchaTriggers', [
			'edit' => [ 'trigger' => true, 'class' => SimpleCaptcha::class ],
			'create' => false,
			'addurl' => false,
		] );
		$user = $this->getServiceContainer()->getUserFactory()->newAnonymous( '1.2.3.4' );
		$getRequest = $this->newRequest();
		$this->setRequest( $getRequest );
		[ $html ] = $this->executeSpecialPage( '', $getRequest, 'en', $user );
		$this->assertTrue( $getRequest->getSession()->isPersistent() );
		$document = DOMUtils::parseHTML( $html );
		$captchaId = $this->assertSelectorMatchesOneElementInNode( $document, '#wpCaptchaId' )
			->getAttribute( 'value' );
		$editToken = $this->assertSelectorMatchesOneElementInNode( $document, '#wpEditToken' )
			->getAttribute( 'value' );
		$this->assertNotEmpty( $captchaId );
		$this->assertNotEmpty( $editToken );
		$this->assertSelectorMatchesOneElementInNode( $document, '#wpCaptchaWord' );
		$captcha = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' )
			->getGlobalInstance( CaptchaTriggers::EDIT );
		$captchaInfo = $captcha->retrieveCaptcha( $captchaId );
		$this->assertIsArray( $captchaInfo );

		$postRequest = $this->newRequest( [
			'value' => 'New value',
			'wpCaptchaId' => $captchaId,
			'wpCaptchaWord' => $captchaInfo['answer'],
			'wpEditToken' => $editToken,
		], true, $getRequest->getSession() );
		$this->setRequest( $postRequest );
		[ , $response ] = $this->executeSpecialPage( '', $postRequest, 'en', $user );

		$this->assertNotNull( $response->getHeader( 'Location' ) );
		$expected = $this->item->copy();
		$expected->setLabel( 'en', 'New value' );
		$actual = WikibaseRepo::getEntityRevisionLookup()->getEntityRevision( $this->item->getId() )->getEntity();
		$this->assertEquals( $expected, $actual );
	}

	public function testFeatureFlagDisabledKeepsDefaultCaptchaConfiguration(): void {
		$this->setHCaptchaIntegration( false );
		$context = new RequestContext();
		$context->setTitle( Title::newFromText( 'Special:SetLabel' ) );
		$factory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$this->assertSame( $factory->getGlobalInstance( CaptchaTriggers::CREATE ),
			$factory->getGlobalInstanceFromContext( $context ) );
	}

	public function testUnrelatedSpecialPageKeepsItsCaptchaConfiguration(): void {
		$context = new RequestContext();
		$context->setTitle( Title::newFromText( 'Special:NewItem' ) );
		$factory = $this->getServiceContainer()->get( 'ConfirmEditCaptchaFactory' );
		$this->assertSame( $factory->getGlobalInstance( CaptchaTriggers::CREATE ),
			$factory->getGlobalInstanceFromContext( $context ) );
	}

	private function setHCaptchaIntegration( bool $enabled ): void {
		$settings = clone WikibaseRepo::getSettings( $this->getServiceContainer() );
		$settings->setSetting( 'tmpEnableHCaptchaIntegration', $enabled );
		$this->setService( 'WikibaseRepo.Settings', $settings );
	}

	private function newRequest(
		array $input = [], bool $posted = false, ?Session $session = null
	): FauxRequest {
		return new FauxRequest( $input + [
			'id' => $this->item->getId()->getSerialization(),
			'language' => 'en',
			'site' => 'enwiki',
		], $posted, $session );
	}

}
