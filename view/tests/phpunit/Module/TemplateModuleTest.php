<?php

namespace Wikibase\View\Template\Test;

use PHPUnit_Framework_TestCase;
use Wikibase\View\Module\TemplateModule;

/**
 * @covers Wikibase\View\Module\TemplateModule
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class TemplateModuleTest extends PHPUnit_Framework_TestCase {

	public function testGetScript() {
		$context = $this->getMockBuilder( 'ResourceLoaderContext' )
			->disableOriginalConstructor()
			->getMock();
		$context->expects( $this->any() )
			->method( 'getLanguage' )
			->will( $this->returnValue( 'en' ) );

		$instance = new TemplateModule();
		$script = $instance->getScript( $context );
		$this->assertInternalType( 'string', $script );
		$this->assertContains( 'wbTemplates', $script );
		$this->assertContains( 'set( {', $script );
	}

	public function testSupportsURLLoading() {
		$instance = new TemplateModule();
		$this->assertFalse( $instance->supportsURLLoading() );
	}

}
