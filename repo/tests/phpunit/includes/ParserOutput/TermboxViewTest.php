<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\TermboxView;
use Wikibase\SettingsArray;

/**
 * @covers \Wikibase\Repo\ParserOutput\TermboxView
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxViewTest extends TestCase {

	use PHPUnit4And6Compat;

	/** private */
	const SSR_URL = 'https://ssr/termbox';

	public function testGetHtml() {
		$termbox = $this->newTermbox();

		$termbox->getHtml(
			'en',
			new TermList( [] ),
			new TermList( [] )
		);
	}

	private function newTermbox(): TermboxView {
		return new TermboxView(
			new LanguageFallbackChain( [] ),
			$this->newHttpRequestFactory(),
			$this->newSettingsWithSsrUrl()
		);
	}

	/**
	 * @return MockObject|HttpRequestFactory
	 */
	private function newHttpRequestFactory() {
		$requestFactory = $this->createMock( HttpRequestFactory::class );
		$requestFactory->expects( $this->once() )
			->method( 'create' )
			->with( self::SSR_URL )
			->willReturn( $this->newHttpRequest() );

		return $requestFactory;
	}

	/**
	 * @return MockObject|SettingsArray
	 */
	private function newSettingsWithSsrUrl() {
		$settings = $this->createMock( SettingsArray::class );
		$settings->expects( $this->once() )
			->method( 'getSetting' )
			->with( 'ssrServerUrl' )
			->willReturn( self::SSR_URL );

		return $settings;
	}

	/**
	 * @return MockObject|MWHttpRequest
	 */
	private function newHttpRequest() {
		$req = $this->createMock( MWHttpRequest::class );
		$req->expects( $this->once() )->method( 'execute' );

		return $req;
	}

}
