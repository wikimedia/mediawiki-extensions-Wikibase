/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw, wb ) {
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

	QUnit.module( 'jquery.wikibase.statementview.RankSelector', window.QUnit.newWbEnvironment( {
		teardown: function() {
			$( '.test_rankselector' ).each( function( i, node ) {
				var $node = $( node );
				$node.data( 'test_rankselector' ).destroy();
				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Instantiation', function( assert ) {
		var rankSelector = createTestRankSelector( { rank: wb.Statement.RANK.DEPRECATED } );

		assert.equal(
			wb.Statement.RANK.DEPRECATED,
			rankSelector.rank(),
			'Instantiated rank selector with "deprecated" rank'
		);
	} );

	QUnit.test( 'Set and get rank via rank()', function( assert ) {
		var rankSelector = createTestRankSelector();

		rankSelector.rank( wb.Statement.RANK.DEPRECATED );

		assert.equal(
			wb.Statement.RANK.DEPRECATED,
			rankSelector.rank(),
			'Set "deprecated" rank.'
		);

		rankSelector.rank( wb.Statement.RANK.PREFERRED );

		assert.equal(
			wb.Statement.RANK.PREFERRED,
			rankSelector.rank(),
			'Set "preferred" rank.'
		);

		rankSelector.rank( wb.Statement.RANK.NORMAL );

		assert.equal(
			wb.Statement.RANK.NORMAL,
			rankSelector.rank(),
			'Set "normal" rank.'
		);
	} );

	QUnit.test( 'Set and get rank via option()', function( assert ) {
		var rankSelector = createTestRankSelector();

		rankSelector.option( 'rank', wb.Statement.RANK.DEPRECATED );

		assert.equal(
			wb.Statement.RANK.DEPRECATED,
			rankSelector.option( 'rank' ),
			'Set "deprecated" rank.'
		);

		rankSelector.option( 'rank', wb.Statement.RANK.PREFERRED );

		assert.equal(
			wb.Statement.RANK.PREFERRED,
			rankSelector.option( 'rank' ),
			'Set "preferred" rank.'
		);

		rankSelector.option( 'rank', wb.Statement.RANK.NORMAL );

		assert.equal(
			wb.Statement.RANK.NORMAL,
			rankSelector.option( 'rank' ),
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

} )( jQuery, mediaWiki, wikibase );
