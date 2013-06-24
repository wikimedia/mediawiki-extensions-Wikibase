/**
 * QUnit tests for autocomplete input interface
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */

( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	/**
	 * Factory for creating a new EditableAliases object suited for testing.
	 *
	 * @param {jQuery} $node
	 * @return  wb.ui.PropertyEditTool.EditableValue.AutocompleteInterface
	 */
	var newTestAutocompleteInterface = function( $node ) {
		if ( $node === undefined ) {
			$node = $( '<div/>', { id: 'subject' } );
		}
		return new wb.ui.PropertyEditTool.EditableValue.AutocompleteInterface( $node );
	};

	QUnit.module(
		'wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface',
		QUnit.newWbEnvironment( {
			setup: function() {
				this.resultSet = [
					'yqwer',
					'yasdf',
					'yxcv',
					'yxcv',
					'yxcv',
					'yxcv',
					'yxcv',
					'yxcv',
					'yxcv'
				];
			},
			teardown: function() {}
		} )
	);

	QUnit.test( 'basic check', function( assert ) {

		var $node = $( '<div/>', { id: 'subject' } );
		var subject = newTestAutocompleteInterface( $node );

		assert.ok(
			subject._subject[0] === $node[0],
			'validated subject'
		);

		assert.equal(
			subject._currentResults.length,
			0,
			'no result set yet'
		);

		subject.setResultSet( this.resultSet );

		assert.equal(
			subject._currentResults,
			this.resultSet,
			'verified set result set'
		);

		subject.startEditing();

		assert.equal(
			typeof subject._inputElem.data( 'suggester' ),
			'object',
			'initialized autocomplete widget'
		);

		assert.equal(
			subject._inputElem.data( 'suggester' ).menu.element.css( 'display' ),
			'none',
			'menu is hidden'
		);

		subject.destroy();

		assert.equal(
			$( subject._getValueContainer()[0] ).children().length,
			0,
			'destroyed input element'
		);

	} );

	QUnit.test( 'simulate AJAX', function( assert ) {

		var subject = newTestAutocompleteInterface();

		// tell autocomplete to use AJAX interface
		subject.url = 'someurl';
		subject.ajaxParams = {};

		subject.startEditing();

		// overriding AJAX request handling
		subject._inputElem.data( 'suggester' ).source = $.proxy(
			function( request, suggest ) {
				subject._currentResults = this.resultSet;
				suggest( this.resultSet );
			},
			this
		);

		// trigger simulated AJAX call
		subject._inputElem.data( 'suggester' ).search( 'y' );

		assert.equal(
			subject._currentResults.length,
			this.resultSet.length,
			'calling function simulating AJAX handling'
		);

		subject.setValue( this.resultSet[0] );

		assert.equal(
			subject.getValue(),
			this.resultSet[0],
			'set valid value'
		);

		subject.stopEditing( true );

		assert.equal(
			subject.getValue(),
			this.resultSet[0],
			'confirmed valid value after stopping edit mode'
		);

		subject.setResultSet( [] );
		subject.startEditing();

		assert.equal(
			subject.getValue(),
			this.resultSet[0],
			'confirmed last set value even after eptying result set (simulating not yet received AJAX request)'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
