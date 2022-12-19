<?php

namespace Wikibase\Repo\Tests\Specials;

use ChangeTags;
use FauxRequest;
use FauxResponse;
use Hamcrest\Matcher;
use HamcrestPHPUnitIntegration;
use MediaWiki\MediaWikiServices;
use SpecialPageTestBase;
use TestSites;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\CopyrightMessageBuilder;
use Wikibase\Repo\SiteLinkPageNormalizer;
use Wikibase\Repo\SiteLinkTargetProvider;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\Specials\SpecialSetSiteLink;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\SpecialSetSiteLink
 * @covers \Wikibase\Repo\Specials\SpecialModifyEntity
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
 * @author Addshore
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class SpecialSetSiteLinkTest extends SpecialPageTestBase {
	use HamcrestPHPUnitIntegration;

	private const TAGS = [ 'mw-replace' ];

	/**
	 * @var array
	 */
	private static $matchers = [];

	/**
	 * @var string|null
	 */
	private static $itemId = null;

	/**
	 * @var string|null
	 */
	private static $badgeId = null;

	/**
	 * @var string|null
	 */
	private static $redirectId = null;

	/**
	 * @var string[]
	 */
	private static $oldBadgeItemsSetting;

	protected function newSpecialPage() {
		$siteLookup = $this->getServiceContainer()->getSiteLookup();
		$settings = WikibaseRepo::getSettings();

		$siteLinkChangeOpFactory = WikibaseRepo::getChangeOpFactoryProvider()->getSiteLinkChangeOpFactory();
		$siteLinkTargetProvider = new SiteLinkTargetProvider(
			$siteLookup,
			$settings->getSetting( 'specialSiteLinkGroups' )
		);

		$copyrightView = new SpecialPageCopyrightView( new CopyrightMessageBuilder(), '', '' );

		return new SpecialSetSiteLink(
			self::TAGS,
			$copyrightView,
			WikibaseRepo::getSummaryFormatter(),
			WikibaseRepo::getEntityTitleLookup(),
			WikibaseRepo::getEditEntityFactory(),
			WikibaseRepo::getSiteLinkPageNormalizer(),
			$siteLinkTargetProvider,
			[ 'wikipedia' ],
			$settings->getSetting( 'badgeItems' ),
			WikibaseRepo::getFallbackLabelDescriptionLookupFactory(),
			$siteLinkChangeOpFactory
		);
	}

	protected function setUp(): void {
		parent::setUp();

		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [ 'read' => true, 'edit' => true ] ] );

		if ( !self::$badgeId ) {
			self::$matchers = self::createMatchers();
			$sitesTable = MediaWikiServices::getInstance()->getSiteStore();
			$sitesTable->clear();
			$sitesTable->saveSites( TestSites::getSites() );

			$this->createItems();
			$this->addBadgeMatcher();
		}

		$settings = clone WikibaseRepo::getSettings();
		$settings->setSetting( 'badgeItems', [ self::$badgeId => '' ] );
		$this->setService( 'WikibaseRepo.Settings', $settings );
	}

	private function createItems() {
		$store = WikibaseRepo::getEntityStore();
		$user = $this->getTestUser()->getUser();

		$badge = new Item();
		$badge->setLabel( 'de', 'Guter Artikel' );
		$store->saveEntity( $badge, "testing", $user, EDIT_NEW );

		$item = new Item();
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'Wikidata', [ $badge->getId() ] );
		$store->saveEntity( $item, "testing", $user, EDIT_NEW );

		$redirect = new EntityRedirect( new ItemId( 'Q12345678' ), $item->getId() );
		$store->saveRedirect( $redirect, "testing", $user, EDIT_NEW );

		self::$badgeId = $badge->getId()->getSerialization();
		self::$itemId = $item->getId()->getSerialization();
		self::$redirectId = $redirect->getEntityId()->getSerialization();
	}

	private function addBadgeMatcher() {
		$value = self::$badgeId;
		self::$matchers['badgeinput'] = tagMatchingOutline( "<input type='checkbox' name='badges[]' value='$value'>" );

		self::$matchers['badgelabel'] = both(
			tagMatchingOutline( "<label>" )
		)->andAlso(
			havingTextContents( 'Guter Artikel' )
		);
	}

	public function testExecuteEmptyForm() {
		$matchers = self::$matchers;
		// Execute with no subpage value
		[ $output ] = $this->executeSpecialPage( '', null, 'de' );

		$this->assertHtmlContainsTagsMatching( $output, $matchers );
	}

	public function testExecuteOneValuePreset() {
		$matchers = self::$matchers;
		// Execute with one subpage value
		// Note: use language fallback de-ch => de
		[ $output ] = $this->executeSpecialPage( self::$itemId, null, 'de-ch' );

		$matchers['id'] = both( tagMatchingOutline( '<div id="wb-modifyentity-id"/>' ) )->andAlso(
			havingChild( both( tagMatchingOutline( '<input name="id"/>' ) )->andAlso(
				withAttribute( 'value' )->havingValue( self::$itemId )
			) )
		);

		$this->assertHtmlContainsTagsMatching( $output, $matchers );
	}

	public function testExecuteTwoValuesPreset() {
		$matchers = self::$matchers;
		// Execute with two subpage values
		// Note: use language fallback de-ch => de
		[ $output ] = $this->executeSpecialPage( self::$itemId . '/dewiki', null, 'de-ch' );

		$itemId = self::$itemId;
		$matchers['id'] = tagMatchingOutline(
			"<input type='hidden' name='id' value='$itemId'/>"
		);
		$matchers['site'] = tagMatchingOutline(
			"<input type='hidden' name='site' value='dewiki'/>"
		);
		$matchers['remove'] = tagMatchingOutline(
			"<input type='hidden' name='remove' value='remove'/>"
		);

		$matchers['page'] = both( tagMatchingOutline( '<div id="wb-setsitelink-page"/>' ) )->andAlso(
			havingChild( both( tagMatchingOutline( '<input name="page"/>' ) )->andAlso(
				withAttribute( 'value' )->havingValue( 'Wikidata' )
			) )
		);

		$this->assertHtmlContainsTagsMatching( $output, $matchers );
	}

	public function testExecuteTwoValuesPreset_no_label() {
		$matchers = self::$matchers;
		// Execute with two subpage values
		// Note: language fallback will fail, no label for en
		[ $output ] = $this->executeSpecialPage( self::$itemId . '/dewiki', null, 'en' );

		// already covered by testExecuteTwoValuesPreset()
		unset( $matchers['id'] );
		unset( $matchers['site'] );
		unset( $matchers['remove'] );

		$matchers['badgelabel'] = both(
			tagMatchingOutline( "<label>" )
		)->andAlso(
			havingTextContents( self::$badgeId )
		);

		$matchers['page'] = both( tagMatchingOutline( '<div id="wb-setsitelink-page"/>' ) )->andAlso(
			havingChild( both( tagMatchingOutline( '<input name="page"/>' ) )->andAlso(
				withAttribute( 'value' )->havingValue( 'Wikidata' )
			) )
		);

		$this->assertHtmlContainsTagsMatching( $output, $matchers );
	}

	public function testExecuteRedirect() {
		[ $output ] = $this->executeSpecialPage( self::$redirectId . '/dewiki', null, 'qqx' );

		$this->assertMatchesRegularExpression(
			'@<p class="error">\(wikibase-wikibaserepopage-unresolved-redirect: .*?\)</p>@',
			$output,
			'Expected error message'
		);
	}

	public function testExecutePostPreserveSiteLinkWhenNothingEntered() {
		$request = new FauxRequest( [
			'id' => self::$itemId,
			'site' => 'dewiki',
			'page' => '',
		], true );

		[ $output ] = $this->executeSpecialPage( '', $request );

		$this->assertThatHamcrest(
			$output,
			is( htmlPiece( havingChild( both( tagMatchingOutline( '<div id="wb-setsitelink-page"/>' ) )->andAlso(
				havingChild( tagMatchingOutline( '<input name="page" value="Wikidata"/>' ) )
			) ) ) ) );
	}

	public function testExecutePostModifySiteLink() {
		$lookup = WikibaseRepo::getEntityLookup();
		$request = new FauxRequest( [
			'id' => self::$itemId,
			'site' => 'dewiki',
			'page' => 'Wikipedia',
			'badges' => [ self::$badgeId ],
		], true );

		$pageNormalizer = $this->createMock( SiteLinkPageNormalizer::class );
		$pageNormalizer->expects( $this->once() )->method( 'normalize' )->with(
			$this->anything(),
			$this->equalTo( 'Wikipedia' ),
			$this->equalTo( [ self::$badgeId ] )
		)->willReturnArgument( 1 );
		$this->setService( 'WikibaseRepo.SiteLinkPageNormalizer', $pageNormalizer );

		[ , $response ] = $this->executeSpecialPage( '', $request );
		$redirect = $response instanceof FauxResponse ? $response->getHeader( 'Location' ) : null;

		$this->assertStringContainsString( self::$itemId, $redirect, "Should redirect to item page" );

		/** @var Item $item */
		$item = $lookup->getEntity( new ItemId( self::$itemId ) );

		$this->assertEquals(
			'Wikipedia',
			$item->getSiteLinkList()->getBySiteId( 'dewiki' )->getPageName(),
			"Should contain new site link"
		);

		$title = WikibaseRepo::getEntityTitleStoreLookup()->getTitleForId( $item->getId() );
		$tags = ChangeTags::getTags( $this->db, null, $title->getLatestRevID() );
		$this->assertArrayEquals( self::TAGS, $tags );
	}

	public function testExecutePostRemoveSiteLink() {
		$lookup = WikibaseRepo::getEntityLookup();
		$request = new FauxRequest( [
			'id' => self::$itemId,
			'site' => 'dewiki',
			'page' => '',
			'remove' => true,
		], true );

		[ , $response ] = $this->executeSpecialPage( '', $request );
		$redirect = $response instanceof FauxResponse ? $response->getHeader( 'Location' ) : null;

		$this->assertStringContainsString( self::$itemId, $redirect, "Should redirect to item page" );

		/** @var Item $item */
		$item = $lookup->getEntity( new ItemId( self::$itemId ) );

		$this->assertFalse( $item->hasLinkToSite( 'dewiki' ), "Should no longer contain site link" );
	}

	private static function createMatchers() {
		return [
			'id' => both( tagMatchingOutline( '<div id="wb-modifyentity-id"/>' ) )->andAlso(
				havingChild( tagMatchingOutline( '<input name="id"/>' ) )
			),
			'site' => both( tagMatchingOutline( '<div id="wb-setsitelink-site"/>' ) )->andAlso(
				havingChild( tagMatchingOutline( '<input name="site"/>' ) )
			),
			'page' => both( tagMatchingOutline( '<div id="wb-setsitelink-page"/>' ) )->andAlso(
				havingChild( tagMatchingOutline( '<input name="page"/>' ) )
			),
			'submit' => both( withAttribute( 'id' )->havingValue( 'wb-setsitelink-submit' ) )->andAlso(
				havingChild( tagMatchingOutline( '<button type="submit" name="wikibase-setsitelink-submit"/>' ) )
			),
		];
	}

	/**
	 * @param string $html
	 * @param Matcher[] $tagMatchers
	 */
	private function assertHtmlContainsTagsMatching( $html, array $tagMatchers ) {
		foreach ( $tagMatchers as $key => $matcher ) {
			$message = "Failed to match html output with tag '{$key}'";
			$this->assertThatHamcrest(
				$message,
				$html,
				is( htmlPiece( havingChild( $matcher ) ) )
			);

		}
	}

}
