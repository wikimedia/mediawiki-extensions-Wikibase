/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit, valueview, $ ) {
	'use strict';

	var testExpert = valueview.tests.testExpert;

	QUnit.module( 'jquery.valueview.experts.GlobeCoordinateInput' );

	testExpert( {
		expertConstructor: valueview.experts.GlobeCoordinateInput
	} );

	QUnit.test( 'don\'t crash with null precision', function( assert ) {
		var $div = $( '<div/>' ).appendTo( 'body' );
		var expert = new valueview.experts.GlobeCoordinateInput(
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
		var done = assert.async();
		expert.init();
		expert.draw();
		expert.focus();
		window.setTimeout( function() {
			assert.ok( true );
			$div.remove();
			done();
		}, 300 );
	} );

}( QUnit, jQuery.valueview, jQuery ) );
