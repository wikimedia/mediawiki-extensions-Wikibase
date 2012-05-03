/**
 * QUnit tests for tooltip component
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.Toolbar.Tooltip.tests.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';

( function() {
	module( 'wikibase.ui.Toolbar.Tooltip', {
		setup: function() {

			this.tooltip = new window.wikibase.ui.Toolbar.Tooltip( 'Text' );

			equal(
				typeof this.tooltip._elem,
				'object',
				'initialized tooltip'
			);

			ok(
				this.tooltip._elem.length == 1 && this.tooltip._elem.children().length == 1,
				'initilized DOM'
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

	} );

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
