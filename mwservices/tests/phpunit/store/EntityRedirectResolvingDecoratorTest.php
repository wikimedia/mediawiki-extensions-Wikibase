<?php

namespace Wikibase\Lib\Test\Store;

use RuntimeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRedirectResolvingDecorator;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\UnresolvedRedirectException;
use Wikibase\PropertyLabelResolver;

/**
 * @covers Wikibase\Lib\Store\EntityRedirectResolver
 *
 * @group WikibaseLib
 * @group Wikibase
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

	public function getEntityRevision( EntityId $id ) {
		if ( $id->getSerialization() === 'Q1' ) {
			throw new UnresolvedRedirectException( new ItemId( 'Q5' ) );
		}

		if ( $id->getSerialization() === 'Q5' ) {
			throw new UnresolvedRedirectException( new ItemId( 'Q10' ) );
		}

		$item = Item::newEmpty();
		$item->setId( $id );

		return new EntityRevision( $item, 777 );
	}

	private function getEntityRevisionLookup() {
		$lookup = $this->getMock( 'Wikibase\Lib\Store\EntityRevisionLookup' );

		$lookup->expects( $this->any() )
			->method( 'getEntityRevision' )
			->will( $this->returnCallback( array( $this, 'getEntityRevision' ) ) );

		return $lookup;
	}

	public function redirectResolutionProvider() {
		// Redirects as per $this->getEntityRevision:
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

	/**
	 * @dataProvider redirectResolutionProvider
	 */
	public function testRedirectResolution( EntityId $id, $levels, EntityId $expected ) {
		$target = $this->getEntityRevisionLookup();

		/* @var EntityRevisionLookup $decorator */
		$decorator = new EntityRedirectResolvingDecorator( $target, $levels );
		$revision = $decorator->getEntityRevision( $id );

		$this->assertEquals( $expected, $revision->getEntity()->getId() );
	}


	public function redirectResolutionFailureProvider() {
		// Redirects as per $this->getEntityRevision:
		// Q1 -> Q5 -> Q10

		$q1 = new ItemId( 'Q1' );
		$q5 = new ItemId( 'Q5' );

		return array(
			'zero levels' => array( $q5, 0 ),
			'double redirect' => array( $q1, 1 ),
		);
	}

	/**
	 * @dataProvider redirectResolutionFailureProvider
	 */
	public function testRedirectResolutionFailure( EntityId $id, $levels ) {
		$target = $this->getEntityRevisionLookup();

		$this->setExpectedException( 'Wikibase\Lib\Store\UnresolvedRedirectException' );

		/* @var EntityRevisionLookup $decorator */
		$decorator = new EntityRedirectResolvingDecorator( $target, $levels );
		$decorator->getEntityRevision( $id );
	}

	public function testNoEntityId() {
		$target = $this->getMock( 'Wikibase\PropertyLabelResolver' );
		$target->expects( $this->once() )
			->method( 'getPropertyIdsForLabels' )
			->will( $this->throwException( new UnresolvedRedirectException( new ItemId( 'Q12' ) ) ) );

		$this->setExpectedException( 'Wikibase\Lib\Store\UnresolvedRedirectException' );

		/* @var PropertyLabelResolver $decorator */
		$decorator = new EntityRedirectResolvingDecorator( $target );
		$decorator->getPropertyIdsForLabels( array( 'foo' ) );
	}

	public function testError() {
		$target = $this->getMock( 'Wikibase\PropertyLabelResolver' );
		$target->expects( $this->once() )
			->method( 'getPropertyIdsForLabels' )
			->will( $this->throwException( new RuntimeException( 'Boo!' ) ) );

		$this->setExpectedException( 'RuntimeException' );

		/* @var PropertyLabelResolver $decorator */
		$decorator = new EntityRedirectResolvingDecorator( $target );
		$decorator->getPropertyIdsForLabels( array( 'foo' ) );
	}


}
