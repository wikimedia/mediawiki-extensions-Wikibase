<?php

namespace Wikibase\Repo\Tests\Rdf;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Rdf\DispatchingEntityRdfBuilder;
use Wikibase\Rdf\EntityRdfBuilder;

/**
 * @covers Wikibase\Rdf\DispatchingEntityRdfBuilder
 *
 * @group Wikibase
 * @group WikibaseRdf
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class DispatchingEntityRdfBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testAddValue() {
		$entity = new Item( new ItemId( 'Q1' ) );

		$fooBuilder = $this->getMock( EntityRdfBuilder::class );
		$fooBuilder->expects( $this->once() )
			->method( 'addEntity' )
			->with( $entity );

		$dispatchingBuilder = new DispatchingEntityRdfBuilder( [
			'item' => [ $fooBuilder ],
		] );

		$dispatchingBuilder->addEntity( $entity );
	}

}
