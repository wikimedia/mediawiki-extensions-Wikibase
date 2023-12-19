<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests;

use MediaWiki\MediaWikiServices;
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
		$this->overrideConfigValue( 'LanguageCode', 'qqx' );
		$tempUserConfig = $this->createMock( TempUserConfig::class );
		$tempUserConfig->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( false );
		$builder = new AnonymousEditWarningBuilder(
			MediaWikiServices::getInstance()->getSpecialPageFactory(),
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
		$this->overrideConfigValue( 'LanguageCode', 'qqx' );
		$tempUserConfig = $this->createMock( TempUserConfig::class );
		$tempUserConfig->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( true );
		$builder = new AnonymousEditWarningBuilder(
			MediaWikiServices::getInstance()->getSpecialPageFactory(),
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
}
