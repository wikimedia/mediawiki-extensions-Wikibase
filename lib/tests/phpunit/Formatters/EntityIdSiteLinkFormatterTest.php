<?php

namespace Wikibase\Lib\Tests\Formatters;

use PHPUnit4And6Compat;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Formatters\EntityIdSiteLinkFormatter;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * @covers Wikibase\Lib\Formatters\EntityIdSiteLinkFormatter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class EntityIdSiteLinkFormatterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

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

		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->with( $id )
			->will( $this->returnValue( $siteLink
				? Title::newFromText( $siteLink->getPageName() )
				: null
			) );

		$labelLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelLookup->expects( $this->any() )
			->method( 'getLabel' )
			->with( $id )
			->will( $this->returnValue( $label ) );

		$formatter = new EntityIdSiteLinkFormatter( $titleLookup, $labelLookup );

		$this->assertSame( $expected, $formatter->formatEntityId( $id ) );
	}

}
