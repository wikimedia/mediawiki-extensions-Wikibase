/**
 * QUnit tests for Wikibase inputAutoExpand jQuery plugin
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.utilities.jQuery.inputAutoExpand.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

( function() {
	module( 'wikibase.utilities.jQuery.inputAutoExpand', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.input = $( '<input/>', {
				'type': 'text',
				'name': 'test',
				'value': '',
				'placeholder': ''
			} );

			this.input.appendTo( '<div/>' )
		},
		teardown: function() {

		}

	} ) );

	test( 'Apply jQuery.inputAutoExpand() on input boxes', function() {

		equal(
			this.input.inputAutoExpand(),
			this.input,
			'auto expand initialized, returned the input box wrapped in jQuery object'
		);

	} );

}() );
