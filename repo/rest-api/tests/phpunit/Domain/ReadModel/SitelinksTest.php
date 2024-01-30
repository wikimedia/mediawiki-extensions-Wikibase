<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Domain\ReadModel;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelink;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;

/**
 * @covers \Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SitelinksTest extends TestCase {

	public function testConstructor(): void {
		$enWikiSitelink = new Sitelink(
			'enwiki',
			'Potato',
			[ new ItemId( 'Q567' ) ],
			'https://en.wikipedia.org/wiki/Potato'
		);
		$deWikiSitelink = new Sitelink(
			'dewiki',
			'Kartoffel',
			[ new ItemId( 'Q567' ), new ItemId( 'Q789' ) ],
			'https://de.wikipedia.org/wiki/Kartoffel'
		);
		$sitelinks = new Sitelinks( $enWikiSitelink, $deWikiSitelink );

		$this->assertSame( $enWikiSitelink, $sitelinks[ 'enwiki' ] );
		$this->assertSame( $deWikiSitelink, $sitelinks[ 'dewiki' ] );
	}

}
