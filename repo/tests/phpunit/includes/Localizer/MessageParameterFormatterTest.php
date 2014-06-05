<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use DataValues\DecimalValue;
use Language;
use Site;
use SiteStore;
use Title;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\EntityId;
use Wikibase\EntityTitleLookup;
use Wikibase\Repo\Localizer\MessageParameterFormatter;

/**
 * @property mixed getMockValueFormatter
 * @covers Wikibase\Repo\Localizer\MessageParameterFormatter
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MessageParameterFormatterTest extends \PHPUnit_Framework_TestCase {

	public function formatProvider() {
		$decimal = new DecimalValue( '+123.456' );
		$entityId = new ItemId( 'Q123' );
		$siteLink = new SiteLink( 'acme', 'Foo' );

		return array(
			'string' => array( 'Hello', 'en', 'Hello' ),
			'int' => array( 456, 'en', '456' ),
			'float en' => array( 123.456, 'en', '123.456' ),
			'float de' => array( 123.456, 'de', '123,456' ),
			'DecimalValue en' => array( $decimal, 'en', 'DataValues\DecimalValue:+123.456' ),
			'EntityId' => array( $entityId, 'en', '[[Q123|Q123]]' ),
			'SiteLink' => array( $siteLink, 'en', '[http://acme.com/Foo acme:Foo]' ),
			'list of floats' => array( array( 1.2, 0.5 ), 'en', '1.2, 0.5' ),
		);
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( $param, $lang, $expected ) {
		$formatter = new MessageParameterFormatter(
			$this->getMockValueFormatter(),
			$this->getMockTitleLookup(),
			$this->getMockSitesTable(),
			Language::factory( $lang )
		);

		$actual = $formatter->format( $param );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @return ValueFormatter
	 */
	private function getMockValueFormatter() {
		$mock = $this->getMock( 'ValueFormatters\ValueFormatter' );
		$mock->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnCallback(
				function ( DataValue $param ) {
					$class = get_class( $param );
					$value = $param->getArrayValue();

					return "$class:$value";
				}
			) );

		return $mock;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getMockTitleLookup() {
		$mock = $this->getMock( 'Wikibase\EntityTitleLookup' );
		$mock->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback(
				function ( EntityId $id ) {
					return Title::makeTitle( NS_MAIN, $id->getSerialization() );
				}
			) );

		return $mock;
	}

	/**
	 * @return SiteStore
	 */
	private function getMockSitesTable() {
		$mock = $this->getMock( 'SiteStore' );
		$mock->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnCallback(
				function ( $siteId ) {
					$site = new Site();
					$site->setGlobalId( $siteId );
					$site->setLinkPath( "http://$siteId.com/$1" );
					return $site;
				}
			) );

		return $mock;
	}
}
