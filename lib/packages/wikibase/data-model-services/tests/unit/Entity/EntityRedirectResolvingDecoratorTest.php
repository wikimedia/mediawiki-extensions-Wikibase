<?php

namespace Wikibase\DataModel\Services\Tests\Entity;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Entity\EntityRedirectResolvingDecorator;
use Wikibase\DataModel\Services\Entity\UnresolvedRedirectException;
use Wikibase\DataModel\Services\Fixtures\FakeEntityDocument;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;

/**
 * @covers Wikibase\DataModel\Services\Entity\EntityRedirectResolvingDecorator
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityRedirectResolvingDecoratorTest extends \PHPUnit_Framework_TestCase {

	public function constructionErrorProvider() {
		return array(
			array( '', 1 ),
			array( null, 1 ),
			array( new ItemId( 'Q7' ), -3 ),
			array( new ItemId( 'Q7' ), null ),
		);
	}

	/**
	 * @dataProvider constructionErrorProvider
	 */
	public function testConstructionError( $target, $levels ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		new EntityRedirectResolvingDecorator( $target, $levels );
	}

	public function testWhenDecoratedObjectThrowsException_exceptionBubblesUp() {
		$target = $this->getMock( 'Wikibase\DataModel\Services\Term\PropertyLabelResolver' );
		$target->expects( $this->once() )
			->method( 'getPropertyIdsForLabels' )
			->will( $this->throwException( new UnresolvedRedirectException( new ItemId( 'Q12' ) ) ) );

		$this->setExpectedException( 'Wikibase\DataModel\Services\Entity\UnresolvedRedirectException' );

		/* @var PropertyLabelResolver $decorator */
		$decorator = new EntityRedirectResolvingDecorator( $target );
		$decorator->getPropertyIdsForLabels( array( 'foo' ) );
	}

	/**
	 * @dataProvider redirectResolutionProvider
	 */
	public function testRedirectResolution( EntityId $id, $levels, EntityId $expected ) {
		$target = $this->getEntityRevisionLookup();

		/* @var EntityLookup $decorator */
		$decorator = new EntityRedirectResolvingDecorator( $target, $levels );
		$entity = $decorator->getEntity( $id );

		$this->assertEquals( $expected, $entity->getId() );
	}

	public function redirectResolutionProvider() {
		// Q1 -> Q5 -> Q10

		$q1 = new ItemId( 'Q1' );
		$q5 = new ItemId( 'Q5' );
		$q10 = new ItemId( 'Q10' );

		return array(
			'no redirect' => array( $q10, 1, $q10 ),
			'redirect resolved' => array( $q5, 1, $q10 ),
			'double redirect resolved' => array( $q1, 2, $q10 ),
		);
	}

	private function getEntityRevisionLookup() {
		$lookup = $this->getMock( 'Wikibase\DataModel\Services\Lookup\EntityLookup' );

		$lookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnCallback( function( EntityId $id ) {
					if ( $id->getSerialization() === 'Q1' ) {
						throw new UnresolvedRedirectException( new ItemId( 'Q5' ) );
					}

					if ( $id->getSerialization() === 'Q5' ) {
						throw new UnresolvedRedirectException( new ItemId( 'Q10' ) );
					}

					return new FakeEntityDocument( $id );
			} ) );

		return $lookup;
	}

}

