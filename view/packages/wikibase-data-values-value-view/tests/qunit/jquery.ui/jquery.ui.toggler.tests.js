/**
 * @since 0.1
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit ) {
	'use strict';

	/**
	 * Factory for creating an toggler widget suitable for testing.
	 *
	 * @param {Object} options
	 * @return {jQuery.ui.toggler}
	 */
	var newTestToggler = function( options ) {
		var $defaultDiv = $( '<div/>' )
			.addClass( 'test_toggler-subject' )
			.css( 'display', 'none' )
			.text( 'test' )
			.appendTo( 'body' );

		options = $.extend( { $subject: $defaultDiv }, options );

		var $div = $( '<div/>' )
			.addClass( 'test_toggler' )
			.appendTo( $( 'body' ) )
			.toggler( options );

		return $div.data( 'toggler' );
	};

	QUnit.module( 'jquery.ui.toggler', QUnit.newMwEnvironment( {
		teardown: function() {
			$( '.test_toggler' ).each( function( i, node ) {
				if( $( node ).data( 'toggler' ) ) {
					$( node ).data( 'toggler' ).destroy();
				}
				$( node ).remove();
			} );
			$( '.test_toggler-subject' ).remove();
		}
	} ) );

	QUnit.test( 'Initialization and destruction', 3, function( assert ) {
		var toggler = newTestToggler();

		assert.equal(
			$( '.test_toggler' ).data( 'toggler' ),
			toggler,
			'Initialized widget.'
		);

		toggler.destroy();

		assert.ok(
			$( '.test_toggler' ).data( 'toggler' ) === undefined,
			'Destroyed widget.'
		);

		assert.equal(
			$( '.test_toggler-subject' ).length,
			1,
			'Toggler subject still exists.'
		);
	} );

}( jQuery, QUnit ) );
