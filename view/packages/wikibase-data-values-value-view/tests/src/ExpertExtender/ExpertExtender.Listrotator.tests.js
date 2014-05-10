/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */

( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit, CompletenessTest ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.Listrotator' );

	if( QUnit.urlParams.completenesstest ) {
		new CompletenessTest( ExpertExtender.Listrotator.prototype, function( cur, tester, path ) {
			return false;
		} );
	}

	testExpertExtenderExtension.constructor(
		ExpertExtender.Listrotator,
		new ExpertExtender.Listrotator( '', [ 'value' ] )
	);
	testExpertExtenderExtension.destroy(
		ExpertExtender.Listrotator,
		new ExpertExtender.Listrotator( '', [ 'value' ] )
	);
	testExpertExtenderExtension.init(
		new ExpertExtender.Listrotator( '', [ 'value' ] )
	);

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit,
	CompletenessTest
);
