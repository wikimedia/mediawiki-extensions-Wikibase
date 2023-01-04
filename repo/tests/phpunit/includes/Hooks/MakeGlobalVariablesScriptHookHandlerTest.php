<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Hooks;

use MediaWikiIntegrationTestCase;
use OutputPage;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\Repo\Hooks\Helpers\OutputPageEntityViewChecker;
use Wikibase\Repo\Hooks\Helpers\UserPreferredContentLanguagesLookup;
use Wikibase\Repo\Hooks\MakeGlobalVariablesScriptHookHandler;
use Wikibase\Repo\OutputPageJsConfigBuilder;

/**
 * @covers \Wikibase\Repo\Hooks\MakeGlobalVariablesScriptHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MakeGlobalVariablesScriptHookHandlerTest extends MediaWikiIntegrationTestCase {

	public function testAddsJsVariables(): void {
		$entityViewChecker = $this->createMock( OutputPageEntityViewChecker::class );
		$entityViewChecker->expects( $this->once() )
			->method( 'hasEntityView' )
			->willReturn( true );
		$services = $this->getServiceContainer();
		$language = $services->getContentLanguage();
		$user = $this->getTestUser()->getUser();
		$userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$userLanguageLookup->expects( $this->once() )
			->method( 'getUserSpecifiedLanguages' )
			->with( $user )
			->willReturn( [ 'en', 'invalid', 'de' ] );
		$userPreferredContentLanguagesLookup = $this->createMock( UserPreferredContentLanguagesLookup::class );
		$userPreferredContentLanguagesLookup->expects( $this->once() )
			->method( 'getLanguages' )
			->with( $language->getCode(), $user )
			->willReturn( [ 'pt', 'en', 'de' ] );
		$outputPage = $this->createMock( OutputPage::class );
		$outputPage->expects( $this->never() )
			->method( 'addJsConfigVars' );
		$outputPage->method( 'getUser' )
			->willReturn( $user );
		$outputPage->method( 'getLanguage' )
			->willReturn( $language );
		$outputPage->method( 'getTitle' )
			->willReturn( $services->getTitleFactory()->makeTitle( NS_MAIN, 'Test' ) );

		$badgeItems = [ 'Q1' => 'first-item' ];
		$stringLimit = 42;
		$taintedReferencesEnabled = false;
		$handler = new MakeGlobalVariablesScriptHookHandler(
			$entityViewChecker,
			new OutputPageJsConfigBuilder(),
			new StaticContentLanguages( [ 'en', 'de', 'pt' ] ),
			$userLanguageLookup,
			$userPreferredContentLanguagesLookup,
			'',
			'',
			$badgeItems,
			$stringLimit,
			$taintedReferencesEnabled
		);
		$vars = $badgeItems;
		$handler->onMakeGlobalVariablesScript( $vars, $outputPage );

		$this->assertSame( [ 'en', 'de' ], $vars['wbUserSpecifiedLanguages'] );
		$this->assertSame( [ 'pt', 'en', 'de' ], $vars['wbUserPreferredContentLanguages'] );
		$this->assertArrayHasKey( 'wbCopyright', $vars );
		$this->assertSame( $badgeItems, $vars['wbBadgeItems'] );
		$this->assertSame( $stringLimit, $vars['wbMultiLingualStringLimit'] );
		$this->assertSame( $taintedReferencesEnabled, $vars['wbTaintedReferencesEnabled'] );
	}

	public function testDoesNothingOnNonEntityViewPages(): void {
		$entityViewChecker = $this->createMock( OutputPageEntityViewChecker::class );
		$entityViewChecker->expects( $this->once() )
			->method( 'hasEntityView' )
			->willReturn( false );
		$outputPageJsConfigBuilder = $this->createMock( OutputPageJsConfigBuilder::class );
		$outputPageJsConfigBuilder->expects( $this->never() )->method( $this->anything() );
		$userLanguageLookup = $this->createMock( UserLanguageLookup::class );
		$userLanguageLookup->expects( $this->never() )->method( 'getUserSpecifiedLanguages' );
		$userPreferredContentLanguagesLookup = $this->createMock( UserPreferredContentLanguagesLookup::class );
		$userPreferredContentLanguagesLookup->expects( $this->never() )->method( $this->anything() );
		$outputPage = $this->createMock( OutputPage::class );
		$outputPage->expects( $this->never() )->method( 'addJsConfigVars' );

		$handler = new MakeGlobalVariablesScriptHookHandler(
			$entityViewChecker,
			$outputPageJsConfigBuilder,
			new StaticContentLanguages( [] ),
			$userLanguageLookup,
			$userPreferredContentLanguagesLookup,
			'', '', [], 42, false
		);
		$vars = [];
		$handler->onMakeGlobalVariablesScript( $vars, $outputPage );

		$this->assertSame( [], $vars );
	}

}
