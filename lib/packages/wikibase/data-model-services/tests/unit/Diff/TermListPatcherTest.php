<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Diff\TermListPatcher;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers \Wikibase\DataModel\Services\Diff\TermListPatcher
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class TermListPatcherTest extends TestCase {

	public function providePatchTermList() {
		return [
			'add a term' => [
				new TermList(),
				new Diff( [
					'en' => new DiffOpAdd( 'foo' ),
				] ),
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
			],
			'change a term' => [
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
				new Diff( [
					'en' => new DiffOpChange( 'foo', 'bar' ),
				] ),
				new TermList( [
					new Term( 'en', 'bar' ),
				] ),
			],
			'remove a term' => [
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
				new Diff( [
					'en' => new DiffOpRemove( 'foo' ),
				] ),
				new TermList(),
			],
			'add an existing language is no-op' => [
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
				new Diff( [
					'en' => new DiffOpAdd( 'bar' ),
				] ),
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
			],
			'add two terms' => [
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
				new Diff( [
					'de' => new DiffOpAdd( 'bar' ),
					'nl' => new DiffOpAdd( 'baz' ),
				] ),
				new TermList( [
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
					new Term( 'nl', 'baz' ),
				] ),
			],
			'remove a not existing language is no-op' => [
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
				new Diff( [
					'de' => new DiffOpRemove( 'bar' ),
				] ),
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
			],
			'change a different value is no-op' => [
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
				new Diff( [
					'en' => new DiffOpChange( 'bar', 'baz' ),
				] ),
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
			],
			'remove a different value is no-op' => [
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
				new Diff( [
					'en' => new DiffOpRemove( 'bar' ),
				] ),
				new TermList( [
					new Term( 'en', 'foo' ),
				] ),
			],
			'complex diff (associative)' => [
				new TermList( [
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
					new Term( 'nl', 'baz' ),
				] ),
				new Diff( [
					'en' => new DiffOpChange( 'foo', 'bar' ),
					'de' => new DiffOpRemove( 'bar' ),
					'nl' => new DiffOpChange( 'bar', 'foo' ),
					'it' => new DiffOpAdd( 'foo' ),
				], true ),
				new TermList( [
					new Term( 'en', 'bar' ),
					new Term( 'nl', 'baz' ),
					new Term( 'it', 'foo' ),
				] ),
			],
			'complex diff (non-associative)' => [
				new TermList( [
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
					new Term( 'nl', 'baz' ),
				] ),
				new Diff( [
					'en' => new DiffOpChange( 'foo', 'bar' ),
					'de' => new DiffOpRemove( 'bar' ),
					'nl' => new DiffOpChange( 'bar', 'foo' ),
					'it' => new DiffOpAdd( 'foo' ),
				], false ),
				new TermList( [
					new Term( 'en', 'bar' ),
					new Term( 'nl', 'baz' ),
					new Term( 'it', 'foo' ),
				] ),
			],
			'complex diff (auto-detected)' => [
				new TermList( [
					new Term( 'en', 'foo' ),
					new Term( 'de', 'bar' ),
					new Term( 'nl', 'baz' ),
				] ),
				new Diff( [
					'en' => new DiffOpChange( 'foo', 'bar' ),
					'de' => new DiffOpRemove( 'bar' ),
					'nl' => new DiffOpChange( 'bar', 'foo' ),
					'it' => new DiffOpAdd( 'foo' ),
				] ),
				new TermList( [
					new Term( 'en', 'bar' ),
					new Term( 'nl', 'baz' ),
					new Term( 'it', 'foo' ),
				] ),
			],
		];
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
