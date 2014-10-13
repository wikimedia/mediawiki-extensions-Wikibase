<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\DispatchingSnakFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;
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
		$snakFormatter,
		$entityTitleLookup,
		$entityInfo,
		$snak,
		$patterns
	) {
		$snakHtmlGenerator = new SnakHtmlGenerator(
			$snakFormatter,
			$entityTitleLookup
		);

		$html = $snakHtmlGenerator->getSnakHtml( $snak, $entityInfo );

		foreach( $patterns as $message => $pattern ) {
			$this->assertRegExp( $pattern, $html, $message );
		}
	}

	public function getSnakHtmlProvider() {
		$snakFormatter = $this->getSnakFormatterMock();

		$entityTitleLookupMock = $this->getEntityTitleLookupMock();

		$testCases = array();

		$testCases[] = array(
			$snakFormatter,
			$entityTitleLookupMock,
			array(),
			new PropertySomeValueSnak( 42 ),
			array(
				'snak variation css' => '/wb-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!/'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$entityTitleLookupMock,
			array(),
			new PropertySomeValueSnak( 42 ),
			array(
				'snak variation css' => '/wb-snakview-variation-somevalue/',
				'formatted snak' => '/a snak!/s'
			)
		);

		$testCases[] = array(
			$snakFormatter,
			$entityTitleLookupMock,
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

		$snakFormatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_HTML ) );

		return $snakFormatter;
	}

	/**
	 * @param EntityId $id
	 * @return Title
	 */
	public function getTitleForId( EntityId $id ) {
		$name = $id->getEntityType() . ':' . $id->getSerialization();
		return Title::makeTitle( NS_MAIN, $name );
	}

	/**
	 * @return EntityTitleLookup
	 */
	protected function getEntityTitleLookupMock() {
		$lookup = $this->getMock( 'Wikibase\Lib\Store\EntityTitleLookup' );
		$lookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( array( $this, 'getTitleForId' ) ) );

		return $lookup;
	}

}
