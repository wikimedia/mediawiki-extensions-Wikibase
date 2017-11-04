<?php

namespace Wikibase\Lib\Tests\Serialization;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\Serialization\SerializationModifier;

/**
 * @covers \Wikibase\Lib\Serialization\SerializationModifier
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class SerializationModifierTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return SerializationModifier
	 */
	private function newSerializationModifier() {
		return new SerializationModifier();
	}

	public function provideKeyValueInjection() {
		return [
			[
				null,
				function( $value ) {
					$value['foo'] = 'bar';
					return $value;
				},
				[],
				[ 'foo' => 'bar' ],
			],
			[
				'foo/*',
				function( $value ) {
					if ( isset( $value['a'] ) ) {
						unset( $value['a'] );
						$value['removed'] = true;
					}
					$value['new'] = 'new';
					return $value;
				},
				[
					'foo' => [
						[ 'a' => 'a' ],
						[ 'b' => 'b' ],
					]
				],
				[
					'foo' => [
						[ 'removed' => true, 'new' => 'new' ],
						[ 'b' => 'b', 'new' => 'new' ],
					]
				],
			],
			[
				'entities/*/claims/*/*/references/*/snaks/*/type',
				function( $value ) {
					if ( $value === 'bar' ) {
						$value = 'BAR';
					}
					if ( $value === 'foo' ) {
						$value = '-' . $value . '-';
					}
					return $value;
				},
				[
					'entities' => [
						'Q1' => [
							'id' => 'Q1',
							'claims' => [
								'P52' => [
									[
										'references' => [
											[
												'snaks' => [
													[ 'value' => 'val1', 'type' => 'foo' ],
													[ 'value' => 'val2', 'type' => 'foo' ],
												],
											],
										],
									],
									[
										'references' => [
											[
												'snaks' => [
													[ 'value' => 'val3', 'type' => 'bar' ],
													[ 'value' => 'val4', 'type' => 'bar' ],
												],
											],
										],
									],
								],
							],
						],
						'Q2' => [
							'id' => 'Q2',
							'claims' => [
								'P52' => [
									[
										'references' => [
											[
												'snaks' => [
													[ 'value' => 'valA', 'type' => 'bar' ],
												],
											],
											[
												'snaks' => [
													[ 'value' => 'valB', 'type' => 'bar' ],
													[ 'value' => 'valC', 'type' => 'foo' ],
												],
											],
										],
									],
								],
							],
						],
					],
				],
				[
					'entities' => [
						'Q1' => [
							'id' => 'Q1',
							'claims' => [
								'P52' => [
									[
										'references' => [
											[
												'snaks' => [
													[ 'value' => 'val1', 'type' => '-foo-' ],
													[ 'value' => 'val2', 'type' => '-foo-' ],
												],
											],
										],
									],
									[
										'references' => [
											[
												'snaks' => [
													[ 'value' => 'val3', 'type' => 'BAR' ],
													[ 'value' => 'val4', 'type' => 'BAR' ],
												],
											],
										],
									],
								],
							],
						],
						'Q2' => [
							'id' => 'Q2',
							'claims' => [
								'P52' => [
									[
										'references' => [
											[
												'snaks' => [
													[ 'value' => 'valA', 'type' => 'BAR' ],
												],
											],
											[
												'snaks' => [
													[ 'value' => 'valB', 'type' => 'BAR' ],
													[ 'value' => 'valC', 'type' => '-foo-' ],
												],
											],
										],
									],
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * @dataProvider provideKeyValueInjection
	 */
	public function testInjectKeyValue( $path, $callback, $array, $expectedArray ) {
		$injector = $this->newSerializationModifier();
		$alteredArray = $injector->modifyUsingCallback( $array, $path, $callback );
		$this->assertEquals( $expectedArray, $alteredArray );
	}

}
