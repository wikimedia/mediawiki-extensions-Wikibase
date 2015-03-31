/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
/* jshint nonew: false */
( function(
	$,
	ExpertExtender,
	testExpertExtenderExtension,
	Time,
	HashMessageProvider,
	sinon,
	QUnit,
	CompletenessTest
) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.CalendarHint' );

	if( QUnit.urlParams.completenesstest && CompletenessTest ) {
		new CompletenessTest( ExpertExtender.CalendarHint.prototype, function( cur, tester, path ) {
			return false;
		} );
	}

	testExpertExtenderExtension.all(
		ExpertExtender.CalendarHint,
		function() {
			return new ExpertExtender.CalendarHint();
		}
	);

	QUnit.test( 'calendarhint is hidden if it should not be shown', function( assert ) {
		var calendarHint = new ExpertExtender.CalendarHint(
			new HashMessageProvider( {
				'valueview-expertextender-calendarhint-gregorian': 'MSG1',
				'valueview-expertextender-calendarhint-switch-julian': 'MSG2'
			} ),
			function() {
				return new time.Time( '2014-01-01' );
			},
			null
		);
		var $extender = $( '<div />' ).appendTo( 'body' ) ;

		calendarHint.init( $extender );
		calendarHint.draw();

		assert.assertFalse( $extender.children().is( ':visible' ) );

		$extender.remove();
	} );

	QUnit.test( 'calendarhint is visible if it should be shown', function( assert ) {
		var calendarHint = new ExpertExtender.CalendarHint(
			new HashMessageProvider( {
				'valueview-expertextender-calendarhint-gregorian': 'MSG1',
				'valueview-expertextender-calendarhint-switch-julian': 'MSG2'
			} ),
			function() {
				return new Time( '1901-01-01' );
			},
			null
		);
		var $extender = $( '<div />' ).appendTo( 'body' ) ;

		calendarHint.init( $extender );
		calendarHint.draw();

		assert.assertTrue( $extender.children().is( ':visible' ) );

		$extender.remove();
	} );

	QUnit.test( 'switch switches the calendar model', function( assert ) {
		var setSpy = sinon.spy();
		var timeValue = new Time( '1901-01-01' );
		var calendarHint = new ExpertExtender.CalendarHint(
			new HashMessageProvider( {
				'valueview-expertextender-calendarhint-gregorian': 'MSG1',
				'valueview-expertextender-calendarhint-switch-julian': 'MSG2'
			} ),
			function() {
				return timeValue;
			},
			setSpy
		);
		var $extender = $( '<div />' ).appendTo( 'body' ) ;

		assert.equal( timeValue.calendar(), 'Gregorian' );

		calendarHint.init( $extender );
		calendarHint.draw();

		$( '.valueview-expertextender-calendarhint-switch', $extender[0] ).click();

		sinon.assert.calledOnce( setSpy );
		assert.equal( setSpy.firstCall.args[0], 'Julian' );

		$extender.remove();
	} );

	QUnit.test( 'switch twice switches the calendar model back', function( assert ) {
		var setSpy = sinon.spy();
		var timeValue = new Time( '1901-01-01' );
		var calendarHint = new ExpertExtender.CalendarHint(
			new HashMessageProvider( {
				'valueview-expertextender-calendarhint-gregorian': 'MSG1',
				'valueview-expertextender-calendarhint-julian': 'MSG2',
				'valueview-expertextender-calendarhint-switch-julian': 'MSG3',
				'valueview-expertextender-calendarhint-switch-gregorian': 'MSG4'
			} ),
			function() {
				return timeValue;
			},
			setSpy
		);
		var $extender = $( '<div />' ).appendTo( 'body' ) ;

		calendarHint.init( $extender );
		calendarHint.draw();

		$( '.valueview-expertextender-calendarhint-switch', $extender[0] ).click();

		sinon.assert.calledOnce( setSpy );
		assert.equal( setSpy.firstCall.args[0], 'Julian' );

		timeValue = new Time( {
			year: timeValue.year(),
			month: timeValue.month(),
			day: timeValue.day(),
			calendarname: Time.CALENDAR.JULIAN,
			precision: timeValue.precision()
		} );
		calendarHint.draw();

		$( '.valueview-expertextender-calendarhint-switch', $extender[0] ).click();

		sinon.assert.calledTwice( setSpy );
		assert.equal( setSpy.secondCall.args[0], 'Gregorian' );

		$extender.remove();
	} );

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	time.Time,
	util.HashMessageProvider,
	sinon,
	QUnit,
	typeof CompletenessTest !== 'undefined' ? CompletenessTest : null
);
