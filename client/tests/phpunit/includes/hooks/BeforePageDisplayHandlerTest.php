<?php

namespace Wikibase\Test;

use FauxRequest;
use OutputPage;
use RequestContext;
use Skin;
use SkinCologneBlue;
use SkinVector;
use Title;
use Wikibase\NamespaceChecker;
use Wikibase\SettingsArray;
use Wikibase\Client\Hooks\BeforePageDisplayHandler;

/**
 * @covers Wikibase\Client\Hooks\BeforePageDisplayHandler
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BeforePageDisplayHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider wikibaseForNamespaceProvider
	 */
	public function testHandle_WikibaseForNamespace( $jsModules, $cssModules,
		$handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$result = $this->getHandlerResult( $handler, $langLinks, $prefixedId,
			$loggedIn, $titleExists, $actionName );

		$this->assertEquals( $jsModules, $result->getModules(), 'js modules' );
		$this->assertEquals( $cssModules, $result->getModuleStyles(), 'css modules' );
	}

	public function wikibaseForNamespaceProvider() {
		return array(
			array(
				array(),
				array(),
				$this->getHookHandler( true, false ),
				array( 'de:Rom' ), // local site link
				null, // no wikibase
				true, // user logged in
				true, // title exists
				'view' // action
			),
			array(
				array(),
				array( 'wikibase.client.init' ),
				$this->getHookHandler( true, true ),
				array( 'de:Rom' ),
				'Q4', // has wikibase
				true, // user logged in
				true, // title exists
				'view' //action
			)
		);
	}

	/**
	 * @dataProvider pageConnectedToWikibaseVectorProvider
	 */
	public function testHandlePageConnectedToWikibaseVector( $jsModules, $cssModules,
		$handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$result = $this->getHandlerResult( $handler, $langLinks, $prefixedId,
			$loggedIn, $titleExists, $actionName );

		$this->assertEquals( $jsModules, $result->getModules(), 'js modules' );
		$this->assertEquals( $cssModules, $result->getModuleStyles(), 'css modules' );
	}

	public function pageConnectedToWikibaseVectorProvider() {
		return array(
			array(
				array(),
				array( 'wikibase.client.init' ),
				$this->getHookHandler( true, true ),
				array( 'de:Rom' ),
				'Q4',
				true, // user logged in
				true, // title exists
				'view' // action
			)
		);
	}

	/**
	 * @dataProvider pageNotConnectedToWikibaseProvider
	 */
	public function testHandlePageNotConnectedToWikibase( $jsModules, $cssModules,
		$handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$result = $this->getHandlerResult( $handler, $langLinks, $prefixedId,
			$loggedIn, $titleExists, $actionName );

		$this->assertEquals( $jsModules, $result->getModules(), 'js modules' );
		$this->assertEquals( $cssModules, $result->getModuleStyles(), 'css modules' );
	}

	public function pageNotConnectedToWikibaseProvider() {
		return array(
			array(
				array( 'wikibase.client.linkitem.init' ),
				array( 'wikibase.client.init', 'wikibase.client.nolanglinks' ),
				$this->getHookHandler( true, true ),
				array(), // no lang links
				null, // no prefixed id
				true, // user logged in
				true, // title exists
				'view' // action
			)
		);
	}

	/**
	 * @dataProvider widgetDisabledProvider
	 */
	public function testHandleWidgetDisabled( $jsModules, $cssModules, $handler, $langLinks,
		$prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$result = $this->getHandlerResult( $handler, $langLinks, $prefixedId,
			$loggedIn, $titleExists, $actionName );

		$this->assertEquals( $jsModules, $result->getModules(), 'js modules' );
		$this->assertEquals( $cssModules, $result->getModuleStyles(), 'css modules' );
	}

	public function widgetDisabledProvider() {
		return array(
			array(
				array(),
				array( 'wikibase.client.init', 'wikibase.client.nolanglinks' ),
				$this->getHookHandler( false, true ),
				array(), // no lang links
				null, // no prefixed id
				true, // user logged in
				true, // title exists
				'view' // action
			)
		);
	}

	/**
	 * @dataProvider handleUserLoggedOutNoLangLinksProvider
	 */
	public function testHandleUserLoggedOutNoLangLinks( $jsModules, $cssModules,
		$handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$result = $this->getHandlerResult( $handler, $langLinks, $prefixedId,
			$loggedIn, $titleExists, $actionName );

		$this->assertEquals( $jsModules, $result->getModules(), 'js modules' );
		$this->assertEquals( $cssModules, $result->getModuleStyles(), 'css modules' );
	}

	public function handleUserLoggedOutNoLangLinksProvider() {
		return array(
			array(
				array(),
				array( 'wikibase.client.init', 'wikibase.client.nolanglinks' ),
				$this->getHookHandler( true, true ),
				array(), // no lang links
				null, // no prefixed id
				false, // user logged out
				true, // title exists
				'view' // action
			)
		);
	}

	/**
	 * @dataProvider handleLoggedInUserNoLangLinksProvider
	 */
	public function testHandleLoggedInUserNoLangLinks( $jsModules, $cssModules,
		$handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$result = $this->getHandlerResult( $handler, $langLinks, $prefixedId,
			$loggedIn, $titleExists, $actionName );

		$this->assertEquals( $jsModules, $result->getModules(), 'js modules' );
		$this->assertEquals( $cssModules, $result->getModuleStyles(), 'css modules' );
	}

	public function handleLoggedInUserNoLangLinksProvider() {
		return array(
			array(
				array(),
				array( 'wikibase.client.nolanglinks' ),
				$this->getHookHandler( true, true ),
				array(), // no lang links
				null, // no prefixed id
				true, // user logged in
				false, // title exists
				'view' // action
			)
		);
	}

	/**
	 * @dataProvider handleHistoryActionNoLangLinksProvider
	 */
	public function testHandle_HistoryActionNoLangLinks( $jsModules, $cssModules,
		$handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$result = $this->getHandlerResult( $handler, $langLinks, $prefixedId,
			$loggedIn, $titleExists, $actionName );

		$this->assertEquals( $jsModules, $result->getModules(), 'js modules' );
		$this->assertEquals( $cssModules, $result->getModuleStyles(), 'css modules' );
	}

	public function handleHistoryActionNoLangLinksProvider() {
		return array(
			array(
				array(),
				array( 'wikibase.client.nolanglinks' ),
				$this->getHookHandler( true, true ),
				array(), // no lang links
				null, // no prefixed id
				true, // user logged in
				true, // title exists
				'history' // action
			)
		);
	}

	private function getContext( $loggedIn, $titleExists ) {
		$context = new RequestContext();

		$title = $this->getTitle( $titleExists );
		$context->setTitle( $title );

		$user = $this->getUser( $loggedIn );
		$context->setUser( $user );

		return $context;
	}

	private function getTitle( $titleExists ) {
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( $titleExists ) );

		return $title;
	}

	private function getUser( $loggedIn ) {
		$user = $this->getMockBuilder( 'User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->any() )
			->method( 'isLoggedIn' )
			->will( $this->returnValue( $loggedIn ) );

		return $user;
	}

	private function getNamespaceChecker( $wikibaseEnabled ) {
		$namespaceChecker = $this->getMockBuilder( 'Wikibase\NamespaceChecker' )
			->disableOriginalConstructor()
			->getMock();

		$namespaceChecker->expects( $this->any() )
			->method( 'isWikibaseEnabled' )
			->will( $this->returnValue( $wikibaseEnabled ) );

		return $namespaceChecker;
	}

	private function getSettings( $widgetEnabled ) {
		$settings = new SettingsArray();
		$settings->setSetting( 'enableSiteLinkWidget', $widgetEnabled );

		return $settings;
	}

	private function getOutputPage( Skin $skin, $langLinks, $prefixedId = null ) {
		$output = $skin->getOutput();
		$output->setLanguageLinks( $langLinks );

		if ( $prefixedId ) {
			$output->setProperty( 'wikibase_item', $prefixedId );
		}

		return $output;
	}

	private function getSkin( $loggedIn, $titleExists ) {
		$context = $this->getContext( $loggedIn, $titleExists );
		$skin = new SkinVector();
		$skin->setContext( $context );

		return $skin;
	}

	private function getHookHandler( $widgetEnabled, $wikibaseEnabled ) {
		$settings = $this->getSettings( $widgetEnabled );
		$namespaceChecker = $this->getNamespaceChecker( $wikibaseEnabled );

		return new BeforePageDisplayHandler( $settings, $namespaceChecker );
	}

	private function getHandlerResult( $handler, $langLinks, $prefixedId, $loggedIn,
		$titleExists, $actionName
	) {
		$skin = $this->getSkin( $loggedIn, $titleExists );
		$output = $this->getOutputPage( $skin, $langLinks, $prefixedId );

		return $handler->handleAddModules( $output, $skin, $actionName );
	}
}
