<?php

namespace Wikibase\Test\Repo\Api;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Api\SerializationModifier;

/**
 * @author Adam Shorland
 *
 * @covers Wikibase\Repo\Api\SerializationModifier
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
				array(),
				null,
				function( $value ) {
					$value['foo'] = 'bar';
					return $value;
				},
				array( 'foo' => 'bar' ),
			),
			array(
				array(
					'foo' => array(
						array( 'a' => 'a' ),
						array( 'b' => 'b' ),
					)
				),
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
						array( 'removed' => true, 'new' => 'new' ),
						array( 'b' => 'b', 'new' => 'new' ),
					)
				),
			),
			array(
				array(
					'foo' => array(
						array( 'a' => 'a' ),
						array( 'b' => 'b' ),
						array( 'c' => array(
							'c1',
							'c2',
							'cInner' => array(
								'innerC1',
								'innerC2',
							)
						) ),
					),
				),
				'foo/*/c/*',
				function( $value ) {
					$value[] = 'innerC3-added';
					return $value;
				},
				array(
					'foo' => array(
						array( 'a' => 'a' ),
						array( 'b' => 'b' ),
						array( 'c' => array(
							'c1',
							'c2',
							'cInner' => array(
								'innerC1',
								'innerC2',
								'innerC3-added',
							)
						) ),
					),
				),
			),
		);
	}

	/**
	 * @dataProvider provideKeyValueInjection
	 */
	public function testInjectKeyValue( $array, $path, $callback, $expectedArray ) {
		$injector = $this->newSerializationModifier();
		$alteredArray = $injector->modifyUsingCallback( $array, $path, $callback );
		$this->assertEquals( $expectedArray, $alteredArray );
	}

}
