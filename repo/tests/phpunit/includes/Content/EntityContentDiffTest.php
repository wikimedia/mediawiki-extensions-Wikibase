<?php

namespace Wikibase\Repo\Tests\Content;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\Repo\Content\EntityContentDiff;

/**
 * @covers Wikibase\Repo\Content\EntityContentDiff
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityContentDiffTest extends \MediaWikiTestCase {

	public function provideConstruction() {
		return array(
			'empty' => array(
				new EntityDiff(),
				new Diff()
			),
			'entity diff' => array(
				new EntityDiff( array(
					'label' => new Diff( array(
						'en' => new DiffOpAdd( 'Spam' ),
					) )
				) ),
				new Diff()
			),
			'redirect diff' => array(
				new EntityDiff(),
				new Diff( array(
					'redirect' => new DiffOpAdd( 'Spam' ),
				) )
			),
			'entity and redirect diff' => array(
				new EntityDiff( array(
					'label' => new Diff( array(
							'en' => new DiffOpAdd( 'Spam' ),
						) )
				) ),
				new Diff( array(
					'redirect' => new DiffOpRemove( 'Spam' ),
				) )
			),
		);
	}

	/**
	 * @dataProvider provideConstruction
	 *
	 * @param EntityDiff $entityDiff
	 * @param Diff $redirectDiff
	 */
	public function testConstruction( EntityDiff $entityDiff, Diff $redirectDiff ) {
		$diff = new EntityContentDiff( $entityDiff, $redirectDiff, 'item' );

		$this->assertArrayEquals(
			$entityDiff->getOperations(),
			$diff->getEntityDiff()->getOperations()
		);
		$this->assertEmpty( array_diff(
			array_keys( $entityDiff->getOperations() ),
			array_keys( $diff->getEntityDiff()->getOperations() )
		) );

		$this->assertArrayEquals(
			$redirectDiff->getOperations(),
			$diff->getRedirectDiff()->getOperations()
		);
		$this->assertEmpty( array_diff(
			array_keys( $redirectDiff->getOperations() ),
			array_keys( $diff->getRedirectDiff()->getOperations() )
		) );
	}

}
