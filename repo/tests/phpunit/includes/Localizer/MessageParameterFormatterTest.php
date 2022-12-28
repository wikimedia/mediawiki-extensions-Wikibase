<?php

namespace Wikibase\Repo\Tests\Localizer;

use DataValues\DataValue;
use DataValues\DecimalValue;
use HashSiteStore;
use MediaWiki\MediaWikiServices;
use Site;
use SiteLookup;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\Localizer\MessageParameterFormatter;

/**
 * @covers \Wikibase\Repo\Localizer\MessageParameterFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MessageParameterFormatterTest extends \PHPUnit\Framework\TestCase {

	public function formatProvider() {
		$decimal = new DecimalValue( '+123.456' );
		$entityId = new ItemId( 'Q123' );
		$siteLink = new SiteLink( 'acme', 'Foo' );
		$badSiteLink = new SiteLink( 'bad', 'Foo' );

		return [
			'string' => [ 'Hello', 'en', 'Hello' ],
			'int' => [ 456, 'en', '456' ],
			'float en' => [ 123.456, 'en', '123.456' ],
			'float de' => [ 123.456, 'de', '123,456' ],
			'DecimalValue en' => [ $decimal, 'en', 'DataValues\DecimalValue:+123.456' ],
			'EntityId' => [ $entityId, 'en', '[[ENTITYID]]' ],
			'SiteLink' => [ $siteLink, 'en', '[http://acme.com/Foo acme:Foo]' ],
			'SiteLink bad' => [ $badSiteLink, 'en', '[bad:Foo]' ],
			'list of floats' => [ [ 1.2, 0.5 ], 'en', '1.2, 0.5' ],
		];
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( $param, $lang, $expected ) {
		$formatter = new MessageParameterFormatter(
			$this->getMockValueFormatter(),
			$this->getMockIdFormatter(),
			$this->getMockSitesTable(),
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $lang )
		);

		$actual = $formatter->format( $param );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @return ValueFormatter
	 */
	private function getMockValueFormatter() {
		$mock = $this->createMock( ValueFormatter::class );
		$mock->method( 'format' )
			->willReturnCallback(
				function ( DataValue $param ) {
					$class = get_class( $param );
					$value = $param->getArrayValue();

					return "$class:$value";
				}
			);

		return $mock;
	}

	/**
	 * @return EntityIdFormatter
	 */
	private function getMockIdFormatter() {
		$mock = $this->createMock( EntityIdFormatter::class );
		$mock->method( 'formatEntityId' )
			->willReturnCallback(
				function ( EntityId $id ) {
					return '[[ENTITYID]]';
				}
			);

		return $mock;
	}

	/**
	 * @return SiteLookup
	 */
	private function getMockSitesTable() {
		$acme = new Site();
		$acme->setGlobalId( 'acme' );
		$acme->setLinkPath( "http://acme.com/$1" );

		return new HashSiteStore( [ $acme ] );
	}

}
