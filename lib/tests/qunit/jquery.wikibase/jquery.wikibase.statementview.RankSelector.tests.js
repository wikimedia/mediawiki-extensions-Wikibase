/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
	'use strict';

	/**
	 * Initializes a rank selector suitable for testing.
	 *
	 * @return {jQuery.wikibase.statementview.RankSelector}
	 */
	function createTestRankSelector( options ) {
		var $node = $( '<span>' )
			.addClass( 'test_rankselector' )
			.appendTo( 'body' );

		var rankSelector = new $.wikibase.statementview.RankSelector( ( options || {} ), $node );
		$node.data( 'test_rankselector', rankSelector );

		return rankSelector;
	}

	QUnit.module( 'jquery.wikibase.statementview.RankSelector', QUnit.newMwEnvironment( {
		teardown: function() {
			$( '.test_rankselector' ).each( function( i, node ) {
				var $node = $( node );
				$node.data( 'test_rankselector' ).destroy();
				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Instantiation', function( assert ) {
		var rankSelector = createTestRankSelector( { rank: wb.datamodel.Statement.RANK.DEPRECATED } );

		assert.equal(
			rankSelector.rank(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Instantiated rank selector with "deprecated" rank.'
		);

		rankSelector = createTestRankSelector( { templateParams: ['ui-state-disabled', '', ''] } );

		assert.ok(
			rankSelector.isDisabled(),
			'Instantiated rank selector disabled via css class.'
		);
	} );

	QUnit.test( 'Set and get rank via rank()', function( assert ) {
		var rankSelector = createTestRankSelector();

		rankSelector.rank( wb.datamodel.Statement.RANK.DEPRECATED );

		assert.equal(
			rankSelector.rank(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Set "deprecated" rank.'
		);

		rankSelector.rank( wb.datamodel.Statement.RANK.PREFERRED );

		assert.equal(
			rankSelector.rank(),
			wb.datamodel.Statement.RANK.PREFERRED,
			'Set "preferred" rank.'
		);

		rankSelector.rank( wb.datamodel.Statement.RANK.NORMAL );

		assert.equal(
			rankSelector.rank(),
			wb.datamodel.Statement.RANK.NORMAL,
			'Set "normal" rank.'
		);
	} );

	QUnit.test( 'Set and get rank via option()', function( assert ) {
		var rankSelector = createTestRankSelector();

		rankSelector.option( 'rank', wb.datamodel.Statement.RANK.DEPRECATED );

		assert.equal(
			rankSelector.option( 'rank' ),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Set "deprecated" rank.'
		);

		rankSelector.option( 'rank', wb.datamodel.Statement.RANK.PREFERRED );

		assert.equal(
			rankSelector.option( 'rank' ),
			wb.datamodel.Statement.RANK.PREFERRED,
			'Set "preferred" rank.'
		);

		rankSelector.option( 'rank', wb.datamodel.Statement.RANK.NORMAL );

		assert.equal(
			rankSelector.option( 'rank' ),
			wb.datamodel.Statement.RANK.NORMAL,
			'Set "normal" rank.'
		);
	} );

	QUnit.test( 'disable(), enable(), isDisable()', function( assert ) {
		var rankSelector = createTestRankSelector();

		assert.ok(
			!rankSelector.isDisabled(),
			'Rank selector is enabled after instantiating.'
		);

		rankSelector.disable();

		assert.ok(
			rankSelector.isDisabled(),
			'Disabled rank selector.'
		);

		rankSelector.enable();

		assert.ok(
			!rankSelector.isDisabled(),
			'Enabled rank selector.'
		);
	} );

	QUnit.test( 'Multiple rank selectors', function ( assert ) {
		var rankSelector1 = createTestRankSelector( { rank: wb.datamodel.Statement.RANK.DEPRECATED } );

		assert.equal(
			rankSelector1.rank(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Instantiated first rank selector with "deprecated" rank.'
		);

		var rankSelector2 = createTestRankSelector( { rank: wb.datamodel.Statement.RANK.PREFERRED } );

		assert.equal(
			rankSelector2.rank(),
			wb.datamodel.Statement.RANK.PREFERRED,
			'Instantiated second rank selector with "preferred" rank.'
		);

		assert.equal(
			rankSelector1.rank(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'First rank selector still features "deprecated" rank.'
		);

		rankSelector1.rank( wb.datamodel.Statement.RANK.NORMAL );

		assert.equal(
			rankSelector1.rank(),
			wb.datamodel.Statement.RANK.NORMAL,
			'Changed first rank selector\'s rank to "normal".'
		);

		assert.equal(
			rankSelector2.rank(),
			wb.datamodel.Statement.RANK.PREFERRED,
			'Second rank selector still features "preferred" rank.'
		);

		rankSelector2.rank( wb.datamodel.Statement.RANK.DEPRECATED );

		assert.equal(
			rankSelector2.rank(),
			wb.datamodel.Statement.RANK.DEPRECATED,
			'Changed second rank selector\'s rank to "deprecated".'
		);

		assert.equal(
			rankSelector1.rank(),
			wb.datamodel.Statement.RANK.NORMAL,
			'First rank selector still features "normal" rank.'
		);

	} );

} )( jQuery, wikibase, QUnit );
