<?php

namespace Wikibase\Repo\Tests\Localizer;

use DataValues\DataValue;
use DataValues\DecimalValue;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Site\Site;
use MediaWiki\Site\SiteLookup;
use MediaWikiLangTestCase;
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
class MessageParameterFormatterTest extends MediaWikiLangTestCase {

	public static function formatProvider() {
		$decimal = new DecimalValue( '+123.456' );
		$siteLink = new SiteLink( 'acme', 'Foo' );
		$badSiteLink = new SiteLink( 'bad', 'Foo' );

		return [
			'string' => [ 'Hello', 'Hello' ],
			'int' => [ 123456, '123,456' ],
			'float' => [ 123456.789, '123,456.789' ],
			'DecimalValue' => [ $decimal, 'DataValues\DecimalValue:+123.456' ],
			'SiteLink' => [ $siteLink, '<a rel="nofollow" class="external text" href="http://acme.com/Foo">acme:Foo</a>' ],
			'SiteLink bad' => [ $badSiteLink, '[bad:Foo]' ],
			'list of floats' => [ [ 987654.2, 2000.5 ], '987,654.2, 2,000.5' ],
		];
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( $param, $expectedHtml ) {
		$formatter = new MessageParameterFormatter(
			$this->getMockValueFormatter(),
			$this->getMockIdFormatter(),
			$this->getMockSitesTable()
		);

		$formattedWikitext = $formatter->format( $param );
		$message = wfMessage( 'parentheses' )->params( $formattedWikitext );
		$this->assertSame( "($expectedHtml)", $message->parse() );
	}

	public static function formatWithoutParseProvider(): iterable {
		// these types would require database access to parse,
		// so test them at the wikitext level instead

		$entityId = new ItemId( 'Q123' );
		yield 'EntityId' => [ $entityId, '[[ENTITYID]]' ];
	}

	/** @dataProvider formatWithoutParseProvider */
	public function testFormatWithoutParse( $param, $expectedWikitext ): void {
		$formatter = new MessageParameterFormatter(
			$this->getMockValueFormatter(),
			$this->getMockIdFormatter(),
			$this->getMockSitesTable()
		);

		$actualWikitext = $formatter->format( $param );
		$this->assertSame( $expectedWikitext, $actualWikitext );
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
