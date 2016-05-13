<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Diff\TermListPatcher;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Services\Diff\TermListPatcher
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class TermListPatcherTest extends PHPUnit_Framework_TestCase {

	/**
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	public function providePatchTermList() {
		return array(
			'add a term' => array(
				new TermList(),
				new Diff( array(
					'en' => new DiffOpAdd( 'foo' )
				) ),
				new TermList( array(
					new Term( 'en', 'foo' )
				) )
			),
			'change a term' => array(
				new TermList( array(
					new Term( 'en', 'foo' )
				) ),
				new Diff( array(
					'en' => new DiffOpChange( 'foo', 'bar' )
				) ),
				new TermList( array(
					new Term( 'en', 'bar' )
				) )
			),
			'remove a term' => array(
				new TermList( array(
					new Term( 'en', 'foo' )
				) ),
				new Diff( array(
					'en' => new DiffOpRemove( 'foo' )
				) ),
				new TermList()
			),
			'add an existing language is no-op' => array(
				new TermList( array(
					new Term( 'en', 'foo' )
				) ),
				new Diff( array(
					'en' => new DiffOpAdd( 'bar' )
				) ),
				new TermList( array(
					new Term( 'en', 'foo' )
				) )
			),
			'add two terms' => array(
				new TermList( array(
					new Term( 'en', 'foo' )
				) ),
				new Diff( array(
					'de' => new DiffOpAdd( 'bar' ),
					'nl' => new DiffOpAdd( 'baz' )
				) ),
				new TermList( array(
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
					new Term( 'nl', 'baz' )
				) )
			),
			'remove a not existing language is no-op' => array(
				new TermList( array(
					new Term( 'en', 'foo' )
				) ),
				new Diff( array(
					'de' => new DiffOpRemove( 'bar' )
				) ),
				new TermList( array(
					new Term( 'en', 'foo' )
				) )
			),
			'change a different value is no-op' => array(
				new TermList( array(
					new Term( 'en', 'foo' )
				) ),
				new Diff( array(
					'en' => new DiffOpChange( 'bar', 'baz' )
				) ),
				new TermList( array(
					new Term( 'en', 'foo' )
				) )
			),
			'remove a different value is no-op' => array(
				new TermList( array(
					new Term( 'en', 'foo' )
				) ),
				new Diff( array(
					'en' => new DiffOpRemove( 'bar' )
				) ),
				new TermList( array(
					new Term( 'en', 'foo' )
				) )
			),
			'complex diff (associative)' => array(
				new TermList( array(
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
					new Term( 'nl', 'baz' )
				) ),
				new Diff( array(
					'en' => new DiffOpChange( 'foo', 'bar' ),
					'de' => new DiffOpRemove( 'bar' ),
					'nl' => new DiffOpChange( 'bar', 'foo' ),
					'it' => new DiffOpAdd( 'foo' )
				), true ),
				new TermList( array(
					new Term( 'en', 'bar' ),
					new Term( 'nl', 'baz' ),
					new Term( 'it', 'foo' )
				) )
			),
			'complex diff (non-associative)' => array(
				new TermList( array(
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
					new Term( 'nl', 'baz' )
				) ),
				new Diff( array(
					'en' => new DiffOpChange( 'foo', 'bar' ),
					'de' => new DiffOpRemove( 'bar' ),
					'nl' => new DiffOpChange( 'bar', 'foo' ),
					'it' => new DiffOpAdd( 'foo' )
				), false ),
				new TermList( array(
					new Term( 'en', 'bar' ),
					new Term( 'nl', 'baz' ),
					new Term( 'it', 'foo' )
				) )
			),
			'complex diff (auto-detected)' => array(
				new TermList( array(
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
					new Term( 'nl', 'baz' )
				) ),
				new Diff( array(
					'en' => new DiffOpChange( 'foo', 'bar' ),
					'de' => new DiffOpRemove( 'bar' ),
					'nl' => new DiffOpChange( 'bar', 'foo' ),
					'it' => new DiffOpAdd( 'foo' )
				) ),
				new TermList( array(
					new Term( 'en', 'bar' ),
					new Term( 'nl', 'baz' ),
					new Term( 'it', 'foo' )
				) )
			),
		);
	}

	/**
	 * @dataProvider providePatchTermList
	 */
	public function testPatchTermList( TermList $terms, Diff $patch, TermList $expected ) {
		$patcher = new TermListPatcher();
		$patcher->patchTermList( $terms, $patch );

		$this->assertSame( $expected->toTextArray(), $terms->toTextArray() );
	}

}
