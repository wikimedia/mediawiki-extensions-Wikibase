<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use MediaWikiTestCaseTrait;
use Wikibase\Repo\Diff\BasicDiffView;

/**
 * @covers \Wikibase\Repo\Diff\BasicDiffView
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class BasicDiffViewTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiTestCaseTrait;

	public function diffOpProvider(): iterable {
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
			],
			'Change operation highlights changed word being deleted and inserted' => [
				'@THE ?<del\b[^>]*> ?OLD ?</del> ?WORD.*THE ?<ins\b[^>]*> ?NEW ?</ins> ?WORD@',
				'THE OLD WORD',
				'THE NEW WORD',
			],
		];
	}

	private function getDiffOps( ?string $oldValue, ?string $newValue ): array {
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
	public function testGetHtml(
		string $pattern,
		?string $oldValue = null,
		?string $newValue = null,
		array $path = []
	): void {
		if ( !is_array( $path ) ) {
			$path = preg_split( '@\s*/\s*@', $path );
		}
		$diff = new Diff( $this->getDiffOps( $oldValue, $newValue ) );

		$diffView = new BasicDiffView( $path, $diff );
		$html = $diffView->getHtml();

		$this->assertIsString( $html );

		$pos = strpos( $html, '</tr><tr>' );
		if ( $pos !== false ) {
			$pos += 5;
			$header = substr( $html, 0, $pos );
			$html = substr( $html, $pos );

			$this->assertMatchesRegularExpression(
				'@^<tr><td\b[^>]* colspan="2"[^>]*>[^<]*</td><td\b[^>]* colspan="2"[^>]*>[^<]*</td></tr>$@',
				$header,
				'Diff table header line'
			);
		}

		$this->assertMatchesRegularExpression( $pattern, $html, 'Diff table content line' );
	}

}
