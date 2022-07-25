<?php

namespace Wikibase\Repo\Tests;

use MWException;
use Wikibase\Lib\DataType;
use Wikibase\Repo\DataTypeSelector;

/**
 * @covers \Wikibase\Repo\DataTypeSelector
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class DataTypeSelectorTest extends \PHPUnit\Framework\TestCase {

	/** @see \LanguageQqx */
	private const DUMMY_LANGUAGE = 'qqx';

	/**
	 * @param string $propertyType
	 * @param string $messageKey
	 *
	 * @return DataType
	 */
	private function newDataType( $propertyType, $messageKey ) {
		$dataType = $this->createMock( DataType::class );

		$dataType->method( 'getId' )
			->willReturn( $propertyType );

		$dataType->method( 'getMessageKey' )
			->willReturn( $messageKey );

		return $dataType;
	}

	/**
	 * @dataProvider invalidConstructorArgumentsProvider
	 */
	public function testConstructorThrowsException( array $dataTypes, $languageCode ) {
		$this->expectException( MWException::class );
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
