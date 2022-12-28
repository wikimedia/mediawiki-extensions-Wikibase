<?php
declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Hooks\Formatters;

use HamcrestPHPUnitIntegration;
use HtmlArmor;
use Language;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;

/**
 * @covers \Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DefaultEntityLinkFormatterTest extends TestCase {
	use HamcrestPHPUnitIntegration;

	private $language;
	private $titleTextLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->language = $this->createMock( Language::class );
		$this->language->method( 'getHtmlCode' )->willReturn( 'en' );
		$this->language->method( 'getDir' )->willReturn( 'ltr' );
		$this->titleTextLookup = $this->createMock( EntityTitleTextLookup::class );
	}

	public function testGetHtmlWithoutLabel() {
		$id = new ItemId( 'Q42' );

		$this->assertThatHamcrest(
			$this->newLinkFormatter()->getHtml( $id ),
			is( htmlPiece( havingChild(
				havingTextContents( containsString( $id->getSerialization() ) )
			) ) )
		);
	}

	public function testGetHtmlWithLabel() {
		$labelData = [ 'language' => 'en', 'value' => 'foobar' ];

		$this->assertThatHamcrest(
			$this->newLinkFormatter()->getHtml( new ItemId( 'Q42' ), $labelData ),
			is( htmlPiece( havingChild(
				havingTextContents( containsString( $labelData['value'] ) )
			) ) )
		);
	}

	public function testGetHtmlAllowsHtmlArmorLabelData() {
		$labelData = [
			'language' => 'en',
			'value' => new HtmlArmor( '<span id="html-label">HELLO</span>' ),
		];

		$this->assertThatHamcrest(
			$this->newLinkFormatter()->getHtml( new ItemId( 'Q42' ), $labelData ),
			is( htmlPiece( havingChild(
				both( tagMatchingOutline( '<span id="html-label">' ) )
					->andAlso( havingTextContents( 'HELLO' ) )
			) ) )
		);
	}

	public function testGetTitleAttributeWithoutLabelAndDescription() {
		$id = new ItemId( 'Q123' );
		$expectedTitleText = 'Item:Q42';

		$this->titleTextLookup = $this->createMock( EntityTitleTextLookup::class );
		$this->titleTextLookup->expects( $this->once() )
			->method( 'getPrefixedText' )
			->with( $id )
			->willReturn( $expectedTitleText );

		$this->assertStringContainsString(
			$expectedTitleText,
			$this->newLinkFormatter()->getTitleAttribute( $id )
		);
	}

	public function testGetTitleAttributeWithLabel() {
		$labelData = [ 'language' => 'en', 'value' => 'foo' ];

		$titleAttribute = $this->newLinkFormatter()->getTitleAttribute( new ItemId( 'Q123' ), $labelData );

		$this->assertStringContainsString(
			$labelData['value'],
			$titleAttribute
		);
	}

	public function testGetTitleAttributeWithLabelAndDescription() {
		$labelData = [ 'language' => 'en', 'value' => 'foo' ];
		$descriptionData = [ 'language' => 'en', 'value' => 'foo bar foo' ];

		$titleAttribute = $this->newLinkFormatter()->getTitleAttribute( new ItemId( 'Q123' ), $labelData, $descriptionData );

		$this->assertStringContainsString(
			$labelData['value'],
			$titleAttribute
		);
		$this->assertStringContainsString(
			$descriptionData['value'],
			$titleAttribute
		);
	}

	private function newLinkFormatter() {
		return new DefaultEntityLinkFormatter(
			$this->language,
			$this->titleTextLookup,
			MediaWikiServices::getInstance()->getLanguageFactory()
		);
	}

}
