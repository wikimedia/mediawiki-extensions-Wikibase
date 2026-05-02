/**
 * @param QUnit
 * @param valueview
 * @param $
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit, valueview, $ ) {
	'use strict';

	const testExpert = valueview.tests.testExpert;

	QUnit.module( 'jquery.valueview.experts.GlobeCoordinateInput' );

	testExpert( {
		expertConstructor: valueview.experts.GlobeCoordinateInput
	} );

	QUnit.test( 'don\'t crash with null precision', ( assert ) => {
		const $div = $( '<div/>' ).appendTo( 'body' );
		const expert = new valueview.experts.GlobeCoordinateInput(
			$div,
			new valueview.tests.MockViewState( {
				value: {
					getValue: function() {
						return {
							getPrecision: function() {
								return null;
							}
						};
					}
				},
				getTextValue: 'value'
			} )
		);
		const done = assert.async();
		expert.init();
		expert.draw();
		expert.focus();
		window.setTimeout( () => {
			assert.ok( true );
			$div.remove();
			done();
		}, 300 );
	} );

}( QUnit, jQuery.valueview, jQuery ) );
