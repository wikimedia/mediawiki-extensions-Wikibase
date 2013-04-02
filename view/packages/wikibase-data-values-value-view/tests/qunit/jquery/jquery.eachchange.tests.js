/**
 * QUnit tests for 'eachchange' jQuery plugin
 *
 * @since 0.1
 * @file
 * @ingroup DataTypes
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( $, QUnit ) {
	'use strict';

	// some helper functions for the tests:
	var i = 0,
		iIncr = function() {
			i++;
		},
		iReset = function() {
			i = 0;
		},
		iTriggerTest = function( subject, expected, description ) {
			subject.filter( 'input' ).trigger( 'eachchange' );
			QUnit.assert.equal(
				i,
				expected,
				description
			);
		};

	QUnit.module( 'jquery.eachchange', QUnit.newMwEnvironment( {
		setup: function() {
			iReset();
		},
		teardown: function() {
			$( '.test_eachchange' ).remove();
		}
	} ) );

	QUnit.test(
		'Initialization',
		function( assert ) {
			var $input = $( '<input/>', { 'class': 'test_eachchange', type: 'text' } ),
				$inputNoType = $( '<input/>', { 'class': 'test_eachchange' } ),
				$textarea = $( '<textarea/>', { 'class': 'test_eachchange' } );

			assert.equal(
				$input.eachchange( iIncr ),
				$input,
				'Initialized "eachchange" on a text input element.'
			);

			assert.equal(
				$inputNoType.eachchange( iIncr ),
				$inputNoType,
				'Initialized "eachchange" on an input element that has no "type" attribute.'
			);

			assert.equal(
				$textarea.eachchange( iIncr ),
				$textarea,
				'Initialized "eachchange" on a textarea.'
			);
		}
	);

	QUnit.test( 'jQuery.eachchange() basics', function( assert ) {
		var subject = $( '<input/>', {
			'class': 'test_eachchange',
			'type': 'text',
			'name': 'test',
			'value': ''
		} ).add( $( '<div/>', { 'class': 'test_eachchange' } ) ); // should always be ignored, otherwise some tests will fail.

		assert.equal(
			subject.eachchange( iIncr ),
			subject,
			'"eachchange" initialized, returns the original jQuery object'
		);

		subject.eachchange( iIncr ); // assign second time

		iTriggerTest(
			subject,
			2,
			'"eachchange" triggered, eachchange() was used twice on same object but should only be triggered once each.'
		);

		iReset();
		subject.on( 'eachchange', iIncr );

		iTriggerTest(
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
			subject.eachchange( iIncr ),
			subject,
			'"eachchange" initialized, returns the original jQuery object'
		);

		iTriggerTest(
			subject,
			2,
			'"eachchange" triggered, eachchange() was used on two objects at the same time.'
		);
	} );

}( jQuery, QUnit ) );
