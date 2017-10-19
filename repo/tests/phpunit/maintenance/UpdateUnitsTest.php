<?php

namespace Wikibase\Test;

use MediaWikiLangTestCase;
use Wikibase\UpdateUnits;

/**
 * @covers Wikibase\UpdateUnits
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Stas Malyshev
 */
class UpdateUnitsTest extends MediaWikiLangTestCase {

	/**
	 * @var UpdateUnits
	 */
	private $script;

	public function setUp() {
		parent::setUp();
		$this->script = new UpdateUnits();
		$this->script->setBaseUri( 'http://acme.test/' );
		$this->script->silent = true;
	}

	public function getUnitCases() {
		return [
			'derived SI unit' => [
				[
					'unit' => 'http://acme.test/Q2',
					'si' => '123.45',
					'siUnit' => 'http://acme.test/Q1',
					'unitLabel' => 'test unit Q2',
					'siUnitLabel' => 'test unit Q1',
				],
				[
					'factor' => '123.45',
					'unit' => 'Q1',
					'label' => 'test unit Q2',
					'siLabel' => 'test unit Q1',
				]
			],
			'unknown base unit' => [
				[
					'unit' => 'http://acme.test/Q2',
					'si' => '123.45',
					'siUnit' => 'http://acme.test/Q5',
					'unitLabel' => 'test unit Q2',
					'siUnitLabel' => 'test unit Q5',
				],
				null
			],
			'already done' => [
				[
					'unit' => 'http://acme.test/Q10',
					'si' => '123.45',
					'siUnit' => 'http://acme.test/Q5',
					'unitLabel' => 'test 10',
					'siUnitLabel' => 'test unit Q5',
				],
				null
			],
			'weird base unit' => [
				[
					'unit' => 'http://acme.test/Q1',
					'si' => '123.45',
					'siUnit' => 'http://acme.test/Q1',
					'unitLabel' => 'test 1',
					'siUnitLabel' => 'test unit Q1',
				],
				null
			],
			'weird non-base unit' => [
				[
					'unit' => 'http://acme.test/Q2',
					'si' => '1',
					'siUnit' => 'http://acme.test/Q2',
					'unitLabel' => 'test 2',
					'siUnitLabel' => 'test unit Q2',
				],
				null
			],
			'low usage unit' => [
				[
					'unit' => 'http://acme.test/Q4',
					'si' => '123.45',
					'siUnit' => 'http://acme.test/Q1',
					'unitLabel' => 'test 4',
					'siUnitLabel' => 'test unit Q1',
				],
				null
			],
			'reconvertable' => [
				[
					'unit' => 'http://acme.test/Q3',
					'si' => '123.45',
					'siUnit' => 'http://acme.test/Q2',
					'unitLabel' => 'test',
					'siUnitLabel' => 'test unit Q2',
				],
				null
			],
		];
	}

	/**
	 * @dataProvider getUnitCases
	 */
	public function testConvertUnit( array $unit, array $expect = null ) {
		$usage = [ 'Q1' => 100, 'Q2' => 50, 'Q3' => 10 ];
		$base = [ 'Q1' => true ];
		$converted = [ 'Q10' => [] ];

		$reconvert = [];
		$converted = $this->script->convertUnit( $unit, $converted, $base, $usage, $reconvert );
		$this->assertEquals( $expect, $converted );
	}

	public function testConvertDerivedUnit() {
		$unit = [
			'unit' => 'http://acme.test/Q3',
			'si' => '67.89',
			'siUnit' => 'http://acme.test/Q2',
			'unitLabel' => 'test unit Q3',
			'siUnitLabel' => 'test unit Q2',
		];
		$usage = [ 'Q1' => 100, 'Q2' => 50, 'Q3' => 10 ];
		$base = [ 'Q1' => true ];
		$converted = [
			'Q2' => [
				'factor' => '123.45',
				'unit' => 'Q1',
				'label' => 'test unit Q2',
				'siLabel' => 'test unit Q1',
			]
		];
		$expected = [
			'factor' => '8381.0205',
			'unit' => 'Q1',
			'label' => 'test unit Q3',
			'siLabel' => 'test unit Q1',
		];

		$reconvert = [];
		$convertedUnit = $this->script->convertUnit( $unit, $converted, $base, $usage, $reconvert );
		$this->assertNull( $convertedUnit );
		$reconverted = reset( $reconvert );

		$convertedUnit = $this->script->convertDerivedUnit( $reconverted, $converted );
		$this->assertEquals( $expected, $convertedUnit );
	}

}
