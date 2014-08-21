<?php

namespace Wikibase\Test;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\Repo\Content\EntityContentDiff;

/**
 * @covers Wikibase\Repo\Content\EntityContentDiff
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
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
		$diff = new EntityContentDiff( $entityDiff, $redirectDiff );

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
