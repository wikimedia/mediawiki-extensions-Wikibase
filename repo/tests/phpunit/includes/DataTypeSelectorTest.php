<?php

namespace Wikibase\Repo\Tests;

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
	 * @param string $label
	 *
	 * @return DataType
	 */
	private function newDataType( $propertyType, $label ) {
		$dataType = $this->getMockBuilder( DataType::class )
			->disableOriginalConstructor()
			->getMock();

		$dataType->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( $propertyType ) );

		$dataType->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( $label ) );

		return $dataType;
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testConstructorThrowsException( array $dataTypes, $languageCode ) {
		$this->setExpectedException( MWException::class );
		new DataTypeSelector( $dataTypes, $languageCode );
	}

	public function invalidConstructorArgumentsProvider() {
		return [
			[ [], null ],
			[ [], false ],
			[ [ null ], '' ],
			[ [ false ], '' ],
			[ [ '' ], '' ],
		];
	}

	/**
	 * @dataProvider getOptionsArrayProvider
	 */
	public function testGetOptionsArray( array $dataTypes, array $expected ) {
		$selector = new DataTypeSelector( $dataTypes, 'qqx' );
		$options = $selector->getOptionsArray();
		$this->assertSame( $expected, $options );
	}

	public function getOptionsArrayProvider() {
		return [
			'basic' => [
				[
					$this->newDataType( '<PT>', '<LABEL>' ),
				],
				[
					'<LABEL>' => '<PT>',
				]
			],
			'natcasesort' => [
				[
					$this->newDataType( '<PTA>', '<LABEL-10>' ),
					$this->newDataType( '<PTB>', '<label-2>' ),
				],
				[
					'<label-2>' => '<PTB>',
					'<LABEL-10>' => '<PTA>',
				]
			],
			'duplicate labels' => [
				[
					$this->newDataType( '<PTB>', '<LABEL>' ),
					$this->newDataType( '<PTA>', '<LABEL>' ),
				],
				[
					'<PTA>' => '<PTA>',
					'<PTB>' => '<PTB>',
				]
			],
		];
	}

}
