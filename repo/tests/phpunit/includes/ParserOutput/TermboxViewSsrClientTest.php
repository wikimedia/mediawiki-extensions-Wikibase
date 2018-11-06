<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Exception;
use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\ParserOutput\TermboxViewSsrClient;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Wikibase\Repo\ParserOutput\TermboxViewSsrClient
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxViewSsrClientTest extends TestCase {

	use PHPUnit4And6Compat;

	/** private */ const SSR_URL = 'https://ssr/termbox';

	public function testGetContentWithEntityIdAndLanguage_returnsRequestResponse() {
		$content = 'hello from server!';

		$request = $this->newHttpRequest();
		$request->expects( $this->once() )
			->method( 'getContent' )
			->willReturn( $content );

		$requestFactory = $this->newHttpRequestFactory();
		$requestFactory->expects( $this->once() )
			->method( 'create' )
			->with( self::SSR_URL . '?entity=Q42&language=de', [] )
			->willReturn( $request );

		$client = new TermboxViewSsrClient( $requestFactory, self::SSR_URL );
		$this->assertSame(
			$content,
			$client->getContent( new ItemId( 'Q42' ), 'de' )
		);
	}

	/**
	 * @expectedException Exception
	 */
	public function testGetContentWithEntityIdAndLanguage_bubblesRequestException() {
		$entityId = new ItemId( 'Q42' );
		$language = 'de';

		$request = $this->newHttpRequest();
		$request->expects( $this->once() )
			->method( 'getContent' )
			->willThrowException( new Exception( 'unspecific' ) );

		$requestFactory = $this->newHttpRequestFactory();
		$requestFactory->expects( $this->once() )
			->method( 'create' )
			->with( self::SSR_URL . '?entity=Q42&language=de', [] )
			->willReturn( $request );

		$client = new TermboxViewSsrClient( $requestFactory, self::SSR_URL );
		$client->getContent( $entityId, $language );
	}

	/**
	 * @return MockObject|HttpRequestFactory
	 */
	private function newHttpRequestFactory() {
		return $this->createMock( HttpRequestFactory::class );
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
