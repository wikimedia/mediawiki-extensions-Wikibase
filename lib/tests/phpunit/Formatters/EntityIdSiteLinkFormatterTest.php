<?php

namespace Wikibase\Lib\Tests\Formatters;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Formatters\EntityIdSiteLinkFormatter;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers Wikibase\Lib\Formatters\EntityIdSiteLinkFormatter
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class EntityIdSiteLinkFormatterTest extends PHPUnit_Framework_TestCase {

	public function formatEntityIdProvider() {
		return [
			[
				new SiteLink( 'enwiki', "'PAGE'" ),
				new Term( 'en', "'LABEL'" ),
				"[['PAGE'|&#39;LABEL&#39;]]"
			],
			[
				new SiteLink( 'enwiki', "'PAGE'" ),
				new Term( 'en', '' ),
				"[['PAGE']]"
			],
			[
				new SiteLink( 'enwiki', "'PAGE'" ),
				null,
				"[['PAGE']]"
			],
			[
				null,
				new Term( 'en', "'LABEL'" ),
				'&#39;LABEL&#39;'
			],
			[
				null,
				null,
				'Q1'
			],
		];
	}

	/**
	 * @dataProvider formatEntityIdProvider
	 */
	public function testFormatEntityId( SiteLink $siteLink = null, Term $label = null, $expected ) {
		$id = new ItemId( 'Q1' );

		$siteLinkLookup = $this->getMock( SiteLinkLookup::class );
		$siteLinkLookup->expects( $this->any() )
			->method( 'getLinks' )
			->with( [ $id->getNumericId() ], [ 'enwiki' ], [] )
			->will( $this->returnValue( $siteLink
				? [ [ null, $siteLink->getPageName() ] ]
				: null
			) );

		$labelLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->with( $id )
			->will( $this->returnValue( $label ) );

		$formatter = new EntityIdSiteLinkFormatter(
			$siteLinkLookup,
			'enwiki',
			$labelLookup
		);

		$this->assertSame( $expected, $formatter->formatEntityId( $id ) );
	}

}
