/**
 * QUnit tests for autocomplete input interface
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.uiPropertyEditTool.EditableValue.AutocompleteInterface.tests.js
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
			this.additionalResults = [
				'yfghj',
				'ycvbn',
				'ycvba'
			];
			this.reopenMenu = function( resultSet ) {
				if ( typeof resultSet != 'undefined' ) {
					this.autocomplete.setResultSet( resultSet );
				}
				this.autocomplete._inputElem.data( 'autocomplete' ).close();
				this.autocomplete._inputElem.data( 'autocomplete' ).search( 'y' );
			};

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

			this.reopen = null;
			this.resultSet = null;
			this.additionalResults = null;
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


	test( 'automatic height adjustment', function() {
		this.autocomplete.setResultSet( this.resultSet );
		this.autocomplete.startEditing();
		this.autocomplete._inputElem.data( 'autocomplete' ).search( 'y' );

		var initHeight = this.autocomplete._inputElem.data( 'autocomplete' ).menu.element.height();
		this.resultSet.push( this.additionalResults[0] );
		this.reopenMenu( this.resultSet );

		// testing (MAX_ITEMS - 1)++
		ok(
			this.autocomplete._inputElem.data( 'autocomplete' ).menu.element.height() > initHeight,
			'height changed after adding another item to result set'
		);

		// adding one more item (MAX_ITEMS + 1) first, since there might be side effects adding the scrollbar
		this.resultSet.push( this.additionalResults[1] );
		this.reopenMenu( this.resultSet );
		initHeight = this.autocomplete._inputElem.data( 'autocomplete' ).menu.element.height();

		this.resultSet.push( this.additionalResults[2] );
		this.reopenMenu( this.resultSet );

		// testing (MAX_ITEMS + 1)++
		equal(
			this.autocomplete._inputElem.data( 'autocomplete' ).menu.element.height(),
			initHeight,
			'height unchanged after adding more than maximum items'
		);

	} );


}() );
