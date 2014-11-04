/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit, CompletenessTest ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.Preview' );

	if( QUnit.urlParams.completenesstest ) {
		new CompletenessTest( ExpertExtender.Preview.prototype, function( cur, tester, path ) {
			return false;
		} );
	}

	testExpertExtenderExtension.constructor(
		ExpertExtender.Preview,
		new ExpertExtender.Preview( null )
	);
	testExpertExtenderExtension.destroy(
		ExpertExtender.Preview,
		new ExpertExtender.Preview( null )
	);
	testExpertExtenderExtension.init(
		new ExpertExtender.Preview( null )
	);

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit,
	CompletenessTest
);
