<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use IContextSource;
use Language;
use Site;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\Repo\Content\EntityContentDiff;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizer;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Diff\EntityDiffVisualizer
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityDiffVisualizerTest extends \MediaWikiTestCase {


	public function diffProvider() {
		$emptyDiff = new EntityContentDiff( new EntityDiff(), new Diff() );

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
				), true  ),
			) ),
			new Diff()
		);

		$fingerprintTags = array(
			'has <td>label / en</td>' => array( 'tag' => 'td', 'content' => 'label / en' ),
			'has <ins>O_o</ins>' => array( 'tag' => 'ins', 'content' => 'O_o' ),
			'has <td>aliases / nl / 0</td>' => array( 'tag' => 'td', 'content' => 'aliases / nl / 0' ),
			'has <ins>daaaah</ins>' => array( 'tag' => 'ins', 'content' => 'daaaah' ),
			'has <td>aliases / nl / 1</td>' => array( 'tag' => 'td', 'content' => 'aliases / nl / 1' ),
			'has <del>foo</del>' => array( 'tag' => 'del', 'content' => 'foo' ),
			'has <td>aliases / nl / 2</td>' => array( 'tag' => 'td', 'content' => 'aliases / nl / 2' ),
			'has <del>bar</del>' => array( 'tag' => 'del', 'content' => 'bar' ),
			'has <td>description / en</td>' => array( 'tag' => 'td', 'content' => 'description / en' ),
			'has <del>ohi there</del>' => array( 'tag' => 'del', 'content' => 'ohi there' ),
		);

		$redirectDiff = new EntityContentDiff( new EntityDiff(), new Diff( array(
			'redirect' => new DiffOpAdd( 'Q1234' )
		), true ) );

		$redirectTags = array(
			'has <td>redirect</td>' => array( 'tag' => 'td', 'content' => 'redirect' ),
			'has <ins>Q1234</ins>' => array( 'tag' => 'ins', 'content' => 'Q1234' ),
		);

		return array(
			'empty' => array( $emptyDiff, array( 'empty' => '/^$/', ) ),
			'fingerprint changed' => array( $fingerprintDiff, $fingerprintTags ),
			'redirect changed' => array( $redirectDiff, $redirectTags ),
		);
	}

	/**
	 * @return IContextSource
	 */
	protected function getMockContext() {
		$en = Language::factory( 'en' );

		$mock = $this->getMock( 'IContextSource' );
		$mock->expects( $this->any() )
			->method( 'getLanguage' )
			->will( $this->returnValue( $en ) );

		return $mock;
	}

	/**
	 * @return ClaimDiffer
	 */
	protected function getMockClaimDiffer() {
		$mock = $this->getMockBuilder( 'Wikibase\Repo\Diff\ClaimDiffer' )
			->disableOriginalConstructor()
			->getMock();
		return $mock;
	}

	/**
	 * @return ClaimDifferenceVisualizer
	 */
	protected function getMockClaimDiffVisualizer() {
		$mock = $this->getMockBuilder( 'Wikibase\Repo\Diff\ClaimDifferenceVisualizer' )
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
		$entityTitleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
		$entityRevisionLookup = new MockRepository();

		return new EntityDiffVisualizer(
			$this->getMockContext(),
			$this->getMockClaimDiffer(),
			$this->getMockClaimDiffVisualizer(),
			new MockSiteStore( array( $enwiki ) ),
			$entityTitleLookup,
			$entityRevisionLookup
		);
	}

	/**
	 * @dataProvider diffProvider
	 */
	public function testGenerateEntityContentDiffBody( EntityContentDiff $diff, $matchers ) {
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
