<?php

namespace Wikibase\DataModel\Services\Tests\Diff\Internal;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Diff\Internal\AliasGroupListPatcher;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @covers \Wikibase\DataModel\Services\Diff\Internal\AliasGroupListPatcher
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupListPatcherTest extends TestCase {

	public function providePatchAliasGroupList() {
		return [
			'add aliases (associative)' => [
				new AliasGroupList(),
				new Diff( [
					'en' => new Diff( [ new DiffOpAdd( 'foo' ), new DiffOpAdd( 'bar' ) ] ),
				], true ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
			],
			'add aliases (non-associative)' => [
				new AliasGroupList(),
				new Diff( [
					'en' => new Diff( [ new DiffOpAdd( 'foo' ), new DiffOpAdd( 'bar' ) ] ),
				], false ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
			],
			'add aliases (auto-detected)' => [
				new AliasGroupList(),
				new Diff( [
					'en' => new Diff( [ new DiffOpAdd( 'foo' ), new DiffOpAdd( 'bar' ) ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
			],
			'add aliases (atomic)' => [
				new AliasGroupList(),
				new Diff( [
					'en' => new DiffOpAdd( [ 'foo', 'bar' ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
			],
			'change aliase' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
				new Diff( [
					'en' => new Diff( [ new DiffOpChange( 'bar', 'baz' ) ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'baz' ] ),
				] ),
			],
			'change aliases (atomic)' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
				new Diff( [
					'en' => new DiffOpChange( [ 'foo', 'bar' ], [ 'baz' ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'baz' ] ),
				] ),
			],
			'remove all aliases' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
				new Diff( [
					'en' => new Diff( [ new DiffOpRemove( 'foo' ), new DiffOpRemove( 'bar' ) ] ),
				] ),
				new AliasGroupList(),
			],
			'remove all aliases (atomic)' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
				new Diff( [
					'en' => new DiffOpRemove( [ 'foo', 'bar' ] ),
				] ),
				new AliasGroupList(),
			],
			'remove some aliases' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar', 'baz' ] ),
				] ),
				new Diff( [
					'en' => new Diff( [ new DiffOpRemove( 'foo' ), new DiffOpRemove( 'bar' ) ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'baz' ] ),
				] ),
			],
			'remove some aliases is no-op (atomic)' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar', 'baz' ] ),
				] ),
				new Diff( [
					'en' => new DiffOpRemove( [ 'foo', 'bar' ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar', 'baz' ] ),
				] ),
			],
			'add alias to an existing language' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
				new Diff( [
					'en' => new Diff( [ new DiffOpAdd( 'baz' ) ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar', 'baz' ] ),
				] ),
			],
			'add an existing language is no-op (atomic)' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
				new Diff( [
					'en' => new DiffOpAdd( [ 'baz' ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
			],
			'add two alias groups' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
				new Diff( [
					'de' => new Diff( [ new DiffOpAdd( 'foo' ), new DiffOpAdd( 'baz' ) ] ),
					'nl' => new Diff( [ new DiffOpAdd( 'bar' ), new DiffOpAdd( 'baz' ) ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
					new AliasGroup( 'de', [ 'foo', 'baz' ] ),
					new AliasGroup( 'nl', [ 'bar', 'baz' ] ),
				] ),
			],
			'add two alias groups (atomic)' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
				new Diff( [
					'de' => new DiffOpAdd( [ 'foo', 'baz' ] ),
					'nl' => new DiffOpAdd( [ 'bar', 'baz' ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
					new AliasGroup( 'de', [ 'foo', 'baz' ] ),
					new AliasGroup( 'nl', [ 'bar', 'baz' ] ),
				] ),
			],
			'remove a not existing language is no-op' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
				new Diff( [
					'de' => new Diff( [ new DiffOpRemove( 'bar' ) ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
			],
			'remove a not existing language is no-op (atomic)' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
				new Diff( [
					'de' => new DiffOpRemove( [ 'bar' ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
			],
			'change different aliases is no-op' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo' ] ),
				] ),
				new Diff( [
					'en' => new Diff( [ new DiffOpChange( 'bar', 'baz' ) ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo' ] ),
				] ),
			],
			'change different aliases is no-op (atomic)' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
				new Diff( [
					'en' => new DiffOpChange( [ 'foo', 'baz' ], [ 'baz' ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
				] ),
			],
			'complex diff' => [
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'foo', 'bar' ] ),
					new AliasGroup( 'de', [ 'foo', 'baz' ] ),
					new AliasGroup( 'nl', [ 'bar', 'baz' ] ),
				] ),
				new Diff( [
					'en' => new Diff( [ new DiffOpRemove( 'foo' ), new DiffOpAdd( 'baz' ) ] ),
					'de' => new Diff( [ new DiffOpRemove( 'foo' ), new DiffOpRemove( 'baz' ) ] ),
					'nl' => new Diff( [ new DiffOpRemove( 'foo' ), new DiffOpRemove( 'bar' ) ] ),
					'it' => new Diff( [ new DiffOpAdd( 'bar' ), new DiffOpAdd( 'baz' ) ] ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'en', [ 'bar', 'baz' ] ),
					new AliasGroup( 'nl', [ 'baz' ] ),
					new AliasGroup( 'it', [ 'bar', 'baz' ] ),
				] ),
			],
		];
	}

	/**
	 * @dataProvider providePatchAliasGroupList
	 */
	public function testPatchAliasGroupList( AliasGroupList $aliasGroups, Diff $patch, AliasGroupList $expected ) {
		$patcher = new AliasGroupListPatcher();
		$patcher->patchAliasGroupList( $aliasGroups, $patch );

		$this->assertSame( $expected->toTextArray(), $aliasGroups->toTextArray() );
	}

}
