/**
 * QUnit tests for Wikibase 'eachchange' jQuery plugin
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

( function() {
	module( 'wikibase.utilities.jQuery.ui.eachchange', window.QUnit.newWbEnvironment( {
		setup: function() {
			var self = this;
			this.i = 0;
			this.iIncr = function() {
				self.i++;
			};
			this.iReset = function() {
				self.i = 0;
			};
			this.iTriggerTest = function( subject, expected, description ) {
				subject.filter( 'input' ).trigger( 'eachchange' );
				equal(
					self.i,
					expected,
					description
				);
			}
		},
		teardown: function() {}
	} ) );

	test( 'jQuery.eachchange() basics', function() {
		var subject = $( '<input/>', {
			'type': 'text',
			'name': 'test',
			'value': ''
		} ).add( $( '<div/>' ) ); // should always be ignored, otherwise some tests will fail.

		equal(
			subject.eachchange( this.iIncr ),
			subject,
			'"eachchange" initialized, returns the original jQuery object'
		);

		subject.eachchange( this.iIncr ); // assign second time

		this.iTriggerTest(
			subject,
			2,
			'"eachchange" triggered, eachchange() was used twice on same object but should only be triggered once each.'
		);

		this.iReset();
		subject.on( 'eachchange', this.iIncr );

		this.iTriggerTest(
			subject,
			3,
			'"eachchange" added with jQuery.on(), should trigger three times now.'
		);

	} );

	test( 'jQuery.eachchange() on a jQuery set of two input elements', function() {
		var subject = $( '<input/>', {
			'type': 'text',
			'name': 'test1',
			'value': ''
		} ).add( $( '<input/>', {
			'type': 'text',
			'name': 'test2',
			'value': ''
		} ) ); // should always be ignored, otherwise some tests will fail.

		equal(
			subject.eachchange( this.iIncr ),
			subject,
			'"eachchange" initialized, returns the original jQuery object'
		);

		this.iTriggerTest(
			subject,
			2,
			'"eachchange" triggered, eachchange() was used on two objects at the same time.'
		);
	} );

}() );
