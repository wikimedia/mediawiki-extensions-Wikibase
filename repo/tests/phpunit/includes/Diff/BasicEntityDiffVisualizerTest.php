<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use HashSiteStore;
use IContextSource;
use MediaWikiTestCase;
use RawMessage;
use Site;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;

/**
 * @covers Wikibase\Repo\Diff\BasicEntityDiffVisualizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class BasicEntityDiffVisualizerTest extends MediaWikiTestCase {

	public function testVisualizingEmptyDiff() {
		$emptyDiff = new EntityContentDiff( new EntityDiff(), new Diff(), 'item' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $emptyDiff );

		$this->assertSame( '', $html );
	}

	public function diffProvider() {
		$fingerprintDiff = new EntityContentDiff(
			new EntityDiff( array(
				'label' => new Diff( array(
					'en' => new DiffOpAdd( 'O_o' ),
				), true ),

				'description' => new Diff( array(
					'en' => new DiffOpRemove( 'ohi there' ),
				), true ),

				'aliases' => new Diff( array(
					'nl' => new Diff( array(
						new DiffOpAdd( 'daaaah' ),
						new DiffOpRemove( 'foo' ),
						new DiffOpRemove( 'bar' ),
					) )
				), true ),
			) ),
			new Diff(),
			'item'
		);

		$fingerprintTags = array(
			'has <td>label / en</td>' => '>(wikibase-diffview-label) / en</td>',
			'has <ins>O_o</ins>' => '>O_o</ins>',
			'has <td>aliases / nl / 0</td>' => '>(wikibase-diffview-alias) / nl / 0</td>',
			'has <ins>daaaah</ins>' => '>daaaah</ins>',
			'has <td>aliases / nl / 1</td>' => '>(wikibase-diffview-alias) / nl / 1</td>',
			'has <del>foo</del>' => '>foo</del>',
			'has <td>aliases / nl / 2</td>' => '>(wikibase-diffview-alias) / nl / 2</td>',
			'has <del>bar</del>' => '>bar</del>',
			'has <td>description / en</td>' => '>(wikibase-diffview-description) / en</td>',
			'has <del>ohi there</del>' => '>ohi there</del>',
		);

		$redirectDiff = new EntityContentDiff(
			new EntityDiff(),
			new Diff( [ 'redirect' => new DiffOpAdd( 'Q1234' ) ], true ),
			'item'
		);

		$redirectTags = array(
			'has <td>redirect</td>' => '>redirect</td>',
			'has <ins>Q1234</ins>' => '>Q1234</ins>',
		);

		return array(
			'fingerprint changed' => array( $fingerprintDiff, $fingerprintTags ),
			'redirect changed' => array( $redirectDiff, $redirectTags ),
		);
	}

	/**
	 * @return IContextSource
	 */
	private function getMockContext() {
		$mock = $this->getMock( IContextSource::class );

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
	 * @return BasicEntityDiffVisualizer
	 */
	private function getVisualizer() {
		$enwiki = new Site();
		$enwiki->setGlobalId( 'enwiki' );

		return new BasicEntityDiffVisualizer(
			$this->getMockContext(),
			$this->getMockClaimDiffer(),
			$this->getMockClaimDiffVisualizer(),
			new HashSiteStore( array( $enwiki ) ),
			$this->getMock( EntityIdFormatter::class )
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
