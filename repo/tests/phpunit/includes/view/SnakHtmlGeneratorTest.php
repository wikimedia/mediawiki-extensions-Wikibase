<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\DispatchingSnakFormatter;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\View\SnakHtmlGenerator;

/**
 * @covers Wikibase\SnakHtmlGenerator
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SnakHtmlGeneratorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getSnakHtmlProvider
	 */
	public function testGetSnakHtml(
		$snakFormatter,
		$entityIdHtmlLinkFormatter,
		$propertyLabels,
		$snak,
		$patterns
	) {
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$entityIdHtmlLinkFormatter
		);

		$html = $snakHtmlGenerator->getSnakHtml( $snak, $propertyLabels );

		foreach( $patterns as $message => $pattern ) {
			$this->assertRegExp( $pattern, $html, $message );
		}
	}

	public function getSnakHtmlProvider() {
		$snakFormatter = $this->getSnakFormatterMock();

		$entityIdHtmlLinkFormatter = $this->getEntityIdHtmlLinkFormatterMock();

		$testCases = array();

		$testCases[] = array(
			$snakFormatter,
			$entityIdHtmlLinkFormatter,
			array(),
			new PropertySomeValueSnak( 42 ),
			array(
				'snak variation css' => '/wb-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!/'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$entityIdHtmlLinkFormatter,
			array(),
			new PropertySomeValueSnak( 42 ),
			array(
				'snak variation css' => '/wb-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!/s'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$entityIdHtmlLinkFormatter,
			array(),
			new PropertyValueSnak( 50, new StringValue( 'chocolate!' ) ),
			array(
				'snak variation css' => '/wb-snakview-variation-value/',
				'formatted snak' => '/a snak!/s'
			)
		);

		return $testCases;
	}

	/**
	 * @return DispatchingSnakFormatter
	 */
	protected function getSnakFormatterMock() {
		$snakFormatter = $this->getMockBuilder( 'Wikibase\Lib\DispatchingSnakFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( 'a snak!' ) );

		return $snakFormatter;
	}

	/**
	 * @param EntityId $id
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getPrefixedId();
		return Title::makeTitle( NS_MAIN, $name );
	}

	/**
	 * @return EntityIdHtmlLinkFormatter
	 */
	protected function getEntityIdHtmlLinkFormatterMock() {
		$formatter = $this->getMockBuilder( 'Wikibase\Lib\EntityIdHtmlLinkFormatter' )
			->disableOriginalConstructor()
			->getMock();

		return $formatter;
	}

}
