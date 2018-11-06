<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChain;
use Wikibase\Repo\ParserOutput\TermboxView;
use Wikibase\Repo\ParserOutput\TermboxViewSsrClient;

/**
 * @covers \Wikibase\Repo\ParserOutput\TermboxView
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermboxViewTest extends TestCase {

	use PHPUnit4And6Compat;

	public function testGetHtmlWithClientStringResponse_returnsContent() {
		$language = 'en';
		$entityId = new ItemId( 'Q42' );

		$response = 'termbox says hi';

		$client = $this->getMockBuilder( TermboxViewSsrClient::class )
			->disableOriginalConstructor()
			->getMock();
		$client->expects( $this->once() )
			->method( 'getContent' )
			->with( $entityId, $language )
			->willReturn( $response );

		$this->assertSame(
			$response,
			$this->newTermbox( $client )->getHtml(
				$language,
				new TermList( [] ),
				new TermList( [] ),
				null,
				$entityId
			)
		);
	}

	public function testGetHtmlWithClientThrowingException_returnsFallbackContent() {
		$language = 'en';
		$entityId = new ItemId( 'Q42' );

		$client = $this->getMockBuilder( TermboxViewSsrClient::class )
			->disableOriginalConstructor()
			->getMock();
		$client->expects( $this->once() )
			->method( 'getContent' )
			->willThrowException( new Exception( 'unspecific' ) );

		$this->assertSame(
			TermboxView::FALLBACK_HTML,
			$this->newTermbox( $client )->getHtml(
				$language,
				new TermList( [] ),
				new TermList( [] ),
				null,
				$entityId
			)
		);
	}

	private function newTermbox( TermboxViewSsrClient $client ): TermboxView {
		return new TermboxView(
			new LanguageFallbackChain( [] ),
			$client
		);
	}

}
