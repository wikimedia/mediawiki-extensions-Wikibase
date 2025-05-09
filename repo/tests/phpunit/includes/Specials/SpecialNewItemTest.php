<?php

namespace Wikibase\Repo\Tests\Specials;

use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Request\WebResponse;
use MediaWiki\Session\Session;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Site\MediaWikiPageNameNormalizer;
use MediaWiki\Site\Site;
use MediaWiki\Site\SiteStore;
use MediaWiki\Tests\User\TempUser\TempUserTestTrait;
use MediaWiki\User\UserIdentity;
use PHPUnit\Framework\MockObject\MockObject;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Specials\SpecialNewItem;
use Wikibase\Repo\Validators\NotMulValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\SpecialNewItem
 * @covers \Wikibase\Repo\Specials\SpecialNewEntity
 * @covers \Wikibase\Repo\Specials\SpecialWikibaseRepoPage
 * @covers \Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Addshore
 */
class SpecialNewItemTest extends SpecialNewEntityTestCase {

	use TempUserTestTrait;

	private const BADGE_GOOD_ARTICLE = 'Q17437798';
	private const BADGE_SITELINK_TO_REDIRECT = 'Q70893996';

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	protected function setUp(): void {
		parent::setUp();
		$this->siteStore = new HashSiteStore();

		$settings = clone WikibaseRepo::getSettings();
		$settings->setSetting( 'enableMulLanguageCode', true );
		$settings->setSetting( 'badgeItems', [
			self::BADGE_GOOD_ARTICLE => 'good-article',
			self::BADGE_SITELINK_TO_REDIRECT => 'sitelink-to-redirect',
		] );
		$settings->setSetting( 'redirectBadgeItems', [
			self::BADGE_SITELINK_TO_REDIRECT,
		] );
		$this->setService( 'WikibaseRepo.Settings', $settings );
	}

	protected function newSpecialPage() {
		$namespaceNumber = 123;

		return new SpecialNewItem(
			self::TAGS,
			$this->copyrightView,
			new EntityNamespaceLookup( [ Item::ENTITY_TYPE => $namespaceNumber ] ),
			WikibaseRepo::getSummaryFormatter(),
			WikibaseRepo::getEntityTitleLookup(),
			WikibaseRepo::getEditEntityFactory(),
			WikibaseRepo::getSiteLinkPageNormalizer(),
			WikibaseRepo::getAnonymousEditWarningBuilder(),
			$this->getTermValidatorFactoryMock(),
			WikibaseRepo::getItemTermsCollisionDetector(),
			WikibaseRepo::getValidatorErrorLocalizer(),
			new SiteLinkTargetProvider( $this->siteStore ),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory(),
			WikibaseRepo::getSettings()->getSetting( 'badgeItems' ),
			[ 'wikiblah' ],
			false
		);
	}

	public function testAllNecessaryFormFieldsArePresent_WhenRendered() {
		[ $html ] = $this->executeSpecialPage();

		$this->assertHtmlContainsInputWithName( $html, SpecialNewItem::FIELD_LANG );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewItem::FIELD_LABEL );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewItem::FIELD_DESCRIPTION );
		$this->assertHtmlContainsInputWithName( $html, SpecialNewItem::FIELD_ALIASES );
		$this->assertHtmlContainsSubmitControl( $html );
	}

	public function testSiteAndPageInputFieldsWithPredefinedValuesPresent_WhenRenderedWithGetParametersPassed() {
		$getParameters = [
			SpecialNewItem::FIELD_SITE => 'some-site',
			SpecialNewItem::FIELD_PAGE => 'some-page',
		];

		[ $html ] = $this->executeSpecialPage( '', new FauxRequest( $getParameters ) );

		$this->assertHtmlContainsInputWithNameAndValue(
			$html,
			SpecialNewItem::FIELD_SITE,
			'some-site'
		);
		$this->assertHtmlContainsInputWithNameAndValue(
			$html,
			SpecialNewItem::FIELD_PAGE,
			'some-page'
		);
	}

	public function testLabelAndDescriptionValuesAreSetAccordingToSubpagePath_WhenRendered() {
		$subPagePart1 = 'LabelText';
		$subPagePart2 = 'DescriptionText';
		$subPage = "{$subPagePart1}/{$subPagePart2}";

		[ $html ] = $this->executeSpecialPage( $subPage );

		$this->assertHtmlContainsInputWithNameAndValue( $html, SpecialNewItem::FIELD_LABEL, $subPagePart1 );
		$this->assertHtmlContainsInputWithNameAndValue( $html, SpecialNewItem::FIELD_DESCRIPTION, $subPagePart2 );
	}

	public static function provideValidEntityCreationRequests() {
		return [
			'only label is set' => [
				[
					SpecialNewItem::FIELD_LANG => 'en',
					SpecialNewItem::FIELD_LABEL => 'label',
					SpecialNewItem::FIELD_DESCRIPTION => '',
					SpecialNewItem::FIELD_ALIASES => '',
				],
			],
			'another language' => [
				[
					SpecialNewItem::FIELD_LANG => 'fr',
					SpecialNewItem::FIELD_LABEL => 'label',
					SpecialNewItem::FIELD_DESCRIPTION => '',
					SpecialNewItem::FIELD_ALIASES => '',
				],
			],
			'only description is set' => [
				[
					SpecialNewItem::FIELD_LANG => 'en',
					SpecialNewItem::FIELD_LABEL => '',
					SpecialNewItem::FIELD_DESCRIPTION => 'desc',
					SpecialNewItem::FIELD_ALIASES => '',
				],
			],
			'single alias' => [
				[
					SpecialNewItem::FIELD_LANG => 'en',
					SpecialNewItem::FIELD_LABEL => '',
					SpecialNewItem::FIELD_DESCRIPTION => '',
					SpecialNewItem::FIELD_ALIASES => 'alias',
				],
			],
			'multiple aliases' => [
				[
					SpecialNewItem::FIELD_LANG => 'en',
					SpecialNewItem::FIELD_LABEL => '',
					SpecialNewItem::FIELD_DESCRIPTION => '',
					SpecialNewItem::FIELD_ALIASES => 'alias1|alias2|alias3',
				],
			],
			'nontrimmed label' => [
				[
					SpecialNewItem::FIELD_LANG => 'en',
					SpecialNewItem::FIELD_LABEL => '  some text with spaces on the sides    ',
					SpecialNewItem::FIELD_DESCRIPTION => '',
					SpecialNewItem::FIELD_ALIASES => '',
				],
			],
			'nontrimmed description' => [
				[
					SpecialNewItem::FIELD_LANG => 'en',
					SpecialNewItem::FIELD_LABEL => '',
					SpecialNewItem::FIELD_DESCRIPTION => ' description with spaces on the sides ',
					SpecialNewItem::FIELD_ALIASES => '',
				],
			],
			'all input is present' => [
				[
					SpecialNewItem::FIELD_LANG => 'en',
					SpecialNewItem::FIELD_LABEL => 'label',
					SpecialNewItem::FIELD_DESCRIPTION => 'desc',
					SpecialNewItem::FIELD_ALIASES => 'a1|a2',
				],
			],
		];
	}

	public static function provideInvalidEntityCreationRequests() {
		return [
			'unknown language' => [
				[
					SpecialNewItem::FIELD_LANG => 'some-weird-language',
					SpecialNewItem::FIELD_LABEL => 'label',
					SpecialNewItem::FIELD_DESCRIPTION => '',
					SpecialNewItem::FIELD_ALIASES => '',
				],
				'(wikibase-content-language-edit-not-recognized-language)',
			],
			'unknown site identifier' => [
				[
					SpecialNewItem::FIELD_LANG => 'en',
					SpecialNewItem::FIELD_LABEL => 'label',
					SpecialNewItem::FIELD_DESCRIPTION => '',
					SpecialNewItem::FIELD_ALIASES => '',
					SpecialNewItem::FIELD_SITE => 'unknown',
					SpecialNewItem::FIELD_PAGE => 'some page',
				],
				'(wikibase-newitem-not-recognized-siteid)',
			],
			'all fields are empty' => [
				[
					SpecialNewItem::FIELD_LANG => 'en',
					SpecialNewItem::FIELD_LABEL => '',
					SpecialNewItem::FIELD_DESCRIPTION => '',
					SpecialNewItem::FIELD_ALIASES => '',
				],
				'(wikibase-newitem-insufficient-data)',
			],
			'empty label and description, aliases contain only spaces and pipe symbols' => [
				[
					SpecialNewItem::FIELD_LANG => 'en',
					SpecialNewItem::FIELD_LABEL => '',
					SpecialNewItem::FIELD_DESCRIPTION => '',
					SpecialNewItem::FIELD_ALIASES => ' | || | ',
				],
				'(wikibase-newitem-insufficient-data)',
			],
			'label and description are identical' => [
				[
					SpecialNewItem::FIELD_LANG => 'en',
					SpecialNewItem::FIELD_LABEL => 'something',
					SpecialNewItem::FIELD_DESCRIPTION => 'something',
					SpecialNewItem::FIELD_ALIASES => '',
				],
				'(wikibase-newitem-same-label-and-description)',
			],
			'mul descriptions' => [
				[
					SpecialNewItem::FIELD_LANG => 'mul',
					SpecialNewItem::FIELD_LABEL => 'blah',
					SpecialNewItem::FIELD_DESCRIPTION => 'a mul description',
					SpecialNewItem::FIELD_ALIASES => '',
				],
				'(wikibase-validator-no-mul-descriptions: mul-language-name)',
			],
		];
	}

	public function testErrorBeingDisplayed_WhenItemWithTheSameLabelAndDescriptionInThisLanguageAlreadyExists() {
		$formData = [
			SpecialNewItem::FIELD_LANG => 'en',
			SpecialNewItem::FIELD_LABEL => 'label1',
			SpecialNewItem::FIELD_DESCRIPTION => 'description1',
			SpecialNewItem::FIELD_ALIASES => '',
		];
		$this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		[ $html ] = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		$this->assertHtmlContainsErrorMessage( $html, '(wikibase-validator-label-with-description-conflict: label1, en, ' );
	}

	public function testErrorAboutNonExistentPageIsDisplayed_WhenSiteExistsButPageDoesNot() {
		$existingSiteId = 'existing-site';
		$formData = [
			SpecialNewItem::FIELD_LANG => 'en',
			SpecialNewItem::FIELD_LABEL => 'some label',
			SpecialNewItem::FIELD_DESCRIPTION => 'some description',
			SpecialNewItem::FIELD_ALIASES => '',
			SpecialNewItem::FIELD_SITE => $existingSiteId,
			SpecialNewItem::FIELD_PAGE => 'nonexistent-page',
		];
		$this->givenSiteWithNoPagesExists( $existingSiteId );

		[ $html ] = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		$this->assertHtmlContainsErrorMessage( $html, '(wikibase-newitem-no-external-page: existing-site, nonexistent-page)' );
	}

	public function testErrorAboutNonExistentSiteIsDisplayed_WhenSiteExistsButHasWrongSiteGroup() {
		$existingSiteId = 'existing-site';
		$formData = [
			SpecialNewItem::FIELD_LANG => 'en',
			SpecialNewItem::FIELD_LABEL => 'some label',
			SpecialNewItem::FIELD_DESCRIPTION => 'some description',
			SpecialNewItem::FIELD_ALIASES => '',
			SpecialNewItem::FIELD_SITE => $existingSiteId,
			SpecialNewItem::FIELD_PAGE => 'nonexistent-page',
		];
		$this->givenSiteWithWrongGroup( $existingSiteId );

		[ $html ] = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		$this->assertHtmlContainsErrorMessage( $html, '(wikibase-newitem-not-recognized-siteid)' );
	}

	public function testWhenLabelIsInvalid_ThenHtmlContainsErrorMessage() {
		$formData = [
				SpecialNewItem::FIELD_LABEL => 'TOO_LONG_ERROR',
		];

		$this->assertHtmlContainsErrorTooLongMessage( $formData );
	}

	public function testWhenDescriptionIsInvalid_ThenHtmlContainsErrorMessage() {
		$formData = [
				SpecialNewItem::FIELD_DESCRIPTION => 'TOO_LONG_ERROR',
		];

		$this->assertHtmlContainsErrorTooLongMessage( $formData );
	}

	public function testWhenAliasIsInvalid_ThenHtmlContainsErrorMessage() {
		$formData = [
				SpecialNewItem::FIELD_ALIASES => 'TOO_LONG_ERROR',
		];

		$this->assertHtmlContainsErrorTooLongMessage( $formData );
	}

	public function testWhenAliasesAreInvalid_ThenHtmlContainsErrorMessage() {
		$formData = [
			SpecialNewItem::FIELD_ALIASES => 'TOO_LONG_ERROR|TOO_LONG_ERROR',
		];

		$this->assertHtmlContainsErrorTooLongMessage( $formData );
	}

	private function assertHtmlContainsErrorTooLongMessage( $formData ) {
		[ $html ] = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		$this->assertHtmlContainsErrorMessage( $html, '(htmlform-invalid-input)' );
	}

	public function testIdGeneratorRateLimit() {
		$this->mergeMwGlobalArrayValue( 'wgRateLimits', [ 'wikibase-idgenerator' => [
			'anon' => [ 1, 60 ],
			'user' => [ 1, 60 ],
		] ] );

		$formData = [
			SpecialNewItem::FIELD_LANG => 'en',
			SpecialNewItem::FIELD_LABEL => 'rate limit test item',
		];

		/** @var WebResponse $response */
		[ , $response ] = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );
		$firstItemId = $this->extractEntityIdFromUrl( $response->getHeader( 'location' ) );
		$firstId = $firstItemId->getNumericId();

		[ $html, $response ] = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );
		$this->assertNull( $response->getHeader( 'location' ) );
		$this->assertStringContainsString( '(actionthrottledtext)', $html );

		$this->mergeMwGlobalArrayValue( 'wgRateLimits', [ 'wikibase-idgenerator' => [
			'anon' => [ 60, 60 ],
			'user' => [ 60, 60 ],
		] ] );

		[ , $response ] = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );
		$secondItemId = $this->extractEntityIdFromUrl( $response->getHeader( 'location' ) );
		$secondId = $secondItemId->getNumericId();

		$this->assertSame( $firstId + 1, $secondId,
			'Failed request should not have consumed item ID' );
	}

	public function testCreateItemWithSitelinkWithBadges(): void {
		$existingSiteId = 'existing-site';
		$formData = [
			SpecialNewItem::FIELD_LANG => 'en',
			SpecialNewItem::FIELD_LABEL => 'some label',
			SpecialNewItem::FIELD_DESCRIPTION => '',
			SpecialNewItem::FIELD_ALIASES => '',
			SpecialNewItem::FIELD_SITE => $existingSiteId,
			SpecialNewItem::FIELD_PAGE => 'Some page',
			SpecialNewItem::FIELD_BADGES => [ self::BADGE_SITELINK_TO_REDIRECT ],
		];
		$this->givenSiteNormalizingDependingOnRedirectFlag( $existingSiteId );

		[ , $webResponse ] = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		$entityId = $this->extractEntityIdFromUrl( $webResponse->getHeader( 'location' ) );
		/** @var Item $item */
		$item = WikibaseRepo::getEntityLookup()->getEntity( $entityId );
		$sitelink = $item->getSiteLink( $existingSiteId );
		$this->assertSame( 'Some page', $sitelink->getPageName() );
		$this->assertCount( 1, $sitelink->getBadges() );
		$this->assertSame( self::BADGE_SITELINK_TO_REDIRECT, $sitelink->getBadges()[0]->getSerialization() );
	}

	public function testTempUserCreatedRedirect(): void {
		$this->enableAutoCreateTempUser();
		$formData = [
			SpecialNewItem::FIELD_LANG => 'en',
			SpecialNewItem::FIELD_LABEL => __METHOD__,
			SpecialNewItem::FIELD_DESCRIPTION => '',
			SpecialNewItem::FIELD_ALIASES => '',
		];
		$this->setTemporaryHook( 'TempUserCreatedRedirect', function (
			Session $session,
			UserIdentity $user,
			string $returnTo,
			string $returnToQuery,
			string $returnToAnchor,
			&$redirectUrl
		): void {
			$userNameUtils = $this->getServiceContainer()->getUserNameUtils();
			$this->assertTrue( $userNameUtils->isTemp( $user ) );
			$redirectUrl = 'http://centralwiki.test?returnto=' . $returnTo;
		} );

		[ , $webResponse ] = $this->executeSpecialPage( '', new FauxRequest( $formData, true ) );

		$redirectUrl = $webResponse->getHeader( 'location' );
		$this->assertStringStartsWith( 'http://centralwiki.test?returnto=', $redirectUrl );
		$entityId = $this->extractEntityIdFromUrl( $redirectUrl );
		/** @var Item $item */
		$item = WikibaseRepo::getEntityLookup()->getEntity( $entityId );
		$this->assertSame( __METHOD__, $item->getLabels()->getByLanguage( 'en' )->getText() );
	}

	/**
	 * @param string $url
	 *
	 * @return ItemId
	 */
	protected function extractEntityIdFromUrl( $url ) {
		preg_match( '/\bQ\d+$/i', $url, $matches );
		return new ItemId( $matches[0] );
	}

	protected function assertEntityMatchesFormData( array $form, EntityDocument $entity ) {
		$this->assertInstanceOf( Item::class, $entity );
		/** @var Item $entity */

		$language = $form[SpecialNewItem::FIELD_LANG];
		if ( $form[SpecialNewItem::FIELD_LABEL] !== '' ) {
			$this->assertSame(
				trim( $form[SpecialNewItem::FIELD_LABEL] ),
				$entity->getLabels()->getByLanguage( $language )->getText()
			);
		}
		if ( $form[SpecialNewItem::FIELD_DESCRIPTION] !== '' ) {
			$this->assertSame(
				trim( $form[SpecialNewItem::FIELD_DESCRIPTION] ),
				$entity->getDescriptions()->getByLanguage( $language )->getText()
			);
		}
		if ( $form[SpecialNewItem::FIELD_ALIASES] !== '' ) {
			$this->assertArrayEquals(
				explode( '|', $form[SpecialNewItem::FIELD_ALIASES] ),
				$entity->getAliasGroups()->getByLanguage( $language )->getAliases()
			);
		}
	}

	/**
	 * @param string $existingSiteId
	 */
	private function givenSiteWithNoPagesExists( $existingSiteId ) {
		/** @var MockObject|Site $siteMock */
		$siteMock = $this->getMockBuilder( Site::class )
			->onlyMethods( [ 'normalizePageName' ] )
			->getMock();
		$siteMock->setGlobalId( $existingSiteId );
		$siteMock->setGroup( 'wikiblah' );
		$siteMock->method( 'normalizePageName' )->willReturn( false );

		$this->siteStore->saveSite( $siteMock );
	}

	/**
	 * @param string $existingSiteId
	 */
	private function givenSiteWithWrongGroup( $existingSiteId ) {
		/** @var MockObject|Site $siteMock */
		$siteMock = $this->getMockBuilder( Site::class )
			->onlyMethods( [ 'normalizePageName' ] )
			->getMock();
		$siteMock->setGlobalId( $existingSiteId );
		$siteMock->setGroup( 'different-site-group' );
		$siteMock->expects( $this->never() )->
			method( 'normalizePageName' );

		$this->siteStore->saveSite( $siteMock );
	}

	private function givenSiteNormalizingDependingOnRedirectFlag( $existingSiteId ) {
		$siteMock = $this->getMockBuilder( Site::class )
			->onlyMethods( [ 'normalizePageName' ] )
			->getMock();
		$siteMock->setGlobalId( $existingSiteId );
		$siteMock->setGroup( 'wikiblah' );
		$siteMock->method( 'normalizePageName' )
			->willReturnCallback( function ( $pageName, $followRedirect ) {
				if ( $followRedirect === MediaWikiPageNameNormalizer::FOLLOW_REDIRECT ) {
					return "Redirect target of $pageName";
				} else {
					return $pageName;
				}
			} );

		$this->siteStore->saveSite( $siteMock );
	}

	private function getTermValidatorFactoryMock() {
		$validatorMock = $this->getValidatorMock();

		$languageNameUtilsMock = $this->createMock( LanguageNameUtils::class );
		$languageNameUtilsMock->method( 'getLanguageName' )
			->with( 'mul', 'qqx' )
			->willReturn( 'mul-language-name' );

		/** @var MockObject|TermValidatorFactory $mock */
		$mock = $this->createMock( TermValidatorFactory::class );
		$mock->method( 'getDescriptionLanguageValidator' )
			->willReturn( new NotMulValidator(
				$languageNameUtilsMock
			) );
		$mock->method( $this->anything() )
			->willReturn( $validatorMock );

		return $mock;
	}

	private function getValidatorMock() {
		/** @var MockObject|ValueValidator $validatorMock */
		$validatorMock = $this->createMock( ValueValidator::class );
		$validatorMock->method( 'validate' )->willReturnCallback(
			function ( $value ) {
				if ( $value === 'TOO_LONG_ERROR' ) {
					return Result::newError( [ Error::newError( 'This is the too long error', null, 'too-long' ) ] );
				}
				return Result::newSuccess();
			}
		);

		return $validatorMock;
	}

}
