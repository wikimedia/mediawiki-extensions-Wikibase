<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\Repo\Diff\BasicDiffView;

/**
 * @covers Wikibase\Repo\Diff\BasicDiffView
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class BasicDiffViewTest extends \PHPUnit\Framework\TestCase {

	public function diffOpProvider() {
		return [
			'Empty' => [
				'@^$@',
			],
			'Add operation inserted' => [
				'@<ins\b[^>]*>NEW</ins>@',
				null,
				'NEW',
			],
			'Remove operation is deleted' => [
				'@<del\b[^>]*>OLD</del>@',
				'OLD',
			],
			'Change operation is deleted and inserted' => [
				'@<del\b[^>]*>OLD</del>.*<ins\b[^>]*>NEW</ins>@',
				'OLD',
				'NEW',
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

		$diffView = new BasicDiffView( $path, $diff );
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
