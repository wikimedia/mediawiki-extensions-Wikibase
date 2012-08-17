<?php

namespace Wikibase\Test;
use Wikibase\SnakList as SnakList;
use Wikibase\Snaks as Snaks;
use Wikibase\Snak as Snak;
use \Wikibase\PropertyValueSnak as PropertyValueSnak;
use \Wikibase\InstanceOfSnak as InstanceOfSnak;
use \DataValue\DataValueObject as DataValueObject;

/**
 * Tests for the Wikibase\SnakList class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseSnak
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SnakListTest extends \MediaWikiTestCase {

	public function constructorProvider() {
		return array(
			array(),
			array( array() ),
			array( array(
				new InstanceOfSnak( 42 )
			) ),
			array( array(
				new InstanceOfSnak( 42 ),
				new InstanceOfSnak( 9001 ),
			) ),
			array( array(
				new InstanceOfSnak( 42 ),
				new InstanceOfSnak( 9001 ),
				new PropertyValueSnak( 42, new DataValueObject() ),
			) ),
		);
	}

	public function instanceProvider() {
		return array_map(
			function( array $args ) {
				return new SnakList( array_key_exists( 0, $args ) ? $args[0] : null );
			},
			$this->constructorProvider()
		);
	}

	/**
	 * @dataProvider constructorProvider
	 *
	 * @param array|null $snaks
	 */
	public function testConstructor( array $snaks = null ) {
		$list = new SnakList( $snaks );

		$this->assertInstanceOf( '\Wikibase\Snaks', $list );

		$count = is_null( $snaks ) ? 0 : count( $snaks );

		$this->assertEquals( $count, count( $list ) );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\Snaks $snaks
	 */
	public function testHasSnak( Snaks $snaks ) {
		/**
		 * @var Snak $snak
		 */
		foreach ( $snaks as $snak ) {
			$this->assertTrue( $snaks->hasSnak( $snak ) );
			$this->assertTrue( $snaks->hasSnakHash( $snak->getHash() ) );
			$snaks->removeSnak( $snak );
			$this->assertFalse( $snaks->hasSnak( $snak ) );
			$this->assertFalse( $snaks->hasSnakHash( $snak->getHash() ) );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\Snaks $snaks
	 */
	public function testRemoveSnak( Snaks $snaks ) {
		$snakCount = count( $snaks );

		/**
		 * @var Snak $snak
		 */
		foreach ( $snaks as $snak ) {
			$this->assertTrue( $snaks->hasSnak( $snak ) );

			if ( $snakCount % 2 === 0 ) {
				$snaks->removeSnak( $snak );
			}
			else {
				$snaks->removeSnakHash( $snak->getHash() );
			}

			$this->assertFalse( $snaks->hasSnak( $snak ) );
			$this->assertEquals( --$snakCount, count( $snaks ) );
		}

		$snak = new InstanceOfSnak( 42 );

		$snaks->removeSnak( $snak );
		$snaks->removeSnakHash( $snak->getHash() );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\Snaks $snaks
	 */
	public function testAddSnak( Snaks $snaks ) {
		$snakCount = count( $snaks );

		$snak = new InstanceOfSnak( 60584238412764 );

		$this->assertTrue( $snaks->addSnak( $snak ) );

		$this->assertEquals( ++$snakCount, count( $snaks ) );

		$this->assertFalse( $snaks->addSnak( $snak ) );

		$this->assertEquals( ++$snakCount, count( $snaks ) );

		$this->assertTrue( $snaks->hasSnak( $snak ) );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\Snaks $snaks
	 */
	public function testGetHash( Snaks $snaks ) {
		$hash = $snaks->getHash();

		$this->assertEquals( $hash, $snaks->getHash() );

		$snaks->addSnak( new InstanceOfSnak( 60584238412764 ) );

		$this->assertTrue( $hash !== $snaks->getHash() );
	}

}
