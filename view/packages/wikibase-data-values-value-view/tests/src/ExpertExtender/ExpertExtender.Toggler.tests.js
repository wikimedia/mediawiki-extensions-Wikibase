/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */

( function( $, ExpertExtender, testExpertExtenderExtension, util, sinon, QUnit, CompletenessTest ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.Toggler' );

	if( QUnit.urlParams.completenesstest ) {
		new CompletenessTest( ExpertExtender.Toggler.prototype, function( cur, tester, path ) {
			return false;
		} );
	}

	testExpertExtenderExtension.constructor(
		ExpertExtender.Toggler,
		new ExpertExtender.Toggler( new util.MessageProvider(), $( '<div />' ) )
	);
	testExpertExtenderExtension.destroy(
		ExpertExtender.Toggler,
		new ExpertExtender.Toggler( new util.MessageProvider(), $( '<div />' ) )
	);
	testExpertExtenderExtension.init(
		new ExpertExtender.Toggler( new util.MessageProvider(), $( '<div />' ) )
	);

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	util,
	sinon,
	QUnit,
	CompletenessTest
);
