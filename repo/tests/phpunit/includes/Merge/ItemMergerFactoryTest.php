<?php

namespace Wikibase\Repo\Tests\Merge;

use SiteLookup;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\Merge\ItemMerger;
use Wikibase\Repo\Merge\ItemMergerFactory;
use Wikibase\Repo\Validators\EntityConstraintProvider;

/**
 * @covers \Wikibase\Repo\Merge\ItemMergerFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemMergerFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testNewItemMerger() {
		$factory = new ItemMergerFactory(
			$this->getMockBuilder( EntityConstraintProvider::class )
				->disableOriginalConstructor()
				->getMock(),
			$this->getMockBuilder( ChangeOpFactoryProvider::class )
				->disableOriginalConstructor()
				->getMock(),
			$this->getMockBuilder( SiteLookup::class )->getMock()
		);

		$this->assertInstanceOf( ItemMerger::class, $factory->newItemMerger( [] ) );
	}

}
