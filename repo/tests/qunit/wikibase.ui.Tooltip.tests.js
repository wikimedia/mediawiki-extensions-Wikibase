/**
 * QUnit tests for tooltip component
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.Tooltip.tests.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';

( function() {
	module( 'wikibase.ui.Tooltip', window.QUnit.newWbEnvironment( null, null, {
		setup: function() {

			this.node = $( '<div/>' );
			this.tooltip = new window.wikibase.ui.Tooltip( this.node, 'Text' );

			equal(
				this.tooltip._subject[0],
				this.node[0],
				'initialized tooltip'
			);

			equal(
				typeof this.tooltip._tipsy,
				'object',
				'created tipsy object'
			);

		},
		teardown: function() {
			this.tooltip.destroy();

			equal(
				this.tooltip._elem,
				null,
				'destroyed tooltip'
			);
		}

	} ) );

	test( 'show and hide', function() {

		equal(
			this.tooltip._isVisible,
			false,
			'tooltip is hidden'
		);

		equal(
			this.tooltip._permanent,
			false,
			'tooltip reacts on hover'
		);

		this.tooltip.show();

		equal(
			this.tooltip._isVisible,
			true,
			'tooltip is visible'
		);

		equal(
			this.tooltip._permanent,
			false,
			'tooltip reacts on hover'
		);

		this.tooltip.hide();

		equal(
			this.tooltip._isVisible,
			false,
			'tooltip is hidden'
		);

		equal(
			this.tooltip._permanent,
			false,
			'tooltip reacts on hover'
		);

		this.tooltip.show( true );

		equal(
			this.tooltip._isVisible,
			true,
			'tooltip is visible'
		);

		equal(
			this.tooltip._permanent,
			true,
			'tooltip does not react on hover'
		);

		this.tooltip.hide();

		equal(
			this.tooltip._isVisible,
			false,
			'tooltip is hidden'
		);

		equal(
			this.tooltip._permanent,
			false,
			'tooltip reacts on hover'
		);

	} );


}() );
