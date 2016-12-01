<?php

namespace Wikibase\Lib\Tests\Store;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\DispatchingPropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\PropertyInfoStore;

/**
 * @covers Wikibase\Lib\Store\DispatchingPropertyInfoLookup
 *
 * @group WikibaseLib
 * @group WikibaseStore
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class DispatchingPropertyInfoLookupTest extends \PHPUnit_Framework_TestCase {

	private $localPropertyInfo;
	private $fooPropertyInfo;

	public function __construct( $name = null, $data = [], $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->localPropertyInfo = [
			'P23' => [ PropertyInfoStore::KEY_DATA_TYPE => 'string' ],
			'P42' => [ PropertyInfoStore::KEY_DATA_TYPE => 'commonsMedia', 'foo' => 'bar' ]
		];
		$this->fooPropertyInfo = [
			'foo:P123' => [ PropertyInfoStore::KEY_DATA_TYPE => 'string', 'foo' => 'bar' ],
			'foo:P42' => [ PropertyInfoStore::KEY_DATA_TYPE => 'commonsMedia' ]
		];
	}

	public function testGivenDifferentPropertyIds_getPropertyInfoDispatchesAccordingToRepository() {
		$lookup = new DispatchingPropertyInfoLookup( [
			'' => $this->getPropertyInfoLookup( $this->localPropertyInfo ),
			'foo' => $this->getPropertyInfoLookup( $this->fooPropertyInfo ),
		] );

		$this->assertSame(
			$this->localPropertyInfo['P23'],
			$lookup->getPropertyInfo( new PropertyId( 'P23' ) )
		);
		$this->assertSame(
			$this->fooPropertyInfo['foo:P42'],
			$lookup->getPropertyInfo( new PropertyId( 'foo:P42' ) )
		);
	}

	public function testGivenMultiplePropertyInfoLookups_getAllPropertyInfoCombinesResults() {
		$lookup = new DispatchingPropertyInfoLookup( [
			'' => $this->getPropertyInfoLookup( $this->localPropertyInfo ),
			'foo' => $this->getPropertyInfoLookup( $this->fooPropertyInfo ),
		] );

		$this->assertSame(
			$lookup->getAllPropertyInfo(),
			array_merge( $this->localPropertyInfo, $this->fooPropertyInfo )
		);
	}

	public function testGivenMultiplePropertyInfoLookups_getPropertyInfoForDataTypeCombinesResults() {
		$lookup = new DispatchingPropertyInfoLookup( [
			'' => $this->getPropertyInfoLookup( $this->localPropertyInfo ),
			'foo' => $this->getPropertyInfoLookup( $this->fooPropertyInfo ),
		] );

		$this->assertSame(
			[ 'P23' => $this->localPropertyInfo['P23'], 'foo:P123' => $this->fooPropertyInfo['foo:P123'] ],
			$lookup->getPropertyInfoForDataType( 'string' )
		);
		$this->assertSame(
			[ 'P42' => $this->localPropertyInfo['P42'], 'foo:P42' => $this->fooPropertyInfo['foo:P42'] ],
			$lookup->getPropertyInfoForDataType( 'commonsMedia' )
		);
	}

	private function getPropertyInfoLookup( $info ) {
		$lookup = $this->getMock( PropertyInfoLookup::class );

		$lookup->method( 'getPropertyInfo' )
			->willReturnCallback( function( PropertyId $id ) use ( $info ) {
				return $info[$id->getSerialization()];
			} );

		$lookup->method( 'getAllPropertyInfo' )
			->willReturnCallback( function() use ( $info ) {
				return $info;
			} );

		$lookup->method( 'getPropertyInfoForDataType' )
			->willReturnCallback( function( $dataType ) use ( $info ) {
				return array_filter( $info, function( array $propertyInfo ) use ( $dataType ) {
					return $propertyInfo[PropertyInfoStore::KEY_DATA_TYPE] === $dataType;
				} );
			} );

		return $lookup;
	}

	/**
	 * @dataProvider provideInvalidForeignLookups
	 */
	public function testGivenInvalidPropertyInfoLookup_exceptionIsThrown( $lookups ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new DispatchingPropertyInfoLookup( $lookups );
	}

	public function provideInvalidForeignLookups() {
		return array(
			'no lookups given' => array( array() ),
			'not an implementation of PropertyInfoLookup given as a lookup' => array(
				array( '' => new ItemId( 'Q123' ) ),
			),
			'non-string keys' => array(
				array(
					'' => $this->getMock( PropertyInfoLookup::class ),
					100 => $this->getMock( PropertyInfoLookup::class ),
				),
			),
			'repo name containing colon' => array(
				array(
					'' => $this->getMock( PropertyInfoLookup::class ),
					'fo:oo' => $this->getMock( PropertyInfoLookup::class ),
				),
			),
		);
	}

}
