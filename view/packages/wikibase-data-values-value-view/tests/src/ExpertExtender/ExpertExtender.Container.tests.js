/**
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.Container' );

	testExpertExtenderExtension.all(
		ExpertExtender.Container,
		function() {
			return new ExpertExtender.Container( $( '<div />' ), {} );
		}
	);

	QUnit.test( 'init calls child', function( assert ) {
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
	QUnit
);
