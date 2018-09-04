<?php

namespace Wikibase\Lib\Tests\Formatters;

use HamcrestPHPUnitIntegration;
use PHPUnit4And6Compat;
use PHPUnit\Framework\TestCase;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\NonExistingEntityIdHtmlFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\UnknownTypeEntityIdHtmlLinkFormatter;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UnknownTypeEntityIdHtmlLinkFormatterTest extends TestCase {

	use HamcrestPHPUnitIntegration;
	use PHPUnit4And6Compat;

	public function testGivenEntityIdWithNullTitle_nonexistingFormatterIsInvoked() {
		$entityId = new ItemId( 'Q7' );

		$entityTitleLookup = $this->createMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->will( $this->returnValue( null ) );

		$nonExistingFormatter = $this->createMock( NonExistingEntityIdHtmlFormatter::class );
		$nonExistingFormatter->expects( $this->once() )
			->method( 'formatEntityId' )
			->with( $entityId );

		$formatter = new UnknownTypeEntityIdHtmlLinkFormatter(
			$entityTitleLookup,
			$nonExistingFormatter
		);

		$formatter->formatEntityId( $entityId );
	}

	public function testFormatEntityId_rendersHtml() {
		$entityId = new ItemId( 'Q42' );

		$entityTitleLookup = $this->createMock( EntityTitleLookup::class );

		$title = Title::newFromText( 'Q42' );
		$entityTitleLookup->expects( $this->once() )
			->method( 'getTitleForId' )
			->will( $this->returnValue( $title ) );

		$nonExistingFormatter = $this->createMock( NonExistingEntityIdHtmlFormatter::class );

		$formatter = new UnknownTypeEntityIdHtmlLinkFormatter(
			$entityTitleLookup,
			$nonExistingFormatter
		);
		$html = $formatter->formatEntityId( $entityId );

		$this->assertThatHamcrest(
			$html,
			is( htmlPiece( havingChild(
				both( withTagName( 'a' ) )
					->andAlso( havingTextContents( 'Q42' ) )
					->andAlso( withAttribute( 'title' )->havingValue( 'Q42' ) )
					->andAlso( withAttribute( 'href' )->havingValue( $title->getLocalURL() ) )
			) ) )
		);
	}

}
