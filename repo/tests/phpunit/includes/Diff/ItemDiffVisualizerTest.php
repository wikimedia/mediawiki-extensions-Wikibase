<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use HashSiteStore;
use MediaWikiIntegrationTestCase;
use MessageLocalizer;
use RawMessage;
use Site;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\ItemDiffVisualizer;

/**
 * @covers \Wikibase\Repo\Diff\ItemDiffVisualizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class ItemDiffVisualizerTest extends MediaWikiIntegrationTestCase {

	public function testVisualizingEmptyDiff() {
		$emptyDiff = new EntityContentDiff( new EntityDiff(), new Diff(), 'item' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $emptyDiff );

		$this->assertSame( '', $html );
	}

	public function diffProvider() {
		$sitelinkDiff = new EntityContentDiff(
			new ItemDiff( [
				'links' => new Diff( [
					'enwiki' => new Diff( [ 'name' => new DiffOpAdd( 'O_o' ) ] ),
					'nlwiki' => new Diff( [ 'name' => new DiffOpRemove( 'o_O' ) ] ),
					'fawiki' => new Diff( [ 'name' => new DiffOpChange( 'O_O', 'o_o' ) ] ),
				], true ),
			] ),
			new Diff(),
			'item'
		);

		$diffViewLink = '(wikibase-diffview-link)';
		$diffViewLinkName = '(wikibase-diffview-link-name)';
		$sitelinkTags = [
			'has <td>links / enwiki</td>' => ">$diffViewLink / enwiki / $diffViewLinkName</td>",
			'has <ins>O_o</ins>' => '>O_o</a></ins>',
			'has <td>links / nlwiki</td>' => ">$diffViewLink / nlwiki / $diffViewLinkName</td>",
			'has <del>o_O</del>' => '>o_O</span></del>',
			'has <td>links / fawiki</td>' => ">$diffViewLink / fawiki / $diffViewLinkName</td>",
			'has <del>O_O</del>' => '>O_O</span></del>',
			'has <ins>o_o</ins>' => '>o_o</span></ins>',
		];

		$redirectDiff = new EntityContentDiff(
			new ItemDiff(),
			new Diff( [ 'redirect' => new DiffOpAdd( 'Q1234' ) ], true ),
			'item'
		);

		$redirectTags = [
			'has <td>redirect</td>' => '>redirect</td>',
			'has <ins>Q1234</ins>' => '>Q1234</ins>',
		];

		return [
			'sitelink changed' => [ $sitelinkDiff, $sitelinkTags ],
			'redirect changed' => [ $redirectDiff, $redirectTags ],
		];
	}

	/**
	 * @return MessageLocalizer
	 */
	private function getMockMessageLocalizer() {
		$mock = $this->createMock( MessageLocalizer::class );

		$mock->method( 'msg' )
			->willReturnCallback( function ( $key ) {
				return new RawMessage( "($key)" );
			} );

		return $mock;
	}

	/**
	 * @return ClaimDiffer
	 */
	private function getMockClaimDiffer() {
		return $this->createMock( ClaimDiffer::class );
	}

	/**
	 * @return ClaimDifferenceVisualizer
	 */
	private function getMockClaimDiffVisualizer() {
		return $this->createMock( ClaimDifferenceVisualizer::class );
	}

	/**
	 * @return ItemDiffVisualizer
	 */
	private function getVisualizer() {
		$enwiki = new Site();
		$enwiki->setGlobalId( 'enwiki' );

		$basicVisualizer = new BasicEntityDiffVisualizer(
			$this->getMockMessageLocalizer(),
			$this->getMockClaimDiffer(),
			$this->getMockClaimDiffVisualizer()
		);

		return new ItemDiffVisualizer(
			$this->getMockMessageLocalizer(),
			new HashSiteStore( [ $enwiki ] ),
			$this->createMock( EntityIdFormatter::class ),
			$basicVisualizer
		);
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGenerateEntityContentDiffBody( EntityContentDiff $diff, array $matchers ) {
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $diff );

		$this->assertIsString( $html );
		foreach ( $matchers as $name => $matcher ) {
			$this->assertStringContainsString( $matcher, $html, $name );
		}
	}

}
