<?php

namespace Wikibase\Repo\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use MediaWikiIntegrationTestCase;
use MessageLocalizer;
use RawMessage;
use Site;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;

/**
 * @covers \Wikibase\Repo\Diff\BasicEntityDiffVisualizer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class BasicEntityDiffVisualizerTest extends MediaWikiIntegrationTestCase {

	public function testVisualizingEmptyDiff() {
		$emptyDiff = new EntityContentDiff( new EntityDiff(), new Diff(), 'item' );
		$html = $this->getVisualizer()->visualizeEntityContentDiff( $emptyDiff );

		$this->assertSame( '', $html );
	}

	public function diffProvider() {
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
					] ),
				], true ),
			] ),
			new Diff(),
			'item'
		);

		$fingerprintTags = [
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
		];

		$redirectDiff = new EntityContentDiff(
			new EntityDiff(),
			new Diff( [ 'redirect' => new DiffOpAdd( 'Q1234' ) ], true ),
			'item'
		);

		$redirectTags = [
			'has <td>redirect</td>' => '>redirect</td>',
			'has <ins>Q1234</ins>' => '>Q1234</ins>',
		];

		return [
			'fingerprint changed' => [ $fingerprintDiff, $fingerprintTags ],
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
	 * @return BasicEntityDiffVisualizer
	 */
	private function getVisualizer() {
		$enwiki = new Site();
		$enwiki->setGlobalId( 'enwiki' );

		return new BasicEntityDiffVisualizer(
			$this->getMockMessageLocalizer(),
			$this->createMock( ClaimDiffer::class ),
			$this->createMock( ClaimDifferenceVisualizer::class )
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
