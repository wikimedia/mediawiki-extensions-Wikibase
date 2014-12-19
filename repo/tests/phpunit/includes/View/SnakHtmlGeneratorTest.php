<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Html;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\View\SnakHtmlGenerator;

/**
 * @covers Wikibase\Repo\View\SnakHtmlGenerator
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
		SnakFormatter $snakFormatter,
		EntityIdFormatter $propertyIdFormatter,
		Snak $snak,
		$patterns
	) {
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$propertyIdFormatter
		);

		$html = $snakHtmlGenerator->getSnakHtml( $snak );

		foreach( $patterns as $message => $pattern ) {
			$this->assertRegExp( $pattern, $html, $message );
		}
	}

	public function getSnakHtmlProvider() {
		$snakFormatter = $this->getSnakFormatterMock();

		$propertyIdFormatterMock = $this->getPropertyIdFormatterMock();

		$testCases = array();

		$testCases[] = array(
			$snakFormatter,
			$propertyIdFormatterMock,
			new PropertySomeValueSnak( 42 ),
			array(
				'snak variation css' => '/wb-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!/'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$propertyIdFormatterMock,
			new PropertySomeValueSnak( 42 ),
			array(
				'snak variation css' => '/wb-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!/s'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$propertyIdFormatterMock,
			new PropertyValueSnak( 50, new StringValue( 'chocolate!' ) ),
			array(
				'snak variation css' => '/wb-snakview-variation-value/',
				'formatted snak' => '/a snak!/s'
			)
		);

		return $testCases;
	}

	/**
	 * @return SnakFormatter
	 */
	protected function getSnakFormatterMock() {
		$snakFormatter = $this->getMock( 'Wikibase\Lib\SnakFormatter' );

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( 'a snak!' ) );

		$snakFormatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML ) );

		return $snakFormatter;
	}

	/**
	 * @param EntityId $id
	 * @return string
	 */
	public function getLinkForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getSerialization();
		$url = 'http://wiki.acme.com/wiki/' . urlencode( $name );

		return Html::element( 'a', array( 'href' => $url ), $name );
	}

	/**
	 * @return EntityIdFormatter
	 */
	protected function getPropertyIdFormatterMock() {
		$lookup = $this->getMockBuilder( 'Wikibase\Lib\EntityIdFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$lookup->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnCallback( array( $this, 'getLinkForId' ) ) );

		return $lookup;
	}

}
