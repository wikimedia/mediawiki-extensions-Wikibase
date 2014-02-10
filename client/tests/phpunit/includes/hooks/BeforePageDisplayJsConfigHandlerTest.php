<?php

namespace Wikibase\Test;

use OutputPage;
use RequestContext;
use Title;
use Wikibase\NamespaceChecker;
use Wikibase\Client\Hooks\BeforePageDisplayJsConfigHandler;

/**
 * @covers Wikibase\Client\Hooks\BeforePageDisplayJsConfigHandler
 *
 * @group WikibaseClient
 * @group WikibaseHooks
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BeforePageDisplayJsConfigHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider addConfigProvider
	 */
	public function testAddConfig( $expected, $namespaceChecker, $outputPage ) {
		$handler = new BeforePageDisplayJsConfigHandler( $namespaceChecker );
		$result = $handler->addConfig( $outputPage );

		$this->assertEquals( $expected, $result->getJsConfigVars() );
	}

	public function addConfigProvider() {
		return array(
			array(
				array( 'wgWikibaseItemId' => 'Q4' ),
				$this->getNamespaceChecker( true ),
				$this->getOutputPage( 'Q4' )
			),
			array(
				array(),
				$this->getNamespaceChecker( true ),
				$this->getOutputPage( null )
			),
			array(
				array(),
				$this->getNamespaceChecker( false ),
				$this->getOutputPage( 'Q4' )
			)
		);
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

	private function getOutputPage( $prefixedId ) {
		$context = new RequestContext();
		$context->setTitle( Title::makeTitle( NS_HELP, 'Contents' ) );
		$output = $context->getOutput();

		if ( $prefixedId ) {
			$output->setProperty( 'wikibase_item', $prefixedId );
		}

		return $output;
	}

}
