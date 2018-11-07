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

	/** private */ const SSR_URL = 'https://ssr/termbox';

	public function testGetHtml() {
		$response = 'termbox says hi';
		$request = $this->newHttpRequest();
		$request->expects( $this->once() )
			->method( 'getContent' )
			->willReturn( $response );

		$requestFactory = $this->newHttpRequestFactory();
		$requestFactory->expects( $this->once() )
			->method( 'create' )
			->with( self::SSR_URL )
			->willReturn( $request );

		$this->assertEquals(
			$response,
			$this->newTermbox( $requestFactory )->getHtml(
				'en',
				new TermList( [] ),
				new TermList( [] )
			)
		);
	}

	private function newTermbox( HttpRequestFactory $requestFactory ): TermboxView {
		return new TermboxView(
			new LanguageFallbackChain( [] ),
			$requestFactory,
			$this->newSettingsWithSsrUrl()
		);
	}

	/**
	 * @return MockObject|HttpRequestFactory
	 */
	private function newHttpRequestFactory() {
		return $this->createMock( HttpRequestFactory::class );
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
