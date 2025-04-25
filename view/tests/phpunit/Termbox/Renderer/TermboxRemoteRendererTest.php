<?php

namespace Wikibase\View\Tests\Termbox\Renderer;

use Exception;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MediaWikiServices;
use MWHttpRequest;
use OutOfBoundsException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageWithConversion;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\View\Termbox\Renderer\TermboxNoRemoteRendererException;
use Wikibase\View\Termbox\Renderer\TermboxRemoteRenderer;
use Wikibase\View\Termbox\Renderer\TermboxRenderingException;
use Wikimedia\Stats\StatsFactory;
use Wikimedia\Stats\UnitTestingHelper;

/**
 * @covers \Wikibase\View\Termbox\Renderer\TermboxRemoteRenderer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxRemoteRendererTest extends TestCase {

	/**
	 * @var LoggerInterface|MockObject
	 */
	private $logger;

	/**
	 * @var StatsFactory
	 */
	private $statsFactory;

	/**
	 * @var UnitTestingHelper
	 */
	private $statsHelper;

	protected function setUp(): void {
		$statsHelper = StatsFactory::newUnitTestingHelper()->withComponent( 'WikibaseRepo' );
		$statsFactory = $statsHelper->getStatsFactory();

		$this->logger = $this->createMock( LoggerInterface::class );
		$this->statsFactory = $statsFactory;
		$this->statsHelper = $statsHelper;
	}

	private const SSR_URL = 'https://ssr/termbox';
	private const SSR_TIMEOUT = 3;

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

		$requestFactory = $this->createMock( HttpRequestFactory::class );
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
			new NullLogger(),
			$this->statsFactory
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

		$this->logger->expects( $this->once() )
			->method( 'error' )
			->with(
				'{class}: Problem requesting from the remote server',
				[
					'class' => TermboxRemoteRenderer::class,
					'errormessage' => $upstreamException->getMessage(),
					'exception' => $upstreamException,
				]
			);

		$client = $this->newTermboxRemoteRendererWithRequest( $request );

		try {
			$client->getContent( $entityId, $revision, $language, $editLinkUrl, $this->newLanguageFallbackChain() );
			$this->fail( 'Expected exception did not occur.' );
		} catch ( Exception $exception ) {
			$this->assertInstanceOf( TermboxRenderingException::class, $exception );
			$this->assertSame( 'Encountered request problem', $exception->getMessage() );
			$this->assertSame( $upstreamException, $exception->getPrevious() );
			$this->assertSame(
				1,
				$this->statsHelper->count( 'termbox_remote_renderer_request_error_total' )
			);
		}
	}

	public function testGetContentEncounteringServerErrorResponse_throwsException() {
		$entityId = new ItemId( 'Q42' );
		$revision = 4711;
		$language = 'de';
		$editLinkUrl = '/edit/Q42';

		$request = $this->newHttpRequest();
		$responseStatus = 500;
		$request->expects( $this->once() )
			->method( 'getStatus' )
			->willReturn( $responseStatus );
		$responseContent = 'boo boo';
		$request->expects( $this->once() )
			->method( 'getContent' )
			->willReturn( $responseContent );
		$responseHeaders = [ 'X-bad' => 'not found' ];
		$request->expects( $this->once() )
			->method( 'getResponseHeaders' )
			->willReturn( $responseHeaders );

		$this->logger->expects( $this->once() )
			->method( 'notice' )
			->with(
				'{class}: encountered a bad response from the remote renderer',
				[
					'class' => TermboxRemoteRenderer::class,
					'status' => $responseStatus,
					'content' => $responseContent,
					'headers' => $responseHeaders,
				]
			);

		$client = $this->newTermboxRemoteRendererWithRequest( $request );

		try {
			$client->getContent( $entityId, $revision, $language, $editLinkUrl, $this->newLanguageFallbackChain() );
			$this->fail( 'Expected exception did not occur.' );
		} catch ( Exception $exception ) {
			$this->assertSame( 'Encountered bad response: 500', $exception->getMessage() );
			$this->assertInstanceOf( TermboxRenderingException::class, $exception );
			$this->assertSame(
				1,
				$this->statsHelper->count( 'termbox_remote_renderer_unsuccessful_response_total' )
			);
		}
	}

	public function testGetContentEncounteringNotFoundResponse_throwsException() {
		$entityId = new ItemId( 'Q4711' );
		$revision = 31510;
		$language = 'de';
		$editLinkUrl = '/edit/Q4711';

		$request = $this->newHttpRequest();
		$responseStatus = 404;
		$request->expects( $this->once() )
			->method( 'getStatus' )
			->willReturn( $responseStatus );
		$responseContent = 'nothing to see here';
		$request->expects( $this->once() )
			->method( 'getContent' )
			->willReturn( $responseContent );
		$responseHeaders = [ 'X-bad' => 'not found' ];
		$request->expects( $this->once() )
			->method( 'getResponseHeaders' )
			->willReturn( $responseHeaders );

		$this->logger->expects( $this->once() )
			->method( 'notice' )
			->with(
				'{class}: encountered a bad response from the remote renderer',
				[
					'class' => TermboxRemoteRenderer::class,
					'status' => $responseStatus,
					'content' => $responseContent,
					'headers' => $responseHeaders,
				]
			);

		$client = $this->newTermboxRemoteRendererWithRequest( $request );

		try {
			$client->getContent( $entityId, $revision, $language, $editLinkUrl, $this->newLanguageFallbackChain() );
			$this->fail( 'Expected exception did not occur.' );
		} catch ( Exception $exception ) {
			$this->assertInstanceOf( TermboxRenderingException::class, $exception );
			$this->assertSame( 'Encountered bad response: 404', $exception->getMessage() );
			$this->assertSame(
				1,
				$this->statsHelper->count( 'termbox_remote_renderer_unsuccessful_response_total' )
			);
		}
	}

	public function testGetContentEncounteringRequestTimeout_throwsException() {
		$language = 'de';
		$itemId = 'Q42';
		$entityId = new ItemId( $itemId );
		$revision = 4711;
		$editLinkUrl = "/wiki/Special:SetLabelDescriptionAliases/$itemId";

		$request = $this->newHttpRequest();
		$request->expects( $this->once() )
			->method( 'getStatus' )
			->willReturn( 0 );

		$this->logger->expects( $this->once() )
			->method( 'error' )
			->with(
				'{class}: Problem requesting from the remote server',
				[
					'class' => TermboxRemoteRenderer::class,
					'errormessage' => 'Request failed with status 0. Usually this means network failure or timeout',
				]
			);

		$client = $this->newTermboxRemoteRendererWithRequest( $request );

		try {
			$client->getContent( $entityId, $revision, $language, $editLinkUrl, $this->newLanguageFallbackChain() );
			$this->fail( 'Expected exception did not occur.' );
		} catch ( Exception $exception ) {
			$this->assertInstanceOf( TermboxRenderingException::class, $exception );
			$this->assertSame( 'Encountered bad response: 0', $exception->getMessage() );
			$this->assertSame(
				1,
				$this->statsHelper->count( 'termbox_remote_renderer_request_error_total' )
			);
		}
	}

	public function testGetContentWithoutSsrUrl_throwsException() {
		$language = 'de';
		$itemId = 'Q42';
		$entityId = new ItemId( $itemId );
		$revision = 4711;
		$editLinkUrl = "/wiki/Special:SetLabelDescriptionAliases/$itemId";

		$requestFactory = $this->createMock( HttpRequestFactory::class );
		$requestFactory->expects( $this->never() )
			->method( 'create' );

		$this->logger->expects( $this->never() )
			->method( 'error' );

		$client = new TermboxRemoteRenderer(
			$requestFactory,
			null,
			0,
			$this->logger,
			$this->statsFactory
		);
		try {
			$client->getContent( $entityId, $revision, $language, $editLinkUrl, $this->newLanguageFallbackChain() );
			$this->fail( 'Expected exception did not occur.' );
		} catch ( TermboxRenderingException $exception ) {
			$this->assertInstanceOf( TermboxNoRemoteRendererException::class, $exception );
		}

		$this->expectException( OutOfBoundsException::class );
		$this->statsHelper->count( 'termbox_remote_renderer_unsuccessful_response_total' );
		$this->statsHelper->count( 'termbox_remote_renderer_request_error_total' );
	}

	private function newTermboxRemoteRendererWithRequest( $request ) {
		return new TermboxRemoteRenderer(
			$this->newHttpRequestFactoryWithRequest( $request ),
			self::SSR_URL,
			self::SSR_TIMEOUT,
			$this->logger,
			$this->statsFactory
		);
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
	 * @return TermLanguageFallbackChain
	 */
	private function newLanguageFallbackChain( array $languages = [] ) {
		$stubContentLanguages = $this->createStub( ContentLanguages::class );
		$stubContentLanguages->method( 'hasLanguage' )
			->willReturn( true );
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		return new TermLanguageFallbackChain( array_map( function ( $languageCode ) use ( $languageFactory ) {
			return LanguageWithConversion::factory( $languageFactory->getLanguage( $languageCode ) );
		}, $languages ), $stubContentLanguages );
	}

}
