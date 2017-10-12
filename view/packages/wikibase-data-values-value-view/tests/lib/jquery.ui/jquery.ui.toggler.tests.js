/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, QUnit ) {
	'use strict';

	/**
	 * @param {Object} [options={}]
	 * @return {jQuery.ui.toggler}
	 */
	var newTestToggler = function( options ) {
		var $defaultDiv = $( '<div/>' )
			.addClass( 'test_toggler-subject' )
			.css( 'display', 'none' )
			.text( 'test' )
			.appendTo( 'body' );

		options = $.extend( { $subject: $defaultDiv }, options || {} );

		var $div = $( '<div/>' )
			.addClass( 'test_toggler' )
			.appendTo( $( 'body' ) )
			.toggler( options );

		return $div.data( 'toggler' );
	};

	QUnit.module( 'jquery.ui.toggler', {
		afterEach: function() {
			$( '.test_toggler' ).each( function( i, node ) {
				if ( $( node ).data( 'toggler' ) ) {
					$( node ).data( 'toggler' ).destroy();
				}
				$( node ).remove();
			} );
			$( '.test_toggler-subject' ).remove();
		}
	} );

	QUnit.test( 'Initialization and destruction', function( assert ) {
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

	QUnit.test( 'Toggle toggler', function( assert ) {
		var toggler = newTestToggler();

		assert.equal(
				toggler.isCollapsed(),
				true,
				'Toggler is initially collapsed'
		);

		toggler.toggle();

		assert.equal(
				toggler.isCollapsed(),
				false,
				'Toggler is expanded after toggle'
		);
	} );

}( jQuery, QUnit ) );
