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
			this.label = new window.wikibase.ui.Toolbar.Label( 'Text' );
			this.error = {
				code: 'error-code',
				shortMessage: 'Text',
				message: 'Text'
			};
		},
		teardown: function() {
			this.tooltip.destroy();

			equal(
				this.tooltip._elem,
				null,
				'destroyed tooltip'
			);

			this.label.destroy();
			this.error = null;
			this.node = null;
		}

	} ) );

	test( 'show and hide basic tooltip', function() {
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

		this.tooltip.showMessage();

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

		this.tooltip.hideMessage();

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

		this.tooltip.showMessage( true );

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

		this.tooltip.hideMessage();

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

	test( 'show and hide error tooltip', function() {
		this.tooltip = new window.wikibase.ui.Tooltip( this.node, this.error, {}, this.label );
		this.label.addTooltip( this.tooltip );

		equal(
			this.tooltip._isError,
			true,
			'is an error tooltip'
		);

		equal(
			typeof this.tooltip._DomContent,
			'object',
			'constructed DOM content'
		);

		this.tooltip.showMessage( true );

		equal(
			this.tooltip._isVisible,
			true,
			'tooltip is visible'
		);

		this.tooltip._tipsy.$tip.trigger( 'click' );

		equal(
			this.tooltip._isVisible,
			true,
			'tooltip still visible after clicking on it'
		);

		$( window ).trigger( 'click' );

		equal(
			this.tooltip._isVisible,
			false,
			'tooltip hidden after triggering window click event'
		);

	} );


}() );
