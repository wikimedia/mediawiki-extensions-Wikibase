<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\Repo\Diff\DiffView;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Repo\Diff\DiffView
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DiffViewTest extends \PHPUnit_Framework_TestCase {

	public function diffOpProvider() {
		$linkPath = wfMessage( 'wikibase-diffview-link' )->text();
		$itemId = new ItemId( 'Q123' );
		$itemTitle = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()->getTitleForId( $itemId );
		$itemLink = $itemTitle->getLinkURL();

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
				'@<a\b[^>]* href="' . preg_quote( $itemLink, '@' ) . '"@',
				null,
				'Q123',
				$linkPath . '/enwiki/badges'
			),
		);
	}

	private function getDiffOps( $oldValue = null, $newValue = null ) {
		$diffOps = array();
		if ( $oldValue !== null && $newValue !== null ) {
			$diffOps['change'] = new DiffOpChange( $oldValue, $newValue );
		} else if ( $oldValue !== null ) {
			$diffOps['remove'] = new DiffOpRemove( $oldValue );
		} else if ( $newValue !== null ) {
			$diffOps['add'] = new DiffOpAdd( $newValue );
		}
		return $diffOps;
	}

	/**
	 * @dataProvider diffOpProvider
	 *
	 * @param string $pattern
	 * @param string|null $oldValue
	 * @param string|null $newValue
	 * @param string|string[] $path
	 */
	public function testGetHtml( $pattern, $oldValue = null, $newValue = null, $path = array() ) {
		if ( is_string( $path ) ) {
			$path = preg_split( '@\s*/\s*@', $path );
		}
		$diff = new Diff( $this->getDiffOps( $oldValue, $newValue ) );
		$siteStore = MockSiteStore::newFromTestSites();
		$entityTitleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$diffView = new DiffView( $path, $diff, $siteStore, $entityTitleLookup );

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

}
