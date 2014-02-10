<?php

namespace Wikibase\Test;

use OutputPage;
use RequestContext;
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

	public function testHandle() {
		$skin = new SkinVector();

		$context = new RequestContext();
		$title = Title::makeTitle( NS_HELP, 'Contents' );
		$context->setTitle( $title );

		$skin->setContext( $context );
		$outputPage = $context->getOutput();

		$settings = new SettingsArray();

		$namespaceChecker = $this->getMockBuilder( 'Wikibase\NamespaceChecker' )
			->disableOriginalConstructor()
			->getMock();

		$handler = new BeforePageDisplayHandler( $settings, $namespaceChecker );
		$outputPage = $handler->handle( $outputPage, $skin );

		$this->assertTrue( true );
	}

}
