<?php

namespace Wikibase\View\Tests;

use DataValues\StringValue;
use PHPUnit\Framework\MockObject\Matcher\Invocation;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\SnakHtmlGenerator;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers \Wikibase\View\SnakHtmlGenerator
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Kreuz
 */
class SnakHtmlGeneratorTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @param Invocation $formatPropertyIdMatcher
	 *
	 * @return SnakHtmlGenerator
	 */
	private function getSnakHtmlGenerator( $formatPropertyIdMatcher ) {
		$snakFormatter = $this->createMock( SnakFormatter::class );
		$snakFormatter->expects( $this->once() )
			->method( 'formatSnak' )
			->willReturn( '<SNAK>' );
		$snakFormatter->expects( $this->once() )
			->method( 'getFormat' )
			->willReturn( SnakFormatter::FORMAT_HTML );

		$propertyIdFormatter = $this->createMock( EntityIdFormatter::class );
		$propertyIdFormatter->expects( $formatPropertyIdMatcher )
			->method( 'formatEntityId' )
			->willReturn( '<ID>' );

		return new SnakHtmlGenerator(
			TemplateFactory::getDefaultInstance(),
			$snakFormatter,
			$propertyIdFormatter,
			new DummyLocalizedTextProvider()
		);
	}

	/**
	 * @dataProvider getSnakHtmlProvider
	 */
	public function testGetSnakHtmlWithPropertyLink( Snak $snak, $className ) {
		$generator = $this->getSnakHtmlGenerator( $this->once() );
		$html = $generator->getSnakHtml( $snak, true );

		$this->assertStringContainsString( '<ID>', $html );
		$this->assertStringContainsString( $className, $html, 'snak variation css' );
		$this->assertStringContainsString( '<SNAK>', $html, 'formatted snak' );
	}

	/**
	 * @dataProvider getSnakHtmlProvider
	 */
	public function testGetSnakHtmlWithoutPropertyLink( Snak $snak, $className ) {
		$generator = $this->getSnakHtmlGenerator( $this->never() );
		$html = $generator->getSnakHtml( $snak, false );

		$this->assertStringNotContainsString( '<ID>', $html );
		$this->assertStringContainsString( $className, $html, 'snak variation css' );
		$this->assertStringContainsString( '<SNAK>', $html, 'formatted snak' );
	}

	public function getSnakHtmlProvider() {
		return [
			[
				new PropertyNoValueSnak( 1 ),
				'wikibase-snakview-variation-novalue',
			],
			[
				new PropertySomeValueSnak( 2 ),
				'wikibase-snakview-variation-somevalue',
			],
			[
				new PropertyValueSnak( 3, new StringValue( 'chocolate!' ) ),
				'wikibase-snakview-variation-value',
			],
		];
	}

}
