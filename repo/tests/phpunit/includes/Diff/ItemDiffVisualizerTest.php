<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use HashSiteStore;
use MediaWikiTestCase;
use MessageLocalizer;
use RawMessage;
use Site;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\ItemDiff;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ItemDiffVisualizer;

/**
 * @covers Wikibase\Repo\Diff\ItemDiffVisualizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class ItemDiffVisualizerTest extends MediaWikiTestCase {

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
		$mock = $this->getMock( MessageLocalizer::class );

		$mock->expects( $this->any() )
			->method( 'msg' )
			->will( $this->returnCallback( function ( $key ) {
				return new RawMessage( "($key)" );
			} ) );

		return $mock;
	}

	/**
	 * @return ClaimDiffer
	 */
	private function getMockClaimDiffer() {
		$mock = $this->getMockBuilder( ClaimDiffer::class )
			->disableOriginalConstructor()
			->getMock();
		return $mock;
	}

	/**
	 * @return ClaimDifferenceVisualizer
	 */
	private function getMockClaimDiffVisualizer() {
		$mock = $this->getMockBuilder( ClaimDifferenceVisualizer::class )
			->disableOriginalConstructor()
			->getMock();
		return $mock;
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
			$this->getMockClaimDiffVisualizer(),
			new HashSiteStore( [ $enwiki ] ),
			$this->getMock( EntityIdFormatter::class )
		);

		return new ItemDiffVisualizer(
			$this->getMockMessageLocalizer(),
			$this->getMockClaimDiffer(),
			$this->getMockClaimDiffVisualizer(),
			new HashSiteStore( [ $enwiki ] ),
			$this->getMock( EntityIdFormatter::class ),
			$basicVisualizer
		);
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGenerateEntityContentDiffBody( EntityContentDiff $diff, array $matchers ) {
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $diff );

		$this->assertInternalType( 'string', $html );
		foreach ( $matchers as $name => $matcher ) {
			$this->assertContains( $matcher, $html, $name );
		}
	}

}
