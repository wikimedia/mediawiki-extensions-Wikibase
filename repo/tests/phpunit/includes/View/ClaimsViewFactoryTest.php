<?php

namespace Wikibase\Test;

use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\View\ClaimsViewFactory;

/**
 * @covers Wikibase\Repo\View\ClaimsViewFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class ClaimsViewFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCreateClaimsView() {
		$claimsViewFactory = $this->getClaimsViewFactory();
		$claimsView = $claimsViewFactory->createClaimsView( 'en', $this->getLanguageFallbackChainMock() );
		$this->assertInstanceOf( 'Wikibase\Repo\View\ClaimsView', $claimsView );
	}

	private function getClaimsViewFactory() {
		return new ClaimsViewFactory(
			$this->getSnakFormatterFactoryMock(),
			$this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' ),
			$this->getMock( 'Wikibase\Lib\Store\EntityInfoBuilderFactory' )
		);
	}

	private function getSnakFormatterFactoryMock() {
		$snakFormatterFactory = $this->getMockBuilder( 'Wikibase\Lib\OutputFormatSnakFormatterFactory' )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatterFactory->expects( $this->any() )
			->method( 'getSnakFormatter' )
			->will( $this->returnValue( $this->getSnakFormatterMock() ) );

		return $snakFormatterFactory;
	}

	private function getSnakFormatterMock() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML ) );

		return $snakFormatter;
	}

	private function getLanguageFallbackChainMock() {
		return $this->getMockBuilder( 'Wikibase\LanguageFallbackChain' )
			->disableOriginalConstructor()
			->getMock();
	}

}
