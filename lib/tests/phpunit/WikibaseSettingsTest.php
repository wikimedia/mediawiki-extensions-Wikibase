<?php

namespace Wikibase\Lib\Tests;

use MWException;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\WikibaseSettings;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Lib\WikibaseSettings
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler <daniel.kinzler@wikimedia.de>
 */
class WikibaseSettingsTest extends \PHPUnit\Framework\TestCase {

	public function testGetRepoSettings() {
		if ( WikibaseSettings::isRepoEnabled() ) {
			$this->assertNotNull( WikibaseSettings::getRepoSettings() );
		} else {
			$this->expectException( MWException::class );
			WikibaseSettings::getRepoSettings();
		}
	}

	public function testGetClientSettings() {
		if ( WikibaseSettings::isClientEnabled() ) {
			$this->assertNotNull( WikibaseSettings::getClientSettings() );
		} else {
			$this->expectException( MWException::class );
			WikibaseSettings::getClientSettings();
		}
	}

	/** @dataProvider provideSettingsTriples */
	public function testMergeSettings( array $default, array $custom, array $expected ) {
		/** @var SettingsArray $actual */
		$actual = TestingAccessWrapper::newFromClass( WikibaseSettings::class )
			->mergeSettings( $default, $custom );

		$this->assertSame( $expected, $actual->getArrayCopy() );
	}

	public function provideSettingsTriples() {
		$default = [ 'key' => 'x' ];
		$custom['key'] = 'y';
		$expected = [ 'key' => 'y' ];
		yield "['key'], scalar" => [ $default, $custom, $expected ];
		unset( $custom );

		$default = [ 'key' => [] ];
		$custom['key'][] = 'one';
		$expected = [ 'key' => [ 'one' ] ];
		yield "['key'][], default empty" => [ $default, $custom, $expected ];
		unset( $custom );

		$default = [ 'key' => [ 'one', 'two' ] ];
		$custom['key'][] = 'three';
		$expected = [ 'key' => [ 'one', 'two', 'three' ] ];
		yield "['key'][], default nonempty" => [ $default, $custom, $expected ];
		unset( $custom );

		$default = [ 'key' => [ 'a' => 'A', 'b' => 'B' ] ];
		$custom['key']['a'] = 'Ä';
		$expected = [ 'key' => [ 'a' => 'Ä', 'b' => 'B' ] ];
		yield "['key']['a'], scalar" => [ $default, $custom, $expected ];
		unset( $custom );

		$default = [ 'key' => [ 'one' ] ];
		$custom['key'] = 'two';
		$expected = [ 'key' => 'two' ];
		yield "['key'], nonempty->scalar" => [ $default, $custom, $expected ];
		unset( $custom );

		$default = [ 'key' => 'value' ];
		$custom = [];
		$expected = [ 'key' => 'value' ];
		yield 'no custom setting' => [ $default, $custom, $expected ];
		unset( $custom );

		$default = [];
		$custom['key'] = 'value';
		$expected = [ 'key' => 'value' ];
		yield 'no default setting, scalar' => [ $default, $custom, $expected ];
		unset( $custom );

		$default = [];
		$custom['key'] = [ 'one', 'two' ];
		$expected = [ 'key' => [ 'one', 'two' ] ];
		yield 'no default setting, array' => [ $default, $custom, $expected ];
		unset( $custom );

		$default = [ 'key' => function () {
			return 'value';
		} ];
		$custom['key'] = 'custom value';
		$expected = [ 'key' => 'custom value' ];
		yield "['key'], callable->scalar" => [ $default, $custom, $expected ];
		unset( $custom );

		$default = [ 'key' => function () {
			return [ 'one', 'two' ];
		} ];
		$custom['key'] = [ 'ONE', 'TWO' ];
		$expected = [ 'key' => [ 'ONE', 'TWO' ] ];
		yield "['key'], callable->array" => [ $default, $custom, $expected ];
		unset( $custom );
	}

	/** @dataProvider provideSettingsComplexMerges */
	public function testMergeSettingsComplexMerges(
		array $default,
		array $custom,
		array $expected,
		array $overrideArrays = [],
		array $twoDArrayMerge = [],
		array $falseMeansRemove = []
	) {
		/** @var SettingsArray $actual */
		$actual = TestingAccessWrapper::newFromClass( WikibaseSettings::class )
			->mergeSettings( $default, $custom, $overrideArrays, $twoDArrayMerge, $falseMeansRemove );

		$this->assertSame( $expected, $actual->getArrayCopy() );
	}

	public function provideSettingsComplexMerges() {
		$default = [ 'key' => [ 'one', 'two' ] ];
		$custom['key']['two'] = false;
		$expected = [ 'key' => [ 'one' ] ];
		yield "['key'][], false removing the key" => [ $default, $custom, $expected, [], [], [ 'key' ] ];
		unset( $custom );

		$default = [ 'key' => [ 'a' => 'A', 'b' => 'B' ] ];
		$custom['key']['a'] = 'Ä';
		$expected = [ 'key' => [ 'a' => 'Ä' ] ];
		yield "['key']['a'], override all config" => [ $default, $custom, $expected, [ 'key' ], [], [] ];
		unset( $custom );

		$default = [ 'key' => [ 'a' => [ 'a' => 'A' ], 'b' => [ 'B' ] ] ];
		$custom['key']['a']['b'] = 'Ä';
		$expected = [ 'key' => [ 'a' => [ 'b' => 'Ä', 'a' => 'A' ], 'b' => [ 'B' ] ] ];
		yield "['key']['a'], 2d merge (add value)" => [ $default, $custom, $expected, [], [ 'key' ], [] ];
		unset( $custom );

		$default = [ 'key' => [ 'a' => [ 'a' => 'A' ], 'b' => [ 'B' ] ] ];
		$custom['key']['a']['a'] = 'C';
		$expected = [ 'key' => [ 'a' => [ 'a' => 'C' ], 'b' => [ 'B' ] ] ];
		yield "['key']['a'], 2d merge (update value)" => [ $default, $custom, $expected, [], [ 'key' ], [] ];
		unset( $custom );
	}

}
