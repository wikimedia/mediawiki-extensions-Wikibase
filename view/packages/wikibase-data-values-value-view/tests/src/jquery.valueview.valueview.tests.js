/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */

( function( vv, QUnit ) {
	'use strict';

	var $vvElem;
	var vvInst;

	QUnit.module( 'jquery.valueview.valueview' );

	function initVv( opts ) {
		opts = opts || {};

		$vvElem = jQuery( '<div/>' );
		vvInst = $vvElem.valueview().data( 'valueview' );

		if( opts.withExpert ) {
			vvInst.startEditing();
			vvInst.draw();
		}
	}

	QUnit.test( 'Constructor', function( assert ) {
		initVv();

		assert.ok(
			vvInst instanceof vv,
			'Instantiated ValueView.'
		);

		assert.ok( $vvElem.hasClass( vvInst.widgetBaseClass ) );
	} );

	QUnit.test( 'destroy', function( assert ) {
		initVv();

		vvInst.destroy();

		assert.ok( !$vvElem.hasClass( vvInst.widgetBaseClass ) );
	} );

	QUnit.test( 'destroy with expert', function( assert ) {
		initVv( { withExpert: true } );

		vvInst.destroy();

		assert.ok( !vvInst.expert() );
	} );

} )( jQuery.valueview, QUnit );
