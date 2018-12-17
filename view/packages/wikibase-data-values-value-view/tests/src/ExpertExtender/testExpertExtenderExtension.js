/**
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( $, valueview, QUnit ) {
	'use strict';

	valueview.tests = valueview.tests || {};
	valueview.tests.testExpertExtenderExtension = {
		all: function( constructor, getInstance ) {
			this.constructor( constructor, getInstance );
			this.destroy( constructor, getInstance );
			this.init( getInstance );
		},

		constructor: function( constructor, getInstance ) {
			QUnit.test( 'Constructor', function( assert ) {
				var instance = getInstance();

				assert.ok(
					instance instanceof constructor,
					'Instantiated.'
				);

				assert.notDeepEqual( instance, constructor.prototype );

				instance.destroy();
			} );
		},

		destroy: function( constructor, getInstance ) {
			QUnit.test( 'destroy cleans up properties', function( assert ) {
				var instance = getInstance();

				instance.destroy();

				assert.deepEqual( instance, constructor.prototype );
			} );
		},

		init: function( getInstance ) {
			QUnit.test( 'init appends an element', function( assert ) {
				var instance = getInstance(),
					$extender = $( '<div />' );

				instance.init( $extender );

				assert.notEqual( $extender.children().length, 0 );

				instance.destroy();
			} );
		}
	};

} )( jQuery, jQuery.valueview, QUnit );
