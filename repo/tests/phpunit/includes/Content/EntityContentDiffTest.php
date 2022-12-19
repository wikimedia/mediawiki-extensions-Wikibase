<?php

namespace Wikibase\Repo\Tests\Content;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpRemove;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\Repo\Content\EntityContentDiff;

/**
 * @covers \Wikibase\Repo\Content\EntityContentDiff
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityContentDiffTest extends MediaWikiIntegrationTestCase {

	public function provideConstruction() {
		return [
			'empty' => [
				new EntityDiff(),
				new Diff(),
			],
			'entity diff' => [
				new EntityDiff( [
					'label' => new Diff( [
						'en' => new DiffOpAdd( 'Spam' ),
					] ),
				] ),
				new Diff(),
			],
			'redirect diff' => [
				new EntityDiff(),
				new Diff( [
					'redirect' => new DiffOpAdd( 'Spam' ),
				] ),
			],
			'entity and redirect diff' => [
				new EntityDiff( [
					'label' => new Diff( [
							'en' => new DiffOpAdd( 'Spam' ),
						] ),
				] ),
				new Diff( [
					'redirect' => new DiffOpRemove( 'Spam' ),
				] ),
			],
		];
	}

	/**
	 * @dataProvider provideConstruction
	 */
	public function testConstruction( EntityDiff $entityDiff, Diff $redirectDiff ) {
		$diff = new EntityContentDiff( $entityDiff, $redirectDiff, Item::ENTITY_TYPE );

		$this->assertArrayEquals(
			$entityDiff->getOperations(),
			$diff->getEntityDiff()->getOperations()
		);
		$this->assertSame( [], array_diff(
			array_keys( $entityDiff->getOperations() ),
			array_keys( $diff->getEntityDiff()->getOperations() )
		) );

		$this->assertArrayEquals(
			$redirectDiff->getOperations(),
			$diff->getRedirectDiff()->getOperations()
		);
		$this->assertSame( [], array_diff(
			array_keys( $redirectDiff->getOperations() ),
			array_keys( $diff->getRedirectDiff()->getOperations() )
		) );
	}

}
