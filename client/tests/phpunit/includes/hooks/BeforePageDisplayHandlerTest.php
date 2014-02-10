<?php

namespace Wikibase\Test;

use FauxRequest;
use OutputPage;
use RequestContext;
use Skin;
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
	public function testHandle_WikibaseForNamespace( $expectedJsModules, $expectedCssModules,
		$handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$skin = $this->getSkin( $loggedIn, $titleExists );
		$output = $this->getOutputPage( $skin, $langLinks, $prefixedId );

		$handler->addModules( $output, $skin, $actionName );

		$this->assertEquals( $expectedJsModules, $output->getModules(), 'js modules' );
		$this->assertEquals( $expectedCssModules, $output->getModuleStyles(), 'css modules' );
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
	 * @dataProvider pageConnectedToWikibaseProvider
	 */
	public function testHandlePageConnectedToWikibase( $expectedJsModules, $expectedCssModules,
		$handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$skin = $this->getSkin( $loggedIn, $titleExists );
		$output = $this->getOutputPage( $skin, $langLinks, $prefixedId );

		$handler->addModules( $output, $skin, $actionName );

		$this->assertEquals( $expectedJsModules, $output->getModules(), 'js modules' );
		$this->assertEquals( $expectedCssModules, $output->getModuleStyles(), 'css modules' );
	}

	public function pageConnectedToWikibaseProvider() {
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
	public function testHandlePageNotConnectedToWikibase( $expectedJsModules, $expectedCssModules,
		$handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$skin = $this->getSkin( $loggedIn, $titleExists );
		$output = $this->getOutputPage( $skin, $langLinks, $prefixedId );

		$handler->addModules( $output, $skin, $actionName );

		$this->assertEquals( $expectedJsModules, $output->getModules(), 'js modules' );
		$this->assertEquals( $expectedCssModules, $output->getModuleStyles(), 'css modules' );
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
	public function testHandleWidgetDisabled( $expectedJsModules, $expectedCssModules, $handler,
		$langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$skin = $this->getSkin( $loggedIn, $titleExists );
		$output = $this->getOutputPage( $skin, $langLinks, $prefixedId );

		$handler->addModules( $output, $skin, $actionName );

		$this->assertEquals( $expectedJsModules, $output->getModules(), 'js modules' );
		$this->assertEquals( $expectedCssModules, $output->getModuleStyles(), 'css modules' );
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
	 * @dataProvider handleUserLoggedOutWithEmptyLangLinksProvider
	 */
	public function testHandleUserLoggedOutWithEmptyLangLinks( $expectedJsModules,
		$expectedCssModules, $handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$skin = $this->getSkin( $loggedIn, $titleExists );
		$output = $this->getOutputPage( $skin, $langLinks, $prefixedId );

		$handler->addModules( $output, $skin, $actionName );

		$this->assertEquals( $expectedJsModules, $output->getModules(), 'js modules' );
		$this->assertEquals( $expectedCssModules, $output->getModuleStyles(), 'css modules' );
	}

	public function handleUserLoggedOutWithEmptyLangLinksProvider() {
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
	 * @dataProvider handleLoggedInUserWithEmptyLangLinksProvider
	 */
	public function testHandleLoggedInUserWithEmptyLangLinks( $expectedJsModules,
		$expectedCssModules, $handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$skin = $this->getSkin( $loggedIn, $titleExists );
		$output = $this->getOutputPage( $skin, $langLinks, $prefixedId );

		$handler->addModules( $output, $skin, $actionName );

		$this->assertEquals( $expectedJsModules, $output->getModules(), 'js modules' );
		$this->assertEquals( $expectedCssModules, $output->getModuleStyles(), 'css modules' );
	}

	public function handleLoggedInUserWithEmptyLangLinksProvider() {
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
	 * @dataProvider handleHistoryActionWithEmptyLangLinksProvider
	 */
	public function testHandle_HistoryActionWithEmptyLangLinks( $expectedJsModules,
		$expectedCssModules, $handler, $langLinks, $prefixedId, $loggedIn, $titleExists, $actionName
	) {
		$skin = $this->getSkin( $loggedIn, $titleExists );
		$output = $this->getOutputPage( $skin, $langLinks, $prefixedId );

		$handler->addModules( $output, $skin, $actionName );

		$this->assertEquals( $expectedJsModules, $output->getModules(), 'js modules' );
		$this->assertEquals( $expectedCssModules, $output->getModuleStyles(), 'css modules' );
	}

	public function handleHistoryActionWithEmptyLangLinksProvider() {
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
		$namespaceChecker = $this->getNamespaceChecker( $wikibaseEnabled );

		return new BeforePageDisplayHandler( $namespaceChecker, $widgetEnabled );
	}

}
