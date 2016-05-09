<?php

namespace Wikibase\DataModel\Services\Tests\Diff\Internal;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Diff\Internal\AliasGroupListPatcher;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;

/**
 * @covers Wikibase\DataModel\Services\Diff\Internal\AliasGroupListPatcher
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class AliasGroupListPatcherTest extends PHPUnit_Framework_TestCase {

	/**
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function providePatchAliasGroupList() {
		return array(
			'add aliases (associative)' => array(
				new AliasGroupList(),
				new Diff( array(
					'en' => new Diff( array( new DiffOpAdd( 'foo' ), new DiffOpAdd( 'bar' ) ) )
				), true ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) )
			),
			'add aliases (non-associative)' => array(
				new AliasGroupList(),
				new Diff( array(
					'en' => new Diff( array( new DiffOpAdd( 'foo' ), new DiffOpAdd( 'bar' ) ) )
				), false ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) )
			),
			'add aliases (auto-detected)' => array(
				new AliasGroupList(),
				new Diff( array(
					'en' => new Diff( array( new DiffOpAdd( 'foo' ), new DiffOpAdd( 'bar' ) ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) )
			),
			'add aliases (atomic)' => array(
				new AliasGroupList(),
				new Diff( array(
					'en' => new DiffOpAdd( array( 'foo', 'bar' ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) )
			),
			'change aliase' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) ),
				new Diff( array(
					'en' => new Diff( array( new DiffOpChange( 'bar', 'baz' ) ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'baz' ) )
				) )
			),
			'change aliases (atomic)' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) ),
				new Diff( array(
					'en' => new DiffOpChange( array( 'foo', 'bar' ), array( 'baz' ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'baz' ) )
				) )
			),
			'remove all aliases' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) ),
				new Diff( array(
					'en' => new Diff( array( new DiffOpRemove( 'foo' ), new DiffOpRemove( 'bar' ) ) )
				) ),
				new AliasGroupList()
			),
			'remove all aliases (atomic)' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) ),
				new Diff( array(
					'en' => new DiffOpRemove( array( 'foo', 'bar' ) )
				) ),
				new AliasGroupList()
			),
			'remove some aliases' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar', 'baz' ) )
				) ),
				new Diff( array(
					'en' => new Diff( array( new DiffOpRemove( 'foo' ), new DiffOpRemove( 'bar' ) ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'baz' ) )
				) )
			),
			'remove some aliases is no-op (atomic)' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar', 'baz' ) )
				) ),
				new Diff( array(
					'en' => new DiffOpRemove( array( 'foo', 'bar' ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar', 'baz' ) )
				) )
			),
			'add alias to an existing language' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) ),
				new Diff( array(
					'en' => new Diff( array( new DiffOpAdd( 'baz' ) ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar', 'baz' ) )
				) )
			),
			'add an existing language is no-op (atomic)' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) ),
				new Diff( array(
					'en' => new DiffOpAdd( array( 'baz' ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) )
			),
			'add two alias groups' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) ),
				new Diff( array(
					'de' => new Diff( array( new DiffOpAdd( 'foo' ), new DiffOpAdd( 'baz' ) ) ),
					'nl' => new Diff( array( new DiffOpAdd( 'bar' ), new DiffOpAdd( 'baz' ) ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) ),
					new AliasGroup( 'de', array( 'foo', 'baz' ) ),
					new AliasGroup( 'nl', array( 'bar', 'baz' ) )
				) )
			),
			'add two alias groups (atomic)' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) ),
				new Diff( array(
					'de' => new DiffOpAdd( array( 'foo', 'baz' ) ),
					'nl' => new DiffOpAdd( array( 'bar', 'baz' ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) ),
					new AliasGroup( 'de', array( 'foo', 'baz' ) ),
					new AliasGroup( 'nl', array( 'bar', 'baz' ) )
				) )
			),
			'remove a not existing language is no-op' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) ),
				new Diff( array(
					'de' => new Diff( array( new DiffOpRemove( 'bar' ) ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) )
			),
			'remove a not existing language is no-op (atomic)' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) ),
				new Diff( array(
					'de' => new DiffOpRemove( array( 'bar' ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) )
			),
			'change different aliases is no-op' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo' ) )
				) ),
				new Diff( array(
					'en' => new Diff( array( new DiffOpChange( 'bar', 'baz' ) ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo' ) )
				) )
			),
			'change different aliases is no-op (atomic)' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) ),
				new Diff( array(
					'en' => new DiffOpChange( array( 'foo', 'baz' ), array( 'baz' ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) )
				) )
			),
			'complex diff' => array(
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'foo', 'bar' ) ),
					new AliasGroup( 'de', array( 'foo', 'baz' ) ),
				    new AliasGroup( 'nl', array( 'bar', 'baz' ) )
				) ),
				new Diff( array(
					'en' => new Diff( array( new DiffOpRemove( 'foo' ), new DiffOpAdd( 'baz' ) ) ),
					'de' => new Diff( array( new DiffOpRemove( 'foo' ), new DiffOpRemove( 'baz' ) ) ),
					'nl' => new Diff( array( new DiffOpRemove( 'foo' ), new DiffOpRemove( 'bar' ) ) ),
					'it' => new Diff( array( new DiffOpAdd( 'bar' ), new DiffOpAdd( 'baz' ) ) )
				) ),
				new AliasGroupList( array(
					new AliasGroup( 'en', array( 'bar', 'baz' ) ),
					new AliasGroup( 'nl', array( 'baz' ) ),
					new AliasGroup( 'it', array( 'bar', 'baz' ) )
				) )
			),
		);
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
