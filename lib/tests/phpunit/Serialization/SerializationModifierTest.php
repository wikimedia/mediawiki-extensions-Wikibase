<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Serialization;

use Wikibase\Lib\Serialization\SerializationModifier;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Lib\Serialization\SerializationModifier
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class SerializationModifierTest extends \PHPUnit\Framework\TestCase {

	private function newSerializationModifier(): SerializationModifier {
		return new SerializationModifier();
	}

	public function providePathAndCallback(): iterable {
		return [
			[
				'',
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
					],
				],
				[
					'foo' => [
						[ 'removed' => true, 'new' => 'new' ],
						[ 'b' => 'b', 'new' => 'new' ],
					],
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
	 * @dataProvider providePathAndCallback
	 */
	public function testSingleCallback(
		string $path,
		callable $callback,
		array $array,
		array $expectedArray
	): void {
		$injector = $this->newSerializationModifier();
		$alteredArray = $injector->modifyUsingCallbacks( $array, [ $path => $callback ] );
		$this->assertEquals( $expectedArray, $alteredArray );
	}

	public function provideCallbacks(): iterable {
		$typeEntityCallback = function ( $value ) {
			$value['type'] = 'entity';
			return $value;
		};
		$typeLabelCallback = function ( $value ) {
			$value['type'] = 'label';
			return $value;
		};

		yield 'add types in multiple places' => [
			'callbacks' => [
				'' => $typeEntityCallback,
				'labels/*' => $typeLabelCallback,
			],
			'array' => [
				'labels' => [
					'en' => [ 'language' => 'en', 'value' => 'label' ],
					'de' => [ 'language' => 'de', 'value' => 'Label' ],
				],
			],
			'alteredArray' => [
				'type' => 'entity',
				'labels' => [
					'en' => [ 'language' => 'en', 'value' => 'label', 'type' => 'label' ],
					'de' => [ 'language' => 'de', 'value' => 'Label', 'type' => 'label' ],
				],
			],
		];

		yield 'add types in overlapping places, more specific path first' => [
			[
				'*/*/labels/*' => $typeLabelCallback,
				'' => $typeEntityCallback,
			],
			'array' => [
				'subentities' => [
					[
						'labels' => [
							'en' => [ 'language' => 'en', 'value' => 'label' ],
						],
					],
				],
			],
			'alteredArray' => [
				'type' => 'entity',
				'subentities' => [
					[
						'labels' => [
							'en' => [ 'language' => 'en', 'value' => 'label', 'type' => 'label' ],
						],
					],
				],
			],
		];

		yield 'add types in overlapping places, less specific path first' => [
			[
				'' => $typeEntityCallback,
				'*/*/labels/*' => $typeLabelCallback,
			],
			'array' => [
				'subentities' => [
					[
						'labels' => [
							'en' => [ 'language' => 'en', 'value' => 'label' ],
						],
					],
				],
			],
			'alteredArray' => [
				'type' => 'entity',
				'subentities' => [
					[
						'labels' => [
							'en' => [ 'language' => 'en', 'value' => 'label', 'type' => 'label' ],
						],
					],
				],
			],
		];
	}

	/** @dataProvider provideCallbacks */
	public function testCallbacks(
		array $callbacks,
		array $array,
		array $expectedArray
	): void {
		$injector = $this->newSerializationModifier();
		$alteredArray = $injector->modifyUsingCallbacks( $array, $callbacks );
		$this->assertEquals( $expectedArray, $alteredArray );
	}

	public function testUnflattenPaths() {
		$array = [
			'' => 'cb0',
			'claims' => 'cb1',
			'claims/foo' => 'cb2',
			'claims/*/bar' => 'cb3',
			'label' => 'cb4',
		];
		$expected = [
			'' => 'cb0',
			'claims' => [
				'' => 'cb1',
				'foo' => [ '' => 'cb2' ],
				'*' => [
					'bar' => [ '' => 'cb3' ],
				],
			],
			'label' => [ '' => 'cb4' ],
		];

		$actual = TestingAccessWrapper::newFromObject( $this->newSerializationModifier() )
			->unflattenPaths( $array );

		$this->assertSame( $expected, $actual );
	}

}
