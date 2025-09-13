<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests;

use MediaWiki\MainConfigNames;
use MediaWiki\Page\PageReferenceValue;
use MediaWiki\User\TempUser\TempUserConfig;
use MediaWikiIntegrationTestCase;
use Wikibase\Repo\AnonymousEditWarningBuilder;

/**
 * @covers \Wikibase\Repo\AnonymousEditWarningBuilder
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class AnonymousEditWarningBuilderTest extends MediaWikiIntegrationTestCase {

	public function testBuildAnonymousEditWarningHTML(): void {
		$this->overrideConfigValue( MainConfigNames::LanguageCode, 'qqx' );
		$tempUserConfig = $this->createMock( TempUserConfig::class );
		$tempUserConfig->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( false );
		$builder = new AnonymousEditWarningBuilder(
			$this->getServiceContainer()->getSpecialPageFactory(),
			$this->getServiceContainer()->getTitleFormatter(),
			$tempUserConfig
		);

		$actualHTML = $builder->buildAnonymousEditWarningHTML( 'Foo' );

		$this->assertStringContainsString(
			'wikibase-anonymouseditwarning',
			$actualHTML
		);
		$this->assertStringContainsString(
			'Special:UserLogin&amp;returnto=Foo',
			$actualHTML
		);
		$this->assertStringContainsString(
			'Special:CreateAccount&amp;returnto=Foo',
			$actualHTML
		);
	}

	public function testBuildAnonymousEditWarningHTMLTempUsers(): void {
		$this->overrideConfigValue( MainConfigNames::LanguageCode, 'qqx' );
		$tempUserConfig = $this->createMock( TempUserConfig::class );
		$tempUserConfig->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( true );
		$builder = new AnonymousEditWarningBuilder(
			$this->getServiceContainer()->getSpecialPageFactory(),
			$this->getServiceContainer()->getTitleFormatter(),
			$tempUserConfig
		);

		$actualHTML = $builder->buildAnonymousEditWarningHTML( 'Foo' );

		$this->assertStringContainsString(
			'wikibase-anonymouseditnotificationtempuser',
			$actualHTML
		);
		$this->assertStringContainsString(
			'Special:UserLogin&amp;returnto=Foo',
			$actualHTML
		);
		$this->assertStringContainsString(
			'Special:CreateAccount&amp;returnto=Foo',
			$actualHTML
		);
	}

	public static function provideBuildMessageValues() {
		return [
			'temp users off' => [ false, 'wikibase-anonymouseditwarning' ],
			'temp users on' => [ true, 'wikibase-anonymouseditnotificationtempuser' ],
		];
	}

	/**
	 * @dataProvider provideBuildMessageValues
	 */
	public function testBuildAnonymousEditWarningMessage( bool $tempUsersSetting, string $expectedMessage ): void {
		$this->overrideConfigValues( [
			MainConfigNames::Server => 'https://wiki.example.org',
			MainConfigNames::ScriptPath => '/wiki',
			MainConfigNames::Script => '/wiki/index.php',
			MainConfigNames::LanguageCode => 'en',
		] );
		$tempUserConfig = $this->createMock( TempUserConfig::class );
		$tempUserConfig->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( $tempUsersSetting );
		$builder = new AnonymousEditWarningBuilder(
			$this->getServiceContainer()->getSpecialPageFactory(),
			$this->getServiceContainer()->getTitleFormatter(),
			$tempUserConfig
		);
		$actualMessage = $builder->buildAnonymousEditWarningMessage(
			PageReferenceValue::localReference( NS_SPECIAL, 'SetSiteLink/Q42/be_x_oldwiki' )
		);

		[ $loginParam, $createAccountParam ] = $actualMessage->getParams();

		$this->assertEquals( $expectedMessage, $actualMessage->getKey() );
		$this->assertEquals(
			'https://wiki.example.org/wiki/index.php?title=Special:UserLogin&returnto=Special%3ASetSiteLink%2FQ42%2Fbe_x_oldwiki',
			$loginParam->getValue()
		);
		$this->assertEquals(
			'https://wiki.example.org/wiki/index.php?title=Special:CreateAccount&returnto=Special%3ASetSiteLink%2FQ42%2Fbe_x_oldwiki',
			$createAccountParam->getValue()
		);
	}
}
