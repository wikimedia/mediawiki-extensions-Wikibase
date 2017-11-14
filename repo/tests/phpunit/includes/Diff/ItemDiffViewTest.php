<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use HashSiteStore;
use PHPUnit_Framework_TestCase;
use RequestContext;
use TestSites;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Diff\ItemDiffView;

/**
 * @covers Wikibase\Repo\Diff\ItemDiffView
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class ItemDiffViewTest extends PHPUnit_Framework_TestCase {

	public function diffOpProvider() {
		$linkPath = wfMessage( 'wikibase-diffview-link' )->text();

		return [
			'Empty' => [
				'@^$@',
			],
			'Link is linked' => [
				'@<a\b[^>]* href="[^"]*\bNEW"[^>]*>NEW</a>@',
				null,
				'NEW',
				$linkPath . '/enwiki'
			],
			'Link has direction' => [
				'@<a\b[^>]* dir="auto"@',
				null,
				'NEW',
				$linkPath . '/enwiki'
			],
			'Link has hreflang' => [
				'@<a\b[^>]* hreflang="en"@',
				null,
				'NEW',
				$linkPath . '/enwiki'
			],
			'Badge is linked correctly' => [
				'@FORMATTED BADGE ID@',
				null,
				'Q123',
				$linkPath . '/enwiki/badges'
			]
		];
	}

	private function getDiffOps( $oldValue = null, $newValue = null ) {
		$diffOps = [];
		if ( $oldValue !== null && $newValue !== null ) {
			$diffOps['change'] = new DiffOpChange( $oldValue, $newValue );
		} elseif ( $oldValue !== null ) {
			$diffOps['remove'] = new DiffOpRemove( $oldValue );
		} elseif ( $newValue !== null ) {
			$diffOps['add'] = new DiffOpAdd( $newValue );
		}
		return $diffOps;
	}

	/**
	 * @param string[] $path
	 * @param Diff $diff
	 *
	 * @return ItemDiffView
	 */
	private function getDiffView( array $path, Diff $diff ) {
		$siteStore = new HashSiteStore( TestSites::getSites() );

		$entityIdFormatter = $this->getMock( EntityIdFormatter::class );
		$entityIdFormatter->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnValue( 'FORMATTED BADGE ID' ) );

		$diffView = new ItemDiffView(
			$path,
			$diff,
			$siteStore,
			$entityIdFormatter,
			new RequestContext()
		);

		return $diffView;
	}

	/**
	 * @dataProvider diffOpProvider
	 * @param string $pattern
	 * @param string|null $oldValue
	 * @param string|null $newValue
	 * @param string|string[] $path
	 */
	public function testGetHtml( $pattern, $oldValue = null, $newValue = null, $path = [] ) {
		if ( !is_array( $path ) ) {
			$path = preg_split( '@\s*/\s*@', $path );
		}
		$diff = new Diff( $this->getDiffOps( $oldValue, $newValue ) );

		$diffView = $this->getDiffView( $path, $diff );
		$html = $diffView->getHtml();

		$this->assertInternalType( 'string', $html );

		$pos = strpos( $html, '</tr><tr>' );
		if ( $pos !== false ) {
			$pos += 5;
			$header = substr( $html, 0, $pos );
			$html = substr( $html, $pos );

			$this->assertRegExp(
				'@^<tr><td\b[^>]* colspan="2"[^>]*>[^<]*</td><td\b[^>]* colspan="2"[^>]*>[^<]*</td></tr>$@',
				$header,
				'Diff table header line'
			);
		}

		$this->assertRegExp( $pattern, $html, 'Diff table content line' );
	}

	/**
	 * @dataProvider invalidBadgeIdProvider
	 * @param string $badgeId
	 */
	public function testGivenInvalidBadgeId_getHtmlDoesNotThrowException( $badgeId ) {
		$path = [
			wfMessage( 'wikibase-diffview-link' )->text(),
			'enwiki',
			'badges'
		];
		$diff = new Diff( [ new DiffOpAdd( $badgeId ) ] );

		$diffView = $this->getDiffView( $path, $diff );
		$html = $diffView->getHtml();

		$this->assertContains( htmlspecialchars( $badgeId ), $html );
	}

	public function invalidBadgeIdProvider() {
		return [
			[ 'invalidBadgeId' ],
			[ '<a>injection</a>' ],
		];
	}

}
