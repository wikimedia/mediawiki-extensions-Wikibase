/**
 * @since 0.1
 * @file
 * @ingroup Time.js
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( QUnit, $, time ) {
	'use strict';

	var Time = time.Time,
		validTimeDefinitions = time.validTimeDefinitions;

	QUnit.module( 'Time.js: time.Time' );

	QUnit.test( 'Construct using time definition object', function( assert ) {
		$.each( validTimeDefinitions, function( name, definition ) {
			testConstructByObject( assert, name, definition );
		} );
	} );

	function testConstructByObject( assert, definitionName, definition ) {
		var time,
			valid = true;
		try {
			var time = new Time( definition ); // throws an error if failure
		} catch( e ) {
			valid = false;
		}

		assert.ok(
			valid,
			'New time.Time object built from "' + definitionName + '" example definition'
		);
	}

	QUnit.test( 'Construct using string to be parsed', function( assert ) {
		$.each( validTimeDefinitions, function( name, definition ) {
			testConstructByString( assert, name, definition );
		} );
	} );

	function testConstructByString( assert, definitionName, definition ) {
		var time,
			valid = true;
		try {
			time = new Time( definitionName ); // throws an error if failure
		} catch( e ) {
			valid = false;
		}

		assert.ok(
			valid && time.isValid(),
			'New valid time.Time object built from "' + definitionName + '" example definition'
		);
	}

}( QUnit, jQuery, time ) );
