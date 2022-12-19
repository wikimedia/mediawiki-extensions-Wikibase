<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\Specials;

use SpecialPageTestBase;
use Wikibase\Repo\FederatedProperties\SpecialListFederatedProperties;

/**
 * @covers \Wikibase\Repo\FederatedProperties\SpecialListFederatedProperties
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @license GPL-2.0-or-later
 * @author sihe
 */
class SpecialListFederatedPropertiesTest extends SpecialPageTestBase {

	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( 'wgLanguageCode', 'qqx' );
	}

	protected function newSpecialPage() {
		return new SpecialListFederatedProperties(
			'http://my.test.url/w/'
		);
	}

	public function testExecute() {
		[ $output ] = $this->executeSpecialPage( '' );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'href="http://my.test.url/w/index.php?title=Special:ListProperties"', $output );
		$this->assertStringContainsString( 'wikibase-federated-properties-special-list-of-properties-notice', $output );
		$this->assertStringContainsString( 'wikibase-federated-properties-special-list-of-properties-source-ref', $output );
	}

	public function testExecute_withDataTypeId() {
		[ $output ] = $this->executeSpecialPage( 'string' );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'href="http://my.test.url/w/index.php?title=Special:ListProperties/string"', $output );
		$this->assertStringContainsString( 'wikibase-federated-properties-special-list-of-properties-notice', $output );
		$this->assertStringContainsString( 'wikibase-federated-properties-special-list-of-properties-source-ref', $output );
	}
}
