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
	module( 'wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface', {
		setup: function() {
			this.node = $( '<div/>', { id: 'subject' } );
			this.autocomplete = new window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface( this.node );
			this.resultSet = ['qwer', 'asdf', 'yxcv' ];

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

			this.autocomplete = null;
			this.node = null;
		}

	} );


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
			this.autocomplete._inputElem.data('autocomplete').menu.element.css( 'display' ),
			'none',
			'menu is hidden'
		);

	} );


}() );
