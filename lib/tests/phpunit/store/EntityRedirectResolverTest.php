<?php

namespace Wikibase\Lib\Test\Store;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityId;
use Wikibase\Lib\Store\EntityRedirectResolver;
use Wikibase\Lib\Store\UnresolvedRedirectException;

/**
 * @covers Wikibase\Lib\Store\EntityRedirectResolver
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityRedirectResolverTest extends \PHPUnit_Framework_TestCase {

	public function testApply() {
		$resolver = new EntityRedirectResolver( function ( EntityId $id ) {
			if ( $id->getSerialization() === 'Q1' ) {
				throw new UnresolvedRedirectException( new ItemId( 'Q10' ) );
			}

			if ( $id->getSerialization() === 'Q10' ) {
				throw new UnresolvedRedirectException( new ItemId( 'Q100' ) );
			}

			return $id->getSerialization();
		} );

		$this->assertEquals( $resolver->apply( new ItemId( 'Q9' ) ), 'Q9', 'no redirect' );
		$this->assertEquals( $resolver->apply( new ItemId( 'Q10' ) ), 'Q100', 'one redirect' );

		$this->setExpectedException( 'Wikibase\Lib\Store\UnresolvedRedirectException' );
		$resolver->apply( new ItemId( 'Q1' ) );
	}

}
