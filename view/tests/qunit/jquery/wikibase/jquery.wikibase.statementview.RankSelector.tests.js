/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';
	var datamodel = require( 'wikibase.datamodel' );

	/**
	 * Initializes a rank selector suitable for testing.
	 *
	 * @return {jQuery.wikibase.statementview.RankSelector}
	 */
	function createTestRankSelector( options ) {
		var $node = $( '<span>' )
			.addClass( 'test_rankselector' )
			.appendTo( document.body );

		var rankSelector = new $.wikibase.statementview.RankSelector( ( options || {} ), $node );
		$node.data( 'test_rankselector', rankSelector );

		return rankSelector;
	}

	QUnit.module( 'jquery.wikibase.statementview.RankSelector', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_rankselector' ).each( function ( i, node ) {
				var $node = $( node );
				$node.data( 'test_rankselector' ).destroy();
				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Instantiation', function ( assert ) {
		var rankSelector = createTestRankSelector( { value: datamodel.Statement.RANK.DEPRECATED } );

		assert.strictEqual(
			rankSelector.value(),
			datamodel.Statement.RANK.DEPRECATED,
			'Instantiated rank selector with "deprecated" rank.'
		);
	} );

	QUnit.test( 'Set and get rank via value()', function ( assert ) {
		var rankSelector = createTestRankSelector();

		rankSelector.value( datamodel.Statement.RANK.DEPRECATED );

		assert.strictEqual(
			rankSelector.value(),
			datamodel.Statement.RANK.DEPRECATED,
			'Set "deprecated" rank.'
		);

		rankSelector.value( datamodel.Statement.RANK.PREFERRED );

		assert.strictEqual(
			rankSelector.value(),
			datamodel.Statement.RANK.PREFERRED,
			'Set "preferred" rank.'
		);

		rankSelector.value( datamodel.Statement.RANK.NORMAL );

		assert.strictEqual(
			rankSelector.value(),
			datamodel.Statement.RANK.NORMAL,
			'Set "normal" rank.'
		);
	} );

	QUnit.test( 'Set and get rank via option()', function ( assert ) {
		var rankSelector = createTestRankSelector();

		rankSelector.option( 'value', datamodel.Statement.RANK.DEPRECATED );

		assert.strictEqual(
			rankSelector.option( 'value' ),
			datamodel.Statement.RANK.DEPRECATED,
			'Set "deprecated" rank.'
		);

		rankSelector.option( 'value', datamodel.Statement.RANK.PREFERRED );

		assert.strictEqual(
			rankSelector.option( 'value' ),
			datamodel.Statement.RANK.PREFERRED,
			'Set "preferred" rank.'
		);

		rankSelector.option( 'value', datamodel.Statement.RANK.NORMAL );

		assert.strictEqual(
			rankSelector.option( 'value' ),
			datamodel.Statement.RANK.NORMAL,
			'Set "normal" rank.'
		);
	} );

	QUnit.test( 'Multiple rank selectors', function ( assert ) {
		var rankSelector1 = createTestRankSelector( { value: datamodel.Statement.RANK.DEPRECATED } );

		assert.strictEqual(
			rankSelector1.value(),
			datamodel.Statement.RANK.DEPRECATED,
			'Instantiated first rank selector with "deprecated" rank.'
		);

		var rankSelector2 = createTestRankSelector( { value: datamodel.Statement.RANK.PREFERRED } );

		assert.strictEqual(
			rankSelector2.value(),
			datamodel.Statement.RANK.PREFERRED,
			'Instantiated second rank selector with "preferred" rank.'
		);

		assert.strictEqual(
			rankSelector1.value(),
			datamodel.Statement.RANK.DEPRECATED,
			'First rank selector still features "deprecated" rank.'
		);

		rankSelector1.value( datamodel.Statement.RANK.NORMAL );

		assert.strictEqual(
			rankSelector1.value(),
			datamodel.Statement.RANK.NORMAL,
			'Changed first rank selector\'s rank to "normal".'
		);

		assert.strictEqual(
			rankSelector2.value(),
			datamodel.Statement.RANK.PREFERRED,
			'Second rank selector still features "preferred" rank.'
		);

		rankSelector2.value( datamodel.Statement.RANK.DEPRECATED );

		assert.strictEqual(
			rankSelector2.value(),
			datamodel.Statement.RANK.DEPRECATED,
			'Changed second rank selector\'s rank to "deprecated".'
		);

		assert.strictEqual(
			rankSelector1.value(),
			datamodel.Statement.RANK.NORMAL,
			'First rank selector still features "normal" rank.'
		);

	} );

}() );
