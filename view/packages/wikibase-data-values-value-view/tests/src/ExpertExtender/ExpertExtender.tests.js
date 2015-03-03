/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
/* jshint nonew: false */
( function( $, ExpertExtender, sinon, QUnit, CompletenessTest ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender' );

	if( QUnit.urlParams.completenesstest ) {
		new CompletenessTest( ExpertExtender.prototype, function( cur, tester, path ) {
			return false;
		} );
	}

	QUnit.test( 'Constructor', function( assert ) {
		var expertExtender = new ExpertExtender( $( '<input/>' ), [] );

		assert.ok(
			expertExtender instanceof ExpertExtender,
			'Instantiated ExpertExtender.'
		);

		assert.notDeepEqual( expertExtender, ExpertExtender.prototype );
	} );

	QUnit.test( 'destroy cleans up properties', function( assert ) {
		var expertExtender = new ExpertExtender( $( '<input/>' ), [] );

		expertExtender.destroy();

		assert.deepEqual( expertExtender, ExpertExtender.prototype );
	} );

	QUnit.test( 'destroy calls extensions', function( assert ) {
		var destroy = sinon.spy(),
			expertExtender = new ExpertExtender( $( '<input/>' ), [ {
				destroy: destroy
			} ] );

		expertExtender.destroy();

		sinon.assert.calledOnce( destroy );
	} );

	QUnit.asyncTest( 'init calls extensions', function( assert ) {
		var $input = $( '<input/>' ).appendTo( 'body' ),
			init = sinon.spy(),
			onInitialShow = sinon.spy(),
			draw = sinon.spy(),
			expertExtender = new ExpertExtender( $input, [ {
				init: init,
				onInitialShow: onInitialShow,
				draw: draw
			} ] );

		$input.focus();

		// inputextender immediately extends if $input has focus
		expertExtender.init();

		window.setTimeout( function() {
			if( !init.called && 'hidden' in document && document.hidden ) {
				assert.ok( true, 'Could not test since browser window is not focused' );
			} else {
				sinon.assert.calledOnce( init );
				sinon.assert.calledOnce( onInitialShow );
				sinon.assert.calledOnce( draw );
			}

			$input.remove();

			QUnit.start();
		}, 0 );
	} );

} )( jQuery, jQuery.valueview.ExpertExtender, sinon, QUnit, CompletenessTest );
