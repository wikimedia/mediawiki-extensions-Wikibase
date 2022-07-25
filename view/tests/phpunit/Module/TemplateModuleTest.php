<?php

declare( strict_types = 1 );

namespace Wikibase\View\Tests\Module;

use MediaWiki\ResourceLoader\Context;
use Wikibase\View\Module\TemplateModule;

/**
 * @covers \Wikibase\View\Module\TemplateModule
 *
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class TemplateModuleTest extends \PHPUnit\Framework\TestCase {

	public function testGetScript() {
		$instance = new TemplateModule();
		$script = $instance->getScript( $this->getResourceLoaderContext() );
		$this->assertIsString( $script );
		$this->assertStringContainsString( 'wbTemplates', $script );
		$this->assertStringContainsString( 'set( {', $script );
	}

	public function testSupportsURLLoading() {
		$instance = new TemplateModule();
		$this->assertFalse( $instance->supportsURLLoading() );
	}

	public function testGetDefinitionSummary() {
		$context = $this->getResourceLoaderContext();
		$file = __DIR__ . '/../../../resources/templates.php';

		$instance = new TemplateModule();
		$oldSummary = $instance->getDefinitionSummary( $context );
		$this->assertIsArray( $oldSummary );
		$this->assertIsString( $oldSummary['mtime'] );

		if ( !is_writable( $file ) || !touch( $file, mt_rand( 0, time() ) ) ) {
			$this->markTestSkipped( "Can't test the modified hash, if we can't touch the file" );
		}

		clearstatcache();
		$newSummary = $instance->getDefinitionSummary( $context );

		$this->assertNotEquals( $oldSummary['mtime'], $newSummary['mtime'] );
	}

	/**
	 * @return Context
	 */
	private function getResourceLoaderContext() {
		$context = $this->createMock( Context::class );
		$context->method( 'getLanguage' )
			->willReturn( 'en' );
		return $context;
	}

}
