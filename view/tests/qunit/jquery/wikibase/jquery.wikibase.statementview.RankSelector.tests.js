/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( $, wb, QUnit ) {
	'use strict';

	/**
	 * Initializes a rank selector suitable for testing.
	 *
	 * @return {jQuery.wikibase.statementview.RankSelector}
	 */
	function createTestRankSelector( options ) {
		var $node = $( '<span/>' )
			.addClass( 'test_rankselector' )
			.appendTo( 'body' );

		var rankSelector = new $.wikibase.statementview.RankSelector( ( options || {} ), $node );
		$node.data( 'test_rankselector', rankSelector );

		return rankSelector;
	}

	QUnit.module( 'jquery.wikibase.statementview.RankSelector', QUnit.newMwEnvironment( {
		teardown: function () {
			$( '.test_rankselector' ).each( function ( i, node ) {
				var $node = $( node );
				$node.data( 'test_rankselector' ).destroy();
				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Instantiation', function ( assert ) {
		assert.expect( 1 );
		var rankSelector = createTestRankSelector( { value: wb.datamodel.Statement.RANK.DEPRECATED } );

		assert.equal(
			rankSelector.value(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Instantiated rank selector with "deprecated" rank.'
		);
	} );

	QUnit.test( 'Set and get rank via value()', function ( assert ) {
		assert.expect( 3 );
		var rankSelector = createTestRankSelector();

		rankSelector.value( wb.datamodel.Statement.RANK.DEPRECATED );

		assert.equal(
			rankSelector.value(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Set "deprecated" rank.'
		);

		rankSelector.value( wb.datamodel.Statement.RANK.PREFERRED );

		assert.equal(
			rankSelector.value(),
			wb.datamodel.Statement.RANK.PREFERRED,
			'Set "preferred" rank.'
		);

		rankSelector.value( wb.datamodel.Statement.RANK.NORMAL );

		assert.equal(
			rankSelector.value(),
			wb.datamodel.Statement.RANK.NORMAL,
			'Set "normal" rank.'
		);
	} );

	QUnit.test( 'Set and get rank via option()', function ( assert ) {
		assert.expect( 3 );
		var rankSelector = createTestRankSelector();

		rankSelector.option( 'value', wb.datamodel.Statement.RANK.DEPRECATED );

		assert.equal(
			rankSelector.option( 'value' ),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Set "deprecated" rank.'
		);

		rankSelector.option( 'value', wb.datamodel.Statement.RANK.PREFERRED );

		assert.equal(
			rankSelector.option( 'value' ),
			wb.datamodel.Statement.RANK.PREFERRED,
			'Set "preferred" rank.'
		);

		rankSelector.option( 'value', wb.datamodel.Statement.RANK.NORMAL );

		assert.equal(
			rankSelector.option( 'value' ),
			wb.datamodel.Statement.RANK.NORMAL,
			'Set "normal" rank.'
		);
	} );

	QUnit.test( 'Multiple rank selectors', function ( assert ) {
		assert.expect( 7 );
		var rankSelector1 = createTestRankSelector( { value: wb.datamodel.Statement.RANK.DEPRECATED } );

		assert.equal(
			rankSelector1.value(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Instantiated first rank selector with "deprecated" rank.'
		);

		var rankSelector2 = createTestRankSelector( { value: wb.datamodel.Statement.RANK.PREFERRED } );

		assert.equal(
			rankSelector2.value(),
			wb.datamodel.Statement.RANK.PREFERRED,
			'Instantiated second rank selector with "preferred" rank.'
		);

		assert.equal(
			rankSelector1.value(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'First rank selector still features "deprecated" rank.'
		);

		rankSelector1.value( wb.datamodel.Statement.RANK.NORMAL );

		assert.equal(
			rankSelector1.value(),
			wb.datamodel.Statement.RANK.NORMAL,
			'Changed first rank selector\'s rank to "normal".'
		);

		assert.equal(
			rankSelector2.value(),
			wb.datamodel.Statement.RANK.PREFERRED,
			'Second rank selector still features "preferred" rank.'
		);

		rankSelector2.value( wb.datamodel.Statement.RANK.DEPRECATED );

		assert.equal(
			rankSelector2.value(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Changed second rank selector\'s rank to "deprecated".'
		);

		assert.equal(
			rankSelector1.value(),
			wb.datamodel.Statement.RANK.NORMAL,
			'First rank selector still features "normal" rank.'
		);

	} );

}( jQuery, wikibase, QUnit ) );
