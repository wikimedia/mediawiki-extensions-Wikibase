<?php

namespace Wikibase\Repo\Tests\Specials;

use MediaWiki\Context\RequestContext;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Request\FauxResponse;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Tests\Specials\SpecialPageExecutor;
use MediaWiki\Tests\Specials\SpecialPageTestBase;
use MockHttpTrait;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Specials\SpecialNewItem;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\SpecialNewEntity
 *
 * @group Wikibase
 * @group SpecialPage
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class SpecialNewEntityCaptchaTest extends SpecialPageTestBase {

	use CaptchaTestHelperProxyTrait;
	use MockHttpTrait;

	private const ITEM_NAMESPACE = 123;

	private const PRIVACY_POLICY_NOTICE =
		'<div class="h-captcha-privacy-policy">(hcaptcha-privacy-policy)</div>';

	protected function setUp(): void {
		parent::setUp();
		$this->setUserLang( 'qqx' );

		$this->clearCaptchaFactoryGlobalInstances();
	}

	protected function tearDown(): void {
		$this->clearCaptchaFactoryGlobalInstances();

		parent::tearDown();
	}

	protected function newSpecialPage() {
		$validatorFactory = $this->createMock( TermValidatorFactory::class );
		$valueValidator = $this->createMock( ValueValidator::class );
		$valueValidator->method( 'validate' )->willReturn( Result::newSuccess() );
		$validatorFactory->method( $this->anything() )->willReturn( $valueValidator );

		$captchaFactory = ExtensionRegistry::getInstance()->isLoaded( 'ConfirmEdit' )
			? $this->getServiceContainer()->getService( 'ConfirmEditCaptchaFactory' )
			: null;

		return new SpecialNewItem(
			[],
			$this->createMock( SpecialPageCopyrightView::class ),
			new EntityNamespaceLookup( [ Item::ENTITY_TYPE => self::ITEM_NAMESPACE ] ),
			WikibaseRepo::getSummaryFormatter(),
			WikibaseRepo::getEntityTitleLookup(),
			WikibaseRepo::getEditEntityFactory(),
			WikibaseRepo::getSiteLinkPageNormalizer(),
			WikibaseRepo::getAnonymousEditWarningBuilder(),
			$validatorFactory,
			WikibaseRepo::getItemTermsCollisionDetector(),
			WikibaseRepo::getValidatorErrorLocalizer(),
			new SiteLinkTargetProvider( new HashSiteStore(), [] ),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory(),
			[],
			false,
			$captchaFactory
		);
	}

	public function testCaptchaWidgetRendered_WhenHCaptchaTriggersForNamespace(): void {
		$this->configureHCaptcha( [ 'create' => true ] );

		[ $html ] = $this->executeSpecialPage();

		$this->assertStringContainsString( 'h-captcha', $html );
		$this->assertStringContainsString( 'data-sitekey', $html );
	}

	public function testPrivacyPolicyNoticeRendered_WhenHCaptchaTriggers(): void {
		$this->configureHCaptcha( [ 'create' => true ] );

		[ $html ] = $this->executeSpecialPage();

		$this->assertSame( 1, substr_count( $html, self::PRIVACY_POLICY_NOTICE ) );
	}

	public function testPrivacyPolicyNoticeRenderedOnce_InInvisibleMode(): void {
		$this->configureHCaptcha( [ 'create' => true ], [ 'HCaptchaInvisibleMode' => true ] );

		[ $html ] = $this->executeSpecialPage();

		$this->assertSame( 1, substr_count( $html, self::PRIVACY_POLICY_NOTICE ) );
	}

	public function testPrivacyPolicyNoticeNotRendered_WhenActiveCaptchaIsNotHCaptcha(): void {
		$this->configureHCaptcha( [ 'create' => true ], [ 'CaptchaClass' => 'SimpleCaptcha' ] );

		[ $html ] = $this->executeSpecialPage();

		$this->assertStringNotContainsString( '(hcaptcha-privacy-policy)', $html );
	}

	public function testCaptchaWidgetNotRendered_WhenUserCanSkipCaptcha(): void {
		$this->configureHCaptcha( [ 'create' => true ] );
		$this->setGroupPermissions( [ '*' => [ 'skipcaptcha' => true ] ] );

		[ $html ] = $this->executeSpecialPage();

		$this->assertStringNotContainsString( 'id="h-captcha"', $html );
		$this->assertStringNotContainsString( '(hcaptcha-privacy-policy)', $html );
	}

	public function testCaptchaWidgetNotRendered_WhenTriggerDisabledForNamespace(): void {
		$this->configureHCaptcha( [ 'create' => true ], [
			'CaptchaTriggersOnNamespace' => [
				self::ITEM_NAMESPACE => [ 'create' => false ],
			],
		] );

		[ $html ] = $this->executeSpecialPage();

		$this->assertStringNotContainsString( 'id="h-captcha"', $html );
	}

	public function testCaptchaWidgetRendered_WhenActiveCaptchaIsNotHCaptcha(): void {
		$this->configureHCaptcha( [ 'create' => true ], [ 'CaptchaClass' => 'SimpleCaptcha' ] );

		[ $html ] = $this->executeSpecialPage();

		// The generic API wraps any captcha's HTML in <div class="captcha">.
		$this->assertStringContainsString( 'class="captcha"', $html );
	}

	private function newSubmitRequest( array $extraData = [] ): FauxRequest {
		$request = new FauxRequest( $extraData + [
			SpecialNewItem::FIELD_LANG => 'en',
			SpecialNewItem::FIELD_LABEL => 'captcha test',
			SpecialNewItem::FIELD_DESCRIPTION => 'captcha test description',
			SpecialNewItem::FIELD_ALIASES => 'alias one|alias two',
			'wpEditToken' => RequestContext::getMain()->getUser()->getEditToken(),
		], true );
		// ConfirmEdit reads the token from $wgRequest
		$this->setRequest( $request );
		return $request;
	}

	private function mockHCaptchaSiteVerify( bool $success ): void {
		$this->installMockHttp( $this->makeFakeHttpRequest(
			json_encode( [ 'success' => $success ] + ( $success ? [] : [ 'error-codes' => [ 'invalid-input-response' ] ] ) )
		) );
	}

	private function assertCaptchaFailureShown( string $html ): void {
		$this->assertMatchesRegularExpression(
			'/cdx-message--error[^>]*><span class="cdx-message__icon"><\/span>' .
				'<div class="cdx-message__content">\(wikibase-newentity-hcaptcha-fail\)/',
			$html
		);
		$this->assertSame( 1, substr_count( $html, '(wikibase-newentity-hcaptcha-fail)' ) );
		$this->assertMatchesRegularExpression( '/<div id="h-captcha" class="[^"]*mw-confirmedit-captcha-fail/', $html );
	}

	public function testSubmitWithoutCaptcha_DoesNotSaveAndShowsErrorKeepingInput(): void {
		$this->configureHCaptcha( [ 'create' => true ] );

		/** @var FauxResponse $response */
		[ $html, $response ] = $this->executeSpecialPage( '', $this->newSubmitRequest() );

		$this->assertNull( $response->getHeader( 'location' ) );
		$this->assertCaptchaFailureShown( $html );
		$this->assertStringContainsString( "value='captcha test'", $html );
		$this->assertStringContainsString( "value='captcha test description'", $html );
		$this->assertStringContainsString( "value='alias one|alias two'", $html );
	}

	public function testSubmitWithInvalidCaptcha_DoesNotSave(): void {
		$this->configureHCaptcha( [ 'create' => true ] );
		$this->mockHCaptchaSiteVerify( false );

		/** @var FauxResponse $response */
		[ $html, $response ] = $this->executeSpecialPage(
			'',
			$this->newSubmitRequest( [ 'h-captcha-response' => 'invalid-token' ] )
		);

		$this->assertNull( $response->getHeader( 'location' ) );
		$this->assertCaptchaFailureShown( $html );
	}

	public static function provideFeatureFlag(): iterable {
		yield 'flag enabled' => [ true ];
		yield 'flag disabled' => [ false ];
	}

	/** @dataProvider provideFeatureFlag */
	public function testCaptchaWidgetRendered_OnlyWhenFeatureFlagEnabled( bool $flag ): void {
		// TODO: Remove this test once the feature flag is removed and hCaptcha is always enabled.
		$this->configureHCaptcha( [ 'create' => true ] );
		$settings = clone WikibaseRepo::getSettings();
		$settings->setSetting( 'tmpEnableHCaptchaIntegration', $flag );
		$this->setService( 'WikibaseRepo.Settings', $settings );

		foreach ( [ 'NewItem', 'NewProperty' ] as $pageName ) {
			$page = $this->getServiceContainer()->getSpecialPageFactory()->getPage( $pageName );
			[ $html ] = ( new SpecialPageExecutor() )->executeSpecialPage( $page, '', null, 'qqx' );

			$this->assertSame( $flag, str_contains( $html, 'id="h-captcha"' ), $pageName );
		}
	}

	public function testSubmitButtonNameIsNotSubmit(): void {
		[ $html ] = $this->executeSpecialPage();

		$this->assertMatchesRegularExpression(
			'~\bname=[\'"]wikibase-newentity-submit[\'"]~',
			$html,
			'Submit button must use the namespaced name'
		);
		$this->assertDoesNotMatchRegularExpression(
			'~\bname=[\'"]submit[\'"]~',
			$html,
			'name="submit" shadows HTMLFormElement.submit() and must not appear'
		);
	}

}
