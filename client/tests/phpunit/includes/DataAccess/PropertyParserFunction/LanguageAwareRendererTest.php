<?php

namespace Wikibase\DataAccess\Tests\PropertyParserFunction;

use DataValues\StringValue;
use Language;
use Wikibase\DataAccess\PropertyParserFunction\LanguageAwareRenderer;
use Wikibase\DataAccess\PropertyParserFunction\SnaksFinder;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\PropertyLabelNotResolvedException;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\LanguageAwareRenderer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class LanguageAwareRendererTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param SnaksFinder $snaksFinder
	 * @param string $languageCode
	 *
	 * @return LanguageAwareRenderer
	 */
	private function getRenderer( SnaksFinder $snaksFinder, $languageCode ) {
		$targetLanguage = Language::factory( $languageCode );

		return new LanguageAwareRenderer(
			$targetLanguage,
			$snaksFinder,
			$this->getSnakFormatter()
		);
	}

	public function testRender() {
		$renderer = $this->getRenderer( $this->getSnaksFinder(), 'en' );

		$result = $renderer->render(
			new ItemId( 'Q42' ),
			'p1337'
		);

		$expected = '(a kitten), (a kitten)';
		$this->assertEquals( $expected, $result );
	}

	private function getSnaksFinder() {
		$snaksFinder = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\SnaksFinder'
			)
			->disableOriginalConstructor()
			->getMock();

		$propertyId = new PropertyId( 'P1337' );
		$snaks = array(
			'Q42$1' => new PropertyValueSnak( $propertyId, new StringValue( 'a kitten!' ) ),
			'Q42$2' => new PropertyValueSnak( $propertyId, new StringValue( 'two kittens!!' ) )
		);

		$snaksFinder->expects( $this->any() )
			->method( 'findSnaks' )
			->will( $this->returnValue( $snaks ) );

		return $snaksFinder;
	}

	public function testRenderForPropertyNotFound() {
		$renderer = $this->getRenderer( $this->getSnaksFinderForPropertyNotFound(), 'qqx' );
		$result = $renderer->render( new ItemId( 'Q4' ), 'invalidLabel' );

		$this->assertRegExp(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$result
		);

		$this->assertRegExp(
			'/wikibase-property-render-error.*invalidLabel.*qqx/',
			$result
		);
	}

	private function getSnaksFinderForPropertyNotFound() {
		$snaksFinder = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\SnaksFinder'
			)
			->disableOriginalConstructor()
			->getMock();

		$snaksFinder->expects( $this->any() )
			->method( 'findSnaks' )
			->will( $this->returnCallback( function() {
				throw new PropertyLabelNotResolvedException( 'invalidLabel', 'qqx' );
			} )
		);

		return $snaksFinder;
	}

	private function getSnakFormatter() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( '(a kitten)' ) );

		return $snakFormatter;
	}

}
