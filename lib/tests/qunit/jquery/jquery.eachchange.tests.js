/**
 * QUnit tests for eachchange jQuery plugin
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'jquery.eachchange', QUnit.newWbEnvironment( {
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
				QUnit.assert.equal(
					self.i,
					expected,
					description
				);
			}
		},
		teardown: function() {
			$( '.test_eachchange' ).remove();
		}
	} ) );

	QUnit.test( 'jQuery.eachchange() basics', function( assert ) {
		var subject = $( '<input/>', {
			'class': 'test_eachchange',
			'type': 'text',
			'name': 'test',
			'value': ''
		} ).add( $( '<div/>', { 'class': 'test_eachchange' } ) ); // should always be ignored, otherwise some tests will fail.

		assert.equal(
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

	QUnit.test( 'jQuery.eachchange() on a jQuery set of two input elements', function( assert ) {
		var subject = $( '<input/>', {
			'class': 'test_eachchange',
			'type': 'text',
			'name': 'test1',
			'value': ''
		} ).add( $( '<input/>', {
			'class': 'test_eachchange',
			'type': 'text',
			'name': 'test2',
			'value': ''
		} ) ); // should always be ignored, otherwise some tests will fail.

		assert.equal(
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

}( jQuery, QUnit ) );
