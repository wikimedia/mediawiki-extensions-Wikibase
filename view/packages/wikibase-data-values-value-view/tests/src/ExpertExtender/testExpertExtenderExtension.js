/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( $, valueview, QUnit ) {
	'use strict';

	valueview.tests = valueview.tests || {};
	valueview.tests.testExpertExtenderExtension = {
		constructor: function( constructor, instance ) {
			QUnit.test( 'Constructor', function( assert ) {
				assert.ok(
					instance instanceof constructor,
					'Instantiated.'
				);

				assert.notDeepEqual( instance, constructor.prototype );
			} );
		},

		destroy: function( constructor, instance ) {
			QUnit.test( 'destroy cleans up properties', function( assert ) {
				instance.destroy();

				assert.deepEqual( instance, constructor.prototype );
			} );
		},

		init: function( instance ) {
			QUnit.test( 'init appends an element', function( assert ) {
				var $extender = $( '<div />' );

				instance.init( $extender );

				assert.notEqual( $extender.children().length, 0 );
			} );
		}
	};

} )( jQuery, jQuery.valueview, QUnit );
