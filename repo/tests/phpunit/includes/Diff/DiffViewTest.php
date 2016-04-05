<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use HashSiteStore;
use PHPUnit_Framework_TestCase;
use TestSites;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Diff\DiffView;

/**
 * @covers Wikibase\Repo\Diff\DiffView
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class DiffViewTest extends PHPUnit_Framework_TestCase {

	public function diffOpProvider() {
		$linkPath = wfMessage( 'wikibase-diffview-link' )->text();

		return array(
			'Empty' => array(
				'@^$@',
			),
			'Add operation inserted' => array(
				'@<ins\b[^>]*>NEW</ins>@',
				null,
				'NEW',
			),
			'Remove operation is deleted' => array(
				'@<del\b[^>]*>OLD</del>@',
				'OLD',
			),
			'Change operation is deleted and inserted' => array(
				'@<del\b[^>]*>OLD</del>.*<ins\b[^>]*>NEW</ins>@',
				'OLD',
				'NEW',
			),
			'Link is linked' => array(
				'@<a\b[^>]* href="[^"]*\bNEW"[^>]*>NEW</a>@',
				null,
				'NEW',
				$linkPath . '/enwiki'
			),
			'Link has direction' => array(
				'@<a\b[^>]* dir="auto"@',
				null,
				'NEW',
				$linkPath . '/enwiki'
			),
			'Link has hreflang' => array(
				'@<a\b[^>]* hreflang="en"@',
				null,
				'NEW',
				$linkPath . '/enwiki'
			),
			'Badge is linked correctly' => array(
				'@FORMATTED BADGE ID@',
				null,
				'Q123',
				$linkPath . '/enwiki/badges'
			)
		);
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
	 * @return DiffView
	 */
	private function getDiffView( array $path, Diff $diff ) {
		$siteStore = new HashSiteStore( TestSites::getSites() );

		$entityIdFormatter = $this->getMock( EntityIdFormatter::class );
		$entityIdFormatter->expects( $this->any() )
			->method( 'formatEntityId' )
			->will( $this->returnValue( 'FORMATTED BADGE ID' ) );

		$diffView = new DiffView(
			$path,
			$diff,
			$siteStore,
			$entityIdFormatter
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
		$path = array(
			wfMessage( 'wikibase-diffview-link' )->text(),
			'enwiki',
			'badges'
		);
		$diff = new Diff( array( new DiffOpAdd( $badgeId ) ) );

		$diffView = $this->getDiffView( $path, $diff );
		$html = $diffView->getHtml();

		$this->assertContains( htmlspecialchars( $badgeId ), $html );
	}

	public function invalidBadgeIdProvider() {
		return array(
			array( 'invalidBadgeId' ),
			array( '<a>injection</a>' ),
		);
	}

}
