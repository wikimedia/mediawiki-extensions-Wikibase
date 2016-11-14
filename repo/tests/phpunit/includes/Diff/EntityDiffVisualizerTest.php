<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use HashSiteStore;
use IContextSource;
use Language;
use MediaWikiTestCase;
use Site;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizer;

/**
 * @covers Wikibase\Repo\Diff\EntityDiffVisualizer
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityDiffVisualizerTest extends MediaWikiTestCase {

	public function diffProvider() {
		$emptyDiff = new EntityContentDiff( new EntityDiff(), new Diff() );

		$fingerprintDiff = new EntityContentDiff(
			new EntityDiff( [
				'label' => new Diff( [
					'en' => new DiffOpAdd( 'O_o' ),
				], true ),

				'description' => new Diff( [
					'en' => new DiffOpRemove( 'ohi there' ),
				], true ),

				'aliases' => new Diff( [
					'nl' => new Diff( [
							new DiffOpAdd( 'daaaah' ),
							new DiffOpRemove( 'foo' ),
							new DiffOpRemove( 'bar' ),
						] )
				], true ),
			] ),
			new Diff()
		);

		$fingerprintTags = [
			'has <td>label / en</td>' => [ 'tag' => 'td', 'content' => 'label / en' ],
			'has <ins>O_o</ins>' => [ 'tag' => 'ins', 'content' => 'O_o' ],
			'has <td>aliases / nl / 0</td>' => [ 'tag' => 'td', 'content' => 'aliases / nl / 0' ],
			'has <ins>daaaah</ins>' => [ 'tag' => 'ins', 'content' => 'daaaah' ],
			'has <td>aliases / nl / 1</td>' => [ 'tag' => 'td', 'content' => 'aliases / nl / 1' ],
			'has <del>foo</del>' => [ 'tag' => 'del', 'content' => 'foo' ],
			'has <td>aliases / nl / 2</td>' => [ 'tag' => 'td', 'content' => 'aliases / nl / 2' ],
			'has <del>bar</del>' => [ 'tag' => 'del', 'content' => 'bar' ],
			'has <td>description / en</td>' => [ 'tag' => 'td', 'content' => 'description / en' ],
			'has <del>ohi there</del>' => [ 'tag' => 'del', 'content' => 'ohi there' ],
		];

		$redirectDiff = new EntityContentDiff( new EntityDiff(), new Diff( [
			'redirect' => new DiffOpAdd( 'Q1234' )
		], true ) );

		$redirectTags = [
			'has <td>redirect</td>' => [ 'tag' => 'td', 'content' => 'redirect' ],
			'has <ins>Q1234</ins>' => [ 'tag' => 'ins', 'content' => 'Q1234' ],
		];

		return [
			'empty' => [ $emptyDiff, [ 'empty' => '/^$/', ] ],
			'fingerprint changed' => [ $fingerprintDiff, $fingerprintTags ],
			'redirect changed' => [ $redirectDiff, $redirectTags ],
		];
	}

	/**
	 * @return IContextSource
	 */
	protected function getMockContext() {
		$en = Language::factory( 'en' );

		$mock = $this->getMock( IContextSource::class );
		$mock->expects( $this->any() )
			->method( 'getLanguage' )
			->will( $this->returnValue( $en ) );

		return $mock;
	}

	/**
	 * @return ClaimDiffer
	 */
	protected function getMockClaimDiffer() {
		$mock = $this->getMockBuilder( ClaimDiffer::class )
			->disableOriginalConstructor()
			->getMock();
		return $mock;
	}

	/**
	 * @return ClaimDifferenceVisualizer
	 */
	protected function getMockClaimDiffVisualizer() {
		$mock = $this->getMockBuilder( ClaimDifferenceVisualizer::class )
			->disableOriginalConstructor()
			->getMock();
		return $mock;
	}

	/**
	 * @return EntityDiffVisualizer
	 */
	protected function getVisualizer() {
		$enwiki = new Site();
		$enwiki->setGlobalId( 'enwiki' );

		return new EntityDiffVisualizer(
			$this->getMockContext(),
			$this->getMockClaimDiffer(),
			$this->getMockClaimDiffVisualizer(),
			new HashSiteStore( [ $enwiki ] ),
			$this->getMock( EntityIdFormatter::class )
		);
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGenerateEntityContentDiffBody( EntityContentDiff $diff, array $matchers ) {
		$visualizer = $this->getVisualizer();

		$html = $visualizer->visualizeEntityContentDiff( $diff );

		$this->assertInternalType( 'string', $html );

		foreach ( $matchers as $name => $matcher ) {
			if ( is_string( $matcher ) ) {
				$this->assertRegExp( $matcher, $html );
			} else {
				$this->assertTag( $matcher, $html, $name );
			}
		}
	}

}
