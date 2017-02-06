/**
 * @licence GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, util, sinon, QUnit ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.Toggler' );

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
	QUnit
);
