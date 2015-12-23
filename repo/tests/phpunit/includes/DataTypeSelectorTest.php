<?php

namespace Wikibase\Test;

use DataTypes\DataType;
use PHPUnit_Framework_TestCase;
use Wikibase\DataTypeSelector;

/**
 * @covers Wikibase\DataTypeSelector
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DataTypeSelectorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @param DataType[]|null $dataTypes
	 *
	 * @return DataTypeSelector
	 */
	private function newInstance( array $dataTypes = null ) {
		return new DataTypeSelector(
			$dataTypes !== null ? $dataTypes : array( new DataType( '<PT>', '<VT>' ) ),
			'qqx'
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
				array(),
				'',
				'<select name="&lt;NAME&gt;" id="&lt;ID&gt;" class="wb-select">'
				. '</select>'
			),
			array(
				array( new DataType( '<PT>', '<VT>' ) ),
				'',
				'<select name="&lt;NAME&gt;" id="&lt;ID&gt;" class="wb-select">'
				. '<option value="&lt;PT&gt;">(datatypes-type-&lt;PT>)</option>'
				. '</select>'
			),
			array(
				array( new DataType( 'PT1', 'VT1' ), new DataType( 'PT2', 'VT2' ) ),
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
	 * @dataProvider testGetOptionsHtmlProvider
	 */
	public function testGetOptionsHtml( $selectedTypeId, $expected ) {
		$selector = $this->newInstance();
		$html = $selector->getOptionsHtml( $selectedTypeId );
		$this->assertSame( $expected, $html );
	}

	public function testGetOptionsHtmlProvider() {
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
