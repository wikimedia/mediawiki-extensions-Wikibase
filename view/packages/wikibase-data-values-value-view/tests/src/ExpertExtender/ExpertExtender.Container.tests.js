/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit, CompletenessTest ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.Container' );

	if ( QUnit.urlParams.completenesstest && CompletenessTest ) {
		new CompletenessTest( ExpertExtender.Container.prototype, function( cur, tester, path ) {
			return false;
		} );
	}

	testExpertExtenderExtension.all(
		ExpertExtender.Container,
		function() {
			return new ExpertExtender.Container( $( '<div />' ), {} );
		}
	);

	QUnit.test( 'init calls child', function( assert ) {
		assert.expect( 2 );
		var $container = $( '<div />' );
		var child = {
			init: sinon.spy()
		};
		var container = new ExpertExtender.Container( $container, child );

		container.init( $( '<div />' ) );

		sinon.assert.calledOnce( child.init );
		sinon.assert.calledWith( child.init, $container );
	} );

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit,
	typeof CompletenessTest !== 'undefined' ? CompletenessTest : null
);
