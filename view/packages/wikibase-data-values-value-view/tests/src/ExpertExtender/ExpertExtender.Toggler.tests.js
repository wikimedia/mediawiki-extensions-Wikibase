/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, util, sinon, QUnit, CompletenessTest ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.Toggler' );

	if( QUnit.urlParams.completenesstest && CompletenessTest ) {
		new CompletenessTest( ExpertExtender.Toggler.prototype, function( cur, tester, path ) {
			return false;
		} );
	}

	testExpertExtenderExtension.all(
		ExpertExtender.Toggler,
		function() {
			return new ExpertExtender.Toggler( new util.HashMessageProvider( {} ), $( '<div />' ) );
		}
	);

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	util,
	sinon,
	QUnit,
	typeof CompletenessTest !== 'undefined' ? CompletenessTest : null
);
