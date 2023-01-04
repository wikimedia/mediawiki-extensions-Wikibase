<?php

namespace Wikibase\Lib\Tests\Formatters;

use HamcrestPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Formatters\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\Formatters\UnknownTypeEntityIdHtmlLinkFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @covers \Wikibase\Lib\Formatters\UnknownTypeEntityIdHtmlLinkFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnknownTypeEntityIdHtmlLinkFormatterTest extends TestCase {

	use HamcrestPHPUnitIntegration;

	public function testFormatEntityIdIdentifyingNullTitle_nonexistingFormatterIsInvoked() {
		$entityId = new ItemId( 'Q7' );

		$entityTitleLookup = $this->createMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $entityId )
			->willReturn( null );

		$nonExistingFormatterResult = 'NonExistingEntityIdHtmlFormatter result';
		$nonExistingFormatter = $this->createMock( NonExistingEntityIdHtmlFormatter::class );
		$nonExistingFormatter->expects( $this->once() )
			->method( 'formatEntityId' )
			->with( $entityId )
			->willReturn( $nonExistingFormatterResult );

		$formatter = new UnknownTypeEntityIdHtmlLinkFormatter(
			$entityTitleLookup,
			$nonExistingFormatter
		);
		$html = $formatter->formatEntityId( $entityId );

		$this->assertSame( $nonExistingFormatterResult, $html );
	}

	public function testFormatEntityId_rendersHtml() {
		$entityId = new ItemId( 'Q42' );

		$entityTitleLookup = $this->createMock( EntityTitleLookup::class );

		$title = Title::makeTitle( NS_MAIN, 'Q42' );
		$entityTitleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $entityId )
			->willReturn( $title );

		$nonExistingFormatter = $this->createMock( NonExistingEntityIdHtmlFormatter::class );

		$formatter = new UnknownTypeEntityIdHtmlLinkFormatter(
			$entityTitleLookup,
			$nonExistingFormatter
		);
		$html = $formatter->formatEntityId( $entityId );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild(
				allOf(
					withTagName( 'a' ),
					havingTextContents( 'Q42' ),
					withAttribute( 'title' )->havingValue( 'Q42' ),
					withAttribute( 'href' )->havingValue( $title->getLocalURL() )
				)
			) ) )
		);
	}

	public function testFormatEntityIdIdentifyingRedirectedTitle_linkHasAdditionalClass() {
		$entityId = new ItemId( 'Q42' );

		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'isRedirect' )
			->willReturn( true );
		$title->method( 'isLocal' )
			->willReturn( true );

		$entityTitleLookup = $this->createMock( EntityTitleLookup::class );
		$entityTitleLookup
			->expects( $this->once() )
			->method( 'getTitleForId' )
			->with( $entityId )
			->willReturn( $title );

		$formatter = new UnknownTypeEntityIdHtmlLinkFormatter(
			$entityTitleLookup,
			$this->createMock( NonExistingEntityIdHtmlFormatter::class )
		);
		$html = $formatter->formatEntityId( $entityId );

		$this->assertThatHamcrest( $html, htmlPiece( havingChild( withClass( 'mw-redirect' ) ) ) );
	}

}
