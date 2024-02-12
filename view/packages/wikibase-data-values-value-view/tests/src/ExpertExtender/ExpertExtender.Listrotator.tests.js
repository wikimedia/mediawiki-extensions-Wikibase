/**
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender.Listrotator' );

	const messageProvider = {
		getMessage: function() { }
	};

	testExpertExtenderExtension.all(
		ExpertExtender.Listrotator,
		function() {
			return new ExpertExtender.Listrotator( '', [ { value: 'value', label: 'label' } ], null, null, messageProvider );
		}
	);

	QUnit.test( 'supports custom values', function( assert ) {
		const getUpstreamValue = function() {
			return {
				custom: true,
				value: 'custom value',
				label: 'label for custom value'
			};
		};
		const $extender = $( '<div />' );

		const listrotator = new ExpertExtender.Listrotator(
			'',
			[ { value: 'fixed value', label: 'label for fixed value' } ],
			null,
			getUpstreamValue,
			messageProvider
		);

		listrotator.init( $extender );
		listrotator.draw();

		assert.strictEqual( listrotator.getValue(), 'custom value' );
	} );

	QUnit.test( 'supports switching away from custom values', function( assert ) {
		const done = assert.async();
		const onValueChange = sinon.spy();
		const upstreamValue = {
			custom: true,
			value: 'custom value',
			label: 'label for custom value'
		};
		const getUpstreamValue = function() {
			return upstreamValue;
		};
		const $extender = $( '<div />' );

		const listrotator = new ExpertExtender.Listrotator(
			'',
			[ { value: 'fixed value', label: 'label for fixed value' } ],
			onValueChange,
			getUpstreamValue,
			messageProvider
		);

		listrotator.init( $extender );
		listrotator.draw();
		listrotator.rotator._setValue( 'fixed value' );

		setTimeout( function() {
			sinon.assert.calledOnce( onValueChange );
			assert.strictEqual( listrotator.getValue(), 'fixed value' );

			done();
		}, 200 );

	} );

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit
);
