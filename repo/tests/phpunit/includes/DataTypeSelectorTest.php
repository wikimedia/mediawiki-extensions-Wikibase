<?php

namespace Wikibase\Repo\Tests;

use PHPUnit4And6Compat;
use Wikibase\Lib\DataType;
use MWException;
use Wikibase\DataTypeSelector;

/**
 * @covers Wikibase\DataTypeSelector
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class DataTypeSelectorTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/** @see \LanguageQqx */
	const DUMMY_LANGUAGE = 'qqx';

	/**
	 * @param string $propertyType
	 * @param string $messageKey
	 *
	 * @return DataType
	 */
	private function newDataType( $propertyType, $messageKey ) {
		$dataType = $this->getMockBuilder( DataType::class )
			->disableOriginalConstructor()
			->getMock();

		$dataType->expects( $this->any() )
			->method( 'getId' )
			->will( $this->returnValue( $propertyType ) );

		$dataType->expects( $this->any() )
			->method( 'getMessageKey' )
			->will( $this->returnValue( $messageKey ) );

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

	public function testGetOptionsArrayWithOneElement() {
		$selector = new DataTypeSelector( [
			$this->newDataType( '<PROPERTY-TYPE>', '<LABEL>' ),
		], self::DUMMY_LANGUAGE );

		$expected = [
			'(<LABEL>)' => '<PROPERTY-TYPE>',
		];
		$this->assertSame( $expected, $selector->getOptionsArray() );
	}

	public function testGetOptionsArrayWithDuplicateLabels() {
		$selector = new DataTypeSelector( [
			$this->newDataType( '<PROPERTY-TYPE-B>', '<LABEL>' ),
			$this->newDataType( '<PROPERTY-TYPE-A>', '<LABEL>' ),
		], self::DUMMY_LANGUAGE );

		$expected = [
			'<PROPERTY-TYPE-A>' => '<PROPERTY-TYPE-A>',
			'<PROPERTY-TYPE-B>' => '<PROPERTY-TYPE-B>',
		];
		$this->assertSame( $expected, $selector->getOptionsArray() );
	}

	public function testGetOptionsArraySortsLabelsInNaturalOrder() {
		$selector = new DataTypeSelector( [
			$this->newDataType( '<PROPERTY-TYPE-A>', '<LABEL-10>' ),
			$this->newDataType( '<PROPERTY-TYPE-B>', '<label-2>' ),
		], self::DUMMY_LANGUAGE );

		$expected = [
			'(<label-2>)' => '<PROPERTY-TYPE-B>',
			'(<LABEL-10>)' => '<PROPERTY-TYPE-A>',
		];
		$this->assertSame( $expected, $selector->getOptionsArray() );
	}

}
