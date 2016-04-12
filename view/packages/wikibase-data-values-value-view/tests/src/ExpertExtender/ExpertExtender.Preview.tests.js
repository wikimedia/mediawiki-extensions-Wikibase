/**
 * @licence GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit, CompletenessTest ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.Preview' );

	if ( QUnit.urlParams.completenesstest && CompletenessTest ) {
		new CompletenessTest( ExpertExtender.Preview.prototype, function( cur, tester, path ) {
			return false;
		} );
	}

	testExpertExtenderExtension.all(
		ExpertExtender.Preview,
		function() {
			return new ExpertExtender.Preview( null, {
				getMessage: function() { }
			} );
		}
	);

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit,
	typeof CompletenessTest !== 'undefined' ? CompletenessTest : null
);
