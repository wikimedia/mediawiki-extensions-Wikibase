<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests;

use MediaWiki\MediaWikiServices;
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
		$builder = new AnonymousEditWarningBuilder(
			MediaWikiServices::getInstance()->getSpecialPageFactory()
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
}
