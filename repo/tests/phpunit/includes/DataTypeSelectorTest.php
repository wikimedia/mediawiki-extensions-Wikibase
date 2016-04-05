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
			array( [], null ),
			array( [], false ),
			array( array( null ), '' ),
			array( array( false ), '' ),
			array( array( '' ), '' ),
		);
	}

	/**
	 * @dataProvider getHtmlProvider
	 */
	public function testGetHtml( array $dataTypes, $selectedTypeId, $expected ) {
		$selector = $this->newInstance( $dataTypes );
		$html = $selector->getHtml( '<ID>', '<NAME>', $selectedTypeId );
		$this->assertSame( $expected, $html );
	}

	public function getHtmlProvider() {
		return array(
			array(
				[],
				'',
				'<select name="&lt;NAME&gt;" id="&lt;ID&gt;" class="wb-select">'
				. '</select>'
			),
			array(
				array( $this->newDataType( '<PT>' ) ),
				'',
				'<select name="&lt;NAME&gt;" id="&lt;ID&gt;" class="wb-select">'
				. '<option value="&lt;PT&gt;">(datatypes-type-&lt;PT>)</option>'
				. '</select>'
			),
			array(
				array( $this->newDataType( 'PT1' ), $this->newDataType( 'PT2' ) ),
				'PT2',
				'<select name="&lt;NAME&gt;" id="&lt;ID&gt;" class="wb-select">'
				. '<option value="PT1">(datatypes-type-PT1)</option>'
				. '<option value="PT2" selected="">(datatypes-type-PT2)</option>'
				. '</select>'
			),
		);
	}

	public function testGetOptionsArray() {
		$selector = $this->newInstance();
		$options = $selector->getOptionsArray();
		$this->assertSame( array( '<PT>' => '(datatypes-type-<PT>)' ), $options );
	}

	/**
	 * @dataProvider getOptionsHtmlProvider
	 */
	public function testGetOptionsHtml( $selectedTypeId, $expected ) {
		$selector = $this->newInstance();
		$html = $selector->getOptionsHtml( $selectedTypeId );
		$this->assertSame( $expected, $html );
	}

	public function getOptionsHtmlProvider() {
		return array(
			array(
				'',
				'<option value="&lt;PT&gt;">(datatypes-type-&lt;PT>)</option>'
			),
			array(
				'<PT>',
				'<option value="&lt;PT&gt;" selected="">(datatypes-type-&lt;PT>)</option>'
			),
		);
	}

}
