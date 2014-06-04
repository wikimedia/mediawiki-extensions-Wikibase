/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */

( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit, CompletenessTest ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.Container' );

	if( QUnit.urlParams.completenesstest ) {
		new CompletenessTest( ExpertExtender.Container.prototype, function( cur, tester, path ) {
			return false;
		} );
	}

	testExpertExtenderExtension.constructor(
		ExpertExtender.Container,
		new ExpertExtender.Container( $( '<div />' ), {} )
	);
	testExpertExtenderExtension.destroy(
		ExpertExtender.Container,
		new ExpertExtender.Container( $( '<div />' ), {} )
	);
	testExpertExtenderExtension.init(
		new ExpertExtender.Container( $( '<div />' ), {} )
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
	QUnit,
	CompletenessTest
);
