/**
 * @license GPL-2.0-or-later
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );
	var origFallbackChains;

	QUnit.module( 'wikibase.view.termFallbackResolver', QUnit.newMwEnvironment( {
		beforeEach: function () {
			origFallbackChains = wb.fallbackChains;
			wb.fallbackChains = {
				de: [ 'de', 'mul', 'en' ],
				en: [ 'en', 'mul' ],
				'en-gb': [ 'en-gb', 'en', 'mul' ],
				lb: [ 'lb', 'de', 'mul', 'en' ]
			};
		},
		afterEach: function () {
			wb.fallbackChains = origFallbackChains;
		}
	} ) );

	QUnit.test( 'term in language', ( assert ) => {
		var terms = new datamodel.TermMap( {
			de: new datamodel.Term( 'de', 'de term' ),
			en: new datamodel.Term( 'en', 'en term' )
		} );

		var term = wb.view.termFallbackResolver.getTerm( terms, 'de' );

		assert.notStrictEqual( term, null );
		assert.strictEqual( term.getText(), 'de term' );
	} );

	QUnit.test( 'term in first fallback language', ( assert ) => {
		var terms = new datamodel.TermMap( {
			de: new datamodel.Term( 'de', 'de term' ),
			en: new datamodel.Term( 'en', 'en term' )
		} );

		var term = wb.view.termFallbackResolver.getTerm( terms, 'lb' );

		assert.notStrictEqual( term, null );
		assert.strictEqual( term.getText(), 'de term' );
	} );

	QUnit.test( 'de falls back to mul before en', ( assert ) => {
		var terms = new datamodel.TermMap( {
			en: new datamodel.Term( 'en', 'en term' ),
			mul: new datamodel.Term( 'mul', 'mul term' )
		} );

		var term = wb.view.termFallbackResolver.getTerm( terms, 'de' );

		assert.notStrictEqual( term, null );
		assert.strictEqual( term.getText(), 'mul term' );
	} );

	QUnit.test( 'en-gb falls back to en before mul', ( assert ) => {
		var terms = new datamodel.TermMap( {
			en: new datamodel.Term( 'en', 'en term' ),
			mul: new datamodel.Term( 'mul', 'mul term' )
		} );

		var term = wb.view.termFallbackResolver.getTerm( terms, 'en-gb' );

		assert.notStrictEqual( term, null );
		assert.strictEqual( term.getText(), 'en term' );
	} );

	QUnit.test( 'unknown language falls back to mul', ( assert ) => {
		var terms = new datamodel.TermMap( {
			en: new datamodel.Term( 'en', 'en term' ),
			mul: new datamodel.Term( 'mul', 'mul term' )
		} );

		var term = wb.view.termFallbackResolver.getTerm( terms, 'qqx' );

		assert.notStrictEqual( term, null );
		assert.strictEqual( term.getText(), 'mul term' );
	} );

	QUnit.test( 'unknown language falls back to en if mul missing', ( assert ) => {
		var terms = new datamodel.TermMap( {
			en: new datamodel.Term( 'en', 'en term' )
		} );

		var term = wb.view.termFallbackResolver.getTerm( terms, 'qqx' );

		assert.notStrictEqual( term, null );
		assert.strictEqual( term.getText(), 'en term' );
	} );

	QUnit.test( 'no fallback to random language', ( assert ) => {
		var terms = new datamodel.TermMap( {
			de: new datamodel.Term( 'de', 'de term' )
		} );

		var term = wb.view.termFallbackResolver.getTerm( terms, 'en' );

		assert.strictEqual( term, null );
	} );

	QUnit.test( 'empty terms', ( assert ) => {
		var terms = new datamodel.TermMap();

		var term = wb.view.termFallbackResolver.getTerm( terms, 'en' );

		assert.strictEqual( term, null );
	} );

}( wikibase ) );
