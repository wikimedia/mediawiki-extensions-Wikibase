<?php

namespace Wikibase\Test;

use DataValues\DataValue;
use DataValues\DecimalValue;
use Language;
use PHPUnit_Framework_TestCase;
use Site;
use SiteStore;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\Localizer\MessageParameterFormatter;

/**
 * @property mixed getMockValueFormatter
 * @covers Wikibase\Repo\Localizer\MessageParameterFormatter
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MessageParameterFormatterTest extends PHPUnit_Framework_TestCase {

	public function formatProvider() {
		$decimal = new DecimalValue( '+123.456' );
		$entityId = new ItemId( 'Q123' );
		$siteLink = new SiteLink( 'acme', 'Foo' );
		$badSiteLink = new SiteLink( 'bad', 'Foo' );

		return array(
			'string' => array( 'Hello', 'en', 'Hello' ),
			'int' => array( 456, 'en', '456' ),
			'float en' => array( 123.456, 'en', '123.456' ),
			'float de' => array( 123.456, 'de', '123,456' ),
			'DecimalValue en' => array( $decimal, 'en', 'DataValues\DecimalValue:+123.456' ),
			'EntityId' => array( $entityId, 'en', '[[ENTITYID]]' ),
			'SiteLink' => array( $siteLink, 'en', '[http://acme.com/Foo acme:Foo]' ),
			'SiteLink bad' => array( $badSiteLink, 'en', '[bad:Foo]' ),
			'list of floats' => array( array( 1.2, 0.5 ), 'en', '1.2, 0.5' ),
		);
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( $param, $lang, $expected ) {
		$formatter = new MessageParameterFormatter(
			$this->getMockValueFormatter(),
			$this->getMockIdFormatter(),
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
		$mock = $this->getMock( ValueFormatter::class );
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
	 * @return EntityIdFormatter
	 */
	private function getMockIdFormatter() {
		$mock = $this->getMock( EntityIdFormatter::class );
		$mock->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnCallback(
				function ( EntityId $id ) {
					return '[[ENTITYID]]';
				}
			) );

		return $mock;
	}

	/**
	 * @return SiteStore
	 */
	private function getMockSitesTable() {
		$acme = new Site();
		$acme->setGlobalId( 'acme' );
		$acme->setLinkPath( "http://acme.com/$1" );

		$mock = $this->getMock( SiteStore::class );
		$mock->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnValueMap( [
				[ 'acme', $acme ],
			] ) );

		return $mock;
	}

}
