<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\Config\HashConfig;
use MediaWiki\Context\IContextSource;
use MediaWiki\Output\OutputPage;
use MediaWiki\Request\WebRequest;
use MediaWiki\Skin\Skin;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\Hooks\BeforePageDisplayHandler;
use Wikibase\Client\NamespaceChecker;

/**
 * @covers \Wikibase\Client\Hooks\BeforePageDisplayHandler
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BeforePageDisplayHandlerTest extends MediaWikiIntegrationTestCase {

	/**
	 * @dataProvider wikibaseForNamespaceProvider
	 * @dataProvider pageConnectedToWikibaseProvider
	 * @dataProvider pageNotConnectedToWikibaseProvider
	 * @dataProvider handleUserLoggedOutWithEmptyLangLinksProvider
	 */
	public function testHandle_WikibaseForNamespace( $expectedJsModules, $expectedCssModules,
		$enabledForNamespace, $langLinks, $prefixedId, $loggedIn, $tempUser
	) {
		$skin = $this->getSkin();
		$context = $this->getContext( $loggedIn, $tempUser, true );
		$output = $this->getOutputPage( $context, $langLinks, $prefixedId );

		$namespaceChecker = $this->getNamespaceChecker( $enabledForNamespace );

		$handler = new BeforePageDisplayHandler( false, $namespaceChecker, false );
		$handler->addModules( $output, 'view', $skin );

		$this->assertEquals( $expectedJsModules, $output->getModules(), 'js modules' );
		$this->assertEquals( $expectedCssModules, $output->getModuleStyles(), 'css modules' );
	}

	public static function wikibaseForNamespaceProvider() {
		return [
			[
				[],
				[],
				false, // wikibase not enabled for namespace
				[ 'de:Rom' ], // local site link
				null, // not connected item, no prefixed id
				true, // user logged in
				false, // user not temp user
			],
			[
				[],
				[ 'wikibase.client.init' ],
				true, // wikibase enabled for namespace
				[ 'de:Rom' ],
				'Q4', // has connected item
				true, // user logged in
				false, // user not temp user
			],
		];
	}

	public static function pageConnectedToWikibaseProvider() {
		return [
			[
				[],
				[ 'wikibase.client.init' ],
				true, // wikibase enabled for namespace
				[ 'de:Rom' ],
				'Q4', // has connected item
				true, // user logged in
				false, // user not temp user
			],
		];
	}

	public function testHandlePageConnectedToWikibase_noexternallinklinks() {
		$skin = $this->getSkin();
		$context = $this->getContext( true, false, true );

		// page connected, has links and noexternallanglinks
		$output = $this->getOutputPage( $context, [ 'de:Rom' ], 'Q4', [ '*' ] );
		$namespaceChecker = $this->getNamespaceChecker( true );

		$handler = new BeforePageDisplayHandler( false, $namespaceChecker, false );
		$handler->addModules( $output, 'view', $skin );

		$this->assertEquals( [], $output->getModules(), 'js modules' );
		$this->assertEquals( [], $output->getModuleStyles(), 'css modules' );
	}

	public static function pageNotConnectedToWikibaseProvider() {
		return [
			[
				[ 'wikibase.client.linkitem.init' ],
				[ 'wikibase.client.init' ],
				true, // wikibase enabled for namespace
				[], // no lang links
				null, // no prefixed id
				true, // user logged in
				false, // user not temp user
			],
			[
				[],
				[ 'wikibase.client.init' ],
				true, // wikibase enabled for namespace
				[], // no lang links
				null, // no prefixed id
				true, // user logged in
				true, // user is a temp user
			],
		];
	}

	public static function handleUserLoggedOutWithEmptyLangLinksProvider() {
		return [
			[
				[],
				[ 'wikibase.client.init' ],
				true, // wikibase enabled for namespace
				[], // no lang links
				null, // no prefixed id
				false, // user logged out
				false, // user not temp user
			],
		];
	}

	/**
	 * @dataProvider handleHistoryActionWithEmptyLangLinksProvider
	 */
	public function testHandleTitleNotExist_NoWikibaseLinks( $expectedJsModules,
		$expectedCssModules, $enabledForNamespace, $langLinks, $prefixedId, $loggedIn
	) {
		$skin = $this->getSkin();
		$context = $this->getContext( $loggedIn, false, false );
		$output = $this->getOutputPage( $context, $langLinks, $prefixedId );

		$namespaceChecker = $this->getNamespaceChecker( $enabledForNamespace );

		$handler = new BeforePageDisplayHandler( false, $namespaceChecker, false );
		$handler->addModules( $output, 'view', $skin );

		$this->assertEquals( $expectedJsModules, $output->getModules(), 'js modules' );
		$this->assertEquals( $expectedCssModules, $output->getModuleStyles(), 'css modules' );
	}

	/**
	 * @dataProvider handleHistoryActionWithEmptyLangLinksProvider
	 */
	public function testHandle_HistoryActionWithEmptyLangLinks( $expectedJsModules,
		$expectedCssModules, $enabledForNamespace, $langLinks, $prefixedId, $loggedIn, $tempUser
	) {
		$skin = $this->getSkin();
		$context = $this->getContext( $loggedIn, $tempUser, true );
		$output = $this->getOutputPage( $context, $langLinks, $prefixedId );

		$namespaceChecker = $this->getNamespaceChecker( $enabledForNamespace );

		$handler = new BeforePageDisplayHandler( false, $namespaceChecker, false );
		$handler->addModules( $output, 'history', $skin );

		$this->assertEquals( $expectedJsModules, $output->getModules(), 'js modules' );
		$this->assertEquals( $expectedCssModules, $output->getModuleStyles(), 'css modules' );
	}

	public static function handleHistoryActionWithEmptyLangLinksProvider() {
		return [
			[
				[],
				[],
				true, // wikibase enabled for namespace
				[], // no lang links
				null, // no prefixed id
				true, // user logged in
				false, // user is not temp user
			],
		];
	}

	/**
	 * @dataProvider handleDataBridgeEnabledProvider
	 */
	public function testHandle_dataBridgeEnabled(
		$expectedJsModules, $expectedCssModules,
		$dataBridgeEnabled, $wikibaseEnabled
	) {
		$skin = $this->getSkin();
		$context = $this->getContext( false, false, false );
		$output = $this->getOutputPage( $context, [] );
		$namespaceChecker = $this->getNamespaceChecker( $wikibaseEnabled );

		$handler = new BeforePageDisplayHandler( false, $namespaceChecker, $dataBridgeEnabled );
		$handler->addModules( $output, 'view', $skin );

		$this->assertSame( $expectedJsModules, $output->getModules(), 'js modules' );
		$this->assertSame( $expectedCssModules, $output->getModuleStyles(), 'css modules' );
	}

	public static function handleDataBridgeEnabledProvider() {
		yield 'disabled' => [
			[],
			[],
			false, // data bridge disabled
			true, // Wikibase enabled
		];

		yield 'enabled' => [
			[ 'wikibase.client.data-bridge.init' ],
			[ 'wikibase.client.data-bridge.externalModifiers' ],
			true, // data bridge enabled
			true, // Wikibase enabled
		];

		yield 'enabled but Wikibase disabled' => [
			[],
			[],
			true, // data bridge enabled
			false, // Wikibase disabled
		];
	}

	private function getContext( bool $loggedIn, bool $tempUser, bool $titleExists ): IContextSource {
		$context = $this->createMock( IContextSource::class );
		$context->method( 'getRequest' )->willReturn( new WebRequest() );
		$context->method( 'getConfig' )->willReturn( new HashConfig() );

		$title = $this->getTitle( $titleExists );
		$context->method( 'getTitle' )->willReturn( $title );

		$user = $this->getUser( $loggedIn, $tempUser );
		$context->method( 'getUser' )->willReturn( $user );

		return $context;
	}

	/**
	 * @param bool $titleExists
	 *
	 * @return Title
	 */
	private function getTitle( $titleExists ) {
		$title = $this->createMock( Title::class );

		$title->method( 'exists' )
			->willReturn( $titleExists );

		return $title;
	}

	/**
	 * @param bool $loggedIn
	 * @param bool $tempUser
	 *
	 * @return User
	 */
	private function getUser( $loggedIn, $tempUser ) {
		$user = $this->createMock( User::class );

		$user->method( 'isRegistered' )
			->willReturn( $loggedIn );
		$user->method( 'isNamed' )
			->willReturn( $loggedIn && !$tempUser );

		return $user;
	}

	/**
	 * @param bool $wikibaseEnabled
	 *
	 * @return NamespaceChecker
	 */
	private function getNamespaceChecker( $wikibaseEnabled ) {
		$namespaceChecker = $this->createMock( NamespaceChecker::class );

		$namespaceChecker->method( 'isWikibaseEnabled' )
			->willReturn( $wikibaseEnabled );

		return $namespaceChecker;
	}

	private function getOutputPage( IContextSource $context, $langLinks, $prefixedId = null,
		$noexternallanglinks = null
	) {
		$output = new OutputPage( $context );
		$output->setLanguageLinks( $langLinks );

		if ( $prefixedId ) {
			$output->setProperty( 'wikibase_item', $prefixedId );
		}

		if ( $noexternallanglinks ) {
			$output->setProperty( 'noexternallanglinks', $noexternallanglinks );
		}

		return $output;
	}

	private function getSkin(): Skin {
		return $this->createNoOpMock( Skin::class, [ 'getSkinName' ] );
	}

}
