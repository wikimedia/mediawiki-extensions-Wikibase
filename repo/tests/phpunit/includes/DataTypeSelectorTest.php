<?php

namespace Wikibase\Test;

use DataTypes\DataType;
use MWException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataTypeSelector;

/**
 * @covers Wikibase\DataTypeSelector
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class DataTypeSelectorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param string $propertyType
	 *
	 * @return DataType
	 */
	private function newDataType( $propertyType ) {
		$dataType = $this->getMockBuilder( DataType::class )
			->disableOriginalConstructor()
			->getMock();

		$dataType->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( $propertyType ) );

		$dataType->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( '(datatypes-type-' . $propertyType . ')' ) );

		return $dataType;
	}

	/**
	 * @param DataType[]|null $dataTypes
	 *
	 * @return DataTypeSelector
	 */
	private function newInstance( array $dataTypes = null ) {
		return new DataTypeSelector(
			$dataTypes !== null ? $dataTypes : array( $this->newDataType( '<PT>' ) ),
			'qqx'
		);
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testConstructorThrowsException( array $dataTypes, $languageCode ) {
		$this->setExpectedException( MWException::class );
		new DataTypeSelector( $dataTypes, $languageCode );
	}

	public function invalidConstructorArgumentsProvider() {
		return array(
			array( array(), null ),
			array( array(), false ),
			array( array( null ), '' ),
			array( array( false ), '' ),
			array( array( '' ), '' ),
		);
	}

	public function testGetOptionsArray() {
		$selector = $this->newInstance();
		$options = $selector->getOptionsArray();
		$this->assertSame( array( '<PT>' => '(datatypes-type-<PT>)' ), $options );
	}

}
