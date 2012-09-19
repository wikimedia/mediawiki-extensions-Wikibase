<?php

namespace Wikibase\Test;
use Wikibase\ClaimList as ClaimList;
use Wikibase\Claims as Claims;
use Wikibase\Claim as Claim;
use Wikibase\ClaimObject as ClaimObject;
use Wikibase\Hashable as Hashable;

/**
 * Tests for the Wikibase\ClaimList class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimListTest extends \MediaWikiTestCase {

	public function getInstanceClass() {
		return '\Wikibase\ClaimList';
	}

	public function instanceProvider() {
		$class = $this->getInstanceClass();

		$instances = array();

		foreach ( $this->constructorProvider() as $args ) {
			$instances[] = array( new $class( array_key_exists( 0, $args ) ? $args[0] : null ) );
		}

		return $instances;
	}

	public function getElementInstances() {
		$instances = array();

		$instances[] = new \Wikibase\ClaimObject( new \Wikibase\InstanceOfSnak( 42 ) );

		return $instances;
	}

	public function constructorProvider() {
		return array(
			array(),
			array( $this->getElementInstances() ),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\ClaimList $array
	 */
	public function testHasClaim( ClaimList $array ) {
		/**
		 * @var Hashable $hashable
		 */
		foreach ( iterator_to_array( $array ) as $hashable ) {
			$this->assertTrue( $array->hasClaim( $hashable ) );
			$array->removeClaim( $hashable );
			$this->assertFalse( $array->hasClaim( $hashable ) );
		}
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\ClaimList $array
	 */
	public function testRemoveClaim( ClaimList $array ) {
		$elementCount = count( $array );

		/**
		 * @var Hashable $element
		 */
		foreach ( iterator_to_array( $array ) as $element ) {
			$this->assertTrue( $array->hasClaim( $element ) );

			$array->removeClaim( $element );

			$this->assertFalse( $array->hasClaim( $element ) );
			$this->assertEquals( --$elementCount, count( $array ) );
		}

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );

		$array->removeClaim( $element );
		$array->removeClaim( $element );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\ClaimList $array
	 */
	public function testAddClaim( ClaimList $array ) {
		$elementCount = count( $array );

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );

		if ( !$array->hasClaim( $element ) ) {
			++$elementCount;
		}

		$array->addClaim( $element );

		$this->assertEquals( $elementCount, count( $array ) );
	}

}
