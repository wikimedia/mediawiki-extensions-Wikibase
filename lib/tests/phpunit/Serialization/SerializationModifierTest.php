<?php

namespace Wikibase\Test\Lib\Serialization;

use PHPUnit_Framework_TestCase;
use Wikibase\Lib\Serialization\SerializationModifier;

/**
 * @author Addshore
 *
 * @group Wikibase
 * @group WikibaseLib
 *
 * @covers Wikibase\Lib\Serialization\SerializationModifier
 */
class SerializationModifierTest extends PHPUnit_Framework_TestCase {

	/**
	 * @return SerializationModifier
	 */
	private function newSerializationModifier() {
		return new SerializationModifier();
	}

	public function provideKeyValueInjection() {
		return array(
			array(
				null,
				function( $value ) {
					$value['foo'] = 'bar';
					return $value;
				},
				array(),
				array( 'foo' => 'bar' ),
			),
			array(
				'foo/*',
				function( $value ) {
					if ( isset( $value['a'] ) ) {
						unset( $value['a'] );
						$value['removed'] = true;
					}
					$value['new'] = 'new';
					return $value;
				},
				array(
					'foo' => array(
						array( 'a' => 'a' ),
						array( 'b' => 'b' ),
					)
				),
				array(
					'foo' => array(
						array( 'removed' => true, 'new' => 'new' ),
						array( 'b' => 'b', 'new' => 'new' ),
					)
				),
			),
			array(
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
				array(
					'entities' => array(
						'Q1' => array(
							'id' => 'Q1',
							'claims' => array(
								'P52' => array(
									array(
										'references' => array(
											array(
												'snaks' => array(
													array( 'value' => 'val1', 'type' => 'foo' ),
													array( 'value' => 'val2', 'type' => 'foo' ),
												),
											),
										),
									),
									array(
										'references' => array(
											array(
												'snaks' => array(
													array( 'value' => 'val3', 'type' => 'bar' ),
													array( 'value' => 'val4', 'type' => 'bar' ),
												),
											),
										),
									),
								),
							),
						),
						'Q2' => array(
							'id' => 'Q2',
							'claims' => array(
								'P52' => array(
									array(
										'references' => array(
											array(
												'snaks' => array(
													array( 'value' => 'valA', 'type' => 'bar' ),
												),
											),
											array(
												'snaks' => array(
													array( 'value' => 'valB', 'type' => 'bar' ),
													array( 'value' => 'valC', 'type' => 'foo' ),
												),
											),
										),
									),
								),
							),
						),
					),
				),
				array(
					'entities' => array(
						'Q1' => array(
							'id' => 'Q1',
							'claims' => array(
								'P52' => array(
									array(
										'references' => array(
											array(
												'snaks' => array(
													array( 'value' => 'val1', 'type' => '-foo-' ),
													array( 'value' => 'val2', 'type' => '-foo-' ),
												),
											),
										),
									),
									array(
										'references' => array(
											array(
												'snaks' => array(
													array( 'value' => 'val3', 'type' => 'BAR' ),
													array( 'value' => 'val4', 'type' => 'BAR' ),
												),
											),
										),
									),
								),
							),
						),
						'Q2' => array(
							'id' => 'Q2',
							'claims' => array(
								'P52' => array(
									array(
										'references' => array(
											array(
												'snaks' => array(
													array( 'value' => 'valA', 'type' => 'BAR' ),
												),
											),
											array(
												'snaks' => array(
													array( 'value' => 'valB', 'type' => 'BAR' ),
													array( 'value' => 'valC', 'type' => '-foo-' ),
												),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		);
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
