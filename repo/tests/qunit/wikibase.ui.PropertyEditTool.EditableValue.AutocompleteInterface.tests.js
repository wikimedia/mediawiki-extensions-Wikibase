/**
 * QUnit tests for autocomplete input interface
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';


( function() {
	module( 'wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.node = $( '<div/>', { id: 'subject' } );
			this.autocomplete = new window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface( this.node );
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

			ok(
				this.autocomplete._subject[0] == this.node[0],
				'validated subject'
			);

		},
		teardown: function() {
			this.autocomplete.destroy();

			equal(
				$( this.autocomplete._getValueContainer()[0] ).children().length,
				0,
				'destroyed input element'
			);

			this.resultSet = null;
			this.autocomplete = null;
			this.node = null;
		}

	} ) );


	test( 'basic check', function() {

		equal(
			this.autocomplete._currentResults.length,
			0,
			'no result set yet'
		);

		this.autocomplete.setResultSet( this.resultSet );

		equal(
			this.autocomplete._currentResults,
			this.resultSet,
			'verified set result set'
		);

		this.autocomplete.startEditing();

		equal(
			typeof this.autocomplete._inputElem.data( 'autocomplete' ),
			'object',
			'initialized autocomplete widget'
		);

		equal(
			this.autocomplete._inputElem.data( 'autocomplete' ).menu.element.css( 'display' ),
			'none',
			'menu is hidden'
		);

	} );


	test( 'simulate AJAX', function() {
		// tell autocomplete to use AJAX interface
		this.autocomplete.url = 'someurl';
		this.autocomplete.ajaxParams = {};

		this.autocomplete.startEditing();

		// overriding AJAX request handling
		this.autocomplete._inputElem.data( 'wikibaseAutocomplete' ).source = $.proxy(
			function( request, suggest ) {
				this.autocomplete._currentResults = this.resultSet;
				suggest( this.resultSet );
			},
			this
		);

		// trigger simulated AJAX call
		this.autocomplete._inputElem.data( 'wikibaseAutocomplete' ).search( 'y' );

		equal(
			this.autocomplete._currentResults.length,
			this.resultSet.length,
			'calling function simulating AJAX handling'
		);

		this.autocomplete.setValue( this.resultSet[0] );

		equal(
			this.autocomplete.getValue(),
			this.resultSet[0],
			'set valid value'
		);

		this.autocomplete.stopEditing( true );

		equal(
			this.autocomplete.getValue(),
			this.resultSet[0],
			'confirmed valid value after stopping edit mode'
		);

		this.autocomplete.setResultSet( [] );
		this.autocomplete.startEditing();

		equal(
			this.autocomplete.getValue(),
			this.resultSet[0],
			'confirmed last set value even after eptying result set (simulating not yet received AJAX request)'
		);

	} );


}() );
