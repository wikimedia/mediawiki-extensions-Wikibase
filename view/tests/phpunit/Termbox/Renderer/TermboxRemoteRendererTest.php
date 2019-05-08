<?php

namespace Wikibase\View\Tests\Termbox\Renderer;

use Exception;
use Language;
use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageWithConversion;
use Wikibase\View\Termbox\Renderer\TermboxRemoteRenderer;
use Wikibase\View\Termbox\Renderer\TermboxRenderingException;

/**
 * @covers \Wikibase\View\Termbox\Renderer\TermboxRemoteRenderer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxRemoteRendererTest extends TestCase {

	use PHPUnit4And6Compat;

	/** private */ const SSR_URL = 'https://ssr/termbox';
	/** private */ const SSR_TIMEOUT = 3;

	public function testGetContentWithSaneParameters_returnsRequestResponse() {
		$content = 'hello from server!';

		$request = $this->newSuccessfulRequest();
		$request->expects( $this->once() )
			->method( 'getContent' )
			->willReturn( $content );

		$client = $this->newTermboxRemoteRendererWithRequest( $request );
		$this->assertSame(
			$content,
			$client->getContent(
				new ItemId( 'Q42' ),
				4711,
				'de',
				'/edit/Q42',
				$this->newLanguageFallbackChain()
			)
		);
	}

	public function testGivenValidParameters_createsWellFormedAndConfiguredRequest() {
		$language = 'de';
		$itemId = 'Q42';
		$revision = 4711;
		$editLinkUrl = "/wiki/Special:SetLabelDescriptionAliases/$itemId";
		$preferredLanguages = [ 'en', 'fr', 'es' ];
		$fallbackChain = $this->newLanguageFallbackChain( $preferredLanguages );

		$requestFactory = $this->newHttpRequestFactory();
		$requestFactory->expects( $this->once() )
			->method( 'create' )
			->with(
				self::SSR_URL
				. '?' . http_build_query( [
					'entity' => $itemId,
					'revision' => $revision,
					'language' => $language,
					'editLink' => $editLinkUrl,
					'preferredLanguages' => "$preferredLanguages[0]|$preferredLanguages[1]|$preferredLanguages[2]",
				] ),
				[ 'timeout' => self::SSR_TIMEOUT ]
			)
			->willReturn( $this->newSuccessfulRequest() );

		( new TermboxRemoteRenderer(
			$requestFactory,
			self::SSR_URL,
			self::SSR_TIMEOUT,
			new NullLogger()
		) )->getContent( new ItemId( $itemId ), $revision, $language, $editLinkUrl, $fallbackChain );
	}

	public function testGetContentEncounteringUpstreamException_bubblesRequestException() {
		$entityId = new ItemId( 'Q42' );
		$revision = 4711;
		$language = 'de';
		$editLinkUrl = '/edit/Q42';

		$upstreamException = new Exception( 'domain exception' );

		$request = $this->newHttpRequest();
		$request->expects( $this->once() )
			->method( 'execute' )
			->willThrowException( $upstreamException );

		$client = $this->newTermboxRemoteRendererWithRequest( $request );

		try {
			$client->getContent( $entityId, $revision, $language, $editLinkUrl, $this->newLanguageFallbackChain() );
			$this->fail( 'Expected exception did not occur.' );
		} catch ( Exception $exception ) {
			$this->assertInstanceOf( TermboxRenderingException::class, $exception );
			$this->assertSame( 'Encountered request problem', $exception->getMessage() );
			$this->assertSame( $upstreamException, $exception->getPrevious() );
		}
	}

	public function testGetContentEncounteringServerErrorResponse_throwsException() {
		$entityId = new ItemId( 'Q42' );
		$revision = 4711;
		$language = 'de';
		$editLinkUrl = '/edit/Q42';

		$request = $this->newHttpRequest();
		$request->expects( $this->once() )
			->method( 'getStatus' )
			->willReturn( 500 );

		$client = $this->newTermboxRemoteRendererWithRequest( $request );

		try {
			$client->getContent( $entityId, $revision, $language, $editLinkUrl, $this->newLanguageFallbackChain() );
			$this->fail( 'Expected exception did not occur.' );
		} catch ( Exception $exception ) {
			$this->assertSame( 'Encountered bad response: 500', $exception->getMessage() );
			$this->assertInstanceOf( TermboxRenderingException::class, $exception );
		}
	}

	public function testGetContentEncounteringNotFoundResponse_throwsException() {
		$entityId = new ItemId( 'Q4711' );
		$revision = 31510;
		$language = 'de';
		$editLinkUrl = '/edit/Q4711';

		$request = $this->newHttpRequest();
		$request->expects( $this->once() )
			->method( 'getStatus' )
			->willReturn( 404 );
		$client = $this->newTermboxRemoteRendererWithRequest( $request );

		try {
			$client->getContent( $entityId, $revision, $language, $editLinkUrl, $this->newLanguageFallbackChain() );
			$this->fail( 'Expected exception did not occur.' );
		} catch ( Exception $exception ) {
			$this->assertInstanceOf( TermboxRenderingException::class, $exception );
			$this->assertSame( 'Encountered bad response: 404', $exception->getMessage() );
		}
	}

	public function testGetContentEncounteringRequestTimeout_throwsException() {
		$language = 'de';
		$itemId = 'Q42';
		$entityId = new ItemId( $itemId );
		$revision = 4711;
		$editLinkUrl = "/wiki/Special:SetLabelDescriptionAliases/$itemId";
		$preferredLanguages = [ 'en', 'fr', 'es' ];
		$fallbackChain = $this->newLanguageFallbackChain( $preferredLanguages );

		$request = $this->newHttpRequest();
		$request->expects( $this->once() )
			->method( 'getStatus' )
			->willReturn( 0 );

		$client = $this->newTermboxRemoteRendererWithRequest( $request );
		try {
			$client->getContent( $entityId, $revision, $language, $editLinkUrl, $this->newLanguageFallbackChain() );
			$this->fail( 'Expected exception did not occur.' );
		} catch ( Exception $exception ) {
			$this->assertInstanceOf( TermboxRenderingException::class, $exception );
			$this->assertSame( 'Encountered bad response: 0', $exception->getMessage() );
		}
	}

	private function newTermboxRemoteRendererWithRequest( $request ) {
		return new TermboxRemoteRenderer(
			$this->newHttpRequestFactoryWithRequest( $request ),
			self::SSR_URL,
			self::SSR_TIMEOUT,
			new NullLogger()
		);
	}

	/**
	 * @return MockObject|HttpRequestFactory
	 */
	private function newHttpRequestFactory() {
		return $this->createMock( HttpRequestFactory::class );
	}

	/**
	 * @return MockObject|HttpRequestFactory
	 */
	private function newHttpRequestFactoryWithRequest( MWHttpRequest $req ) {
		$factory = $this->createMock( HttpRequestFactory::class );
		$factory->method( 'create' )
			->willReturn( $req );

		return $factory;
	}

	/**
	 * @return MockObject|MWHttpRequest
	 */
	private function newSuccessfulRequest() {
		$request = $this->newHttpRequest();
		$request->method( 'getStatus' )
			->willReturn( TermboxRemoteRenderer::HTTP_STATUS_OK );

		return $request;
	}

	/**
	 * @return MockObject|MWHttpRequest
	 */
	private function newHttpRequest() {
		$req = $this->createMock( MWHttpRequest::class );
		$req->expects( $this->once() )->method( 'execute' );

		return $req;
	}

	/**
	 * @return LanguageFallbackChain
	 */
	private function newLanguageFallbackChain( $languages = [] ) {
		return new LanguageFallbackChain( array_map( function ( $languageCode ) {
			return LanguageWithConversion::factory( Language::factory( $languageCode ) );
		}, $languages ) );
	}

}
