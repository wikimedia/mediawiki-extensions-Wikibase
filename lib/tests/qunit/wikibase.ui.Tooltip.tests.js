/**
 * QUnit tests for tooltip component
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater <mediawiki@snater.com>
 */
'use strict';

( function() {
	module( 'wikibase.ui.Tooltip', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.node = $( '<div/>' );
			this.label = $( '<span/>' ).wblabel( { content: 'Text' } );
		},
		teardown: function() {
			$( window ).off( 'click' );
			$( window ).off( 'resize' );
			this.tooltip = null;
			this.label.data( 'wblabel' ).destroy();
			this.node = null;
		}

	} ) );

	test( 'show and hide basic tooltip', function() {
		this.tooltip = new window.wikibase.ui.Tooltip( this.node, {}, 'Text' );

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

		this.tooltip.destroy();

		equal(
			this.tooltip._elem,
			null,
			'destroyed tooltip'
		);

	} );


	test( 'show and hide by triggering events', function() {
		this.label.data( 'wblabel' ).setTooltip( 'Text' );

		equal(
			this.label.data( 'wblabel' )._tooltip._isVisible,
			false,
			'tooltip is hidden'
		);

		this.label.trigger( 'mouseover' );

		equal(
			this.label.data( 'wblabel' )._tooltip._isVisible,
			true,
			'tooltip is visible when mouseover event is triggered'
		);

		this.label.trigger( 'mouseout' );

		equal(
			this.label.data( 'wblabel' )._tooltip._isVisible,
			false,
			'tooltip is hidden when mouseout event is triggered'
		);

		this.label.trigger( 'mouseover' );

		equal(
			this.label.data( 'wblabel' )._tooltip._isVisible,
			true,
			'tooltip is visible when mouseover event is triggered (second time)'
		);

	} );

	test( 'show and hide error tooltip', function() {
		var error = new wb.RepoApiError( 'error-code', 'detailed message' );

		this.tooltip = new window.wikibase.ui.Tooltip( this.node, {}, error, {}, this.label );
		this.label.data( 'wblabel' ).setTooltip( this.tooltip );

		ok(
			this.tooltip._error != null,
			'is an error tooltip'
		);

		this.tooltip.show( true );

		equal(
			typeof this.tooltip._DomContent,
			'object',
			'constructed DOM content'
		);

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

		$( window ).trigger( 'resize' );

		equal(
			this.tooltip._isVisible,
			true,
			'tooltip still visible after triggering window resize event'
		);

		$( window ).trigger( 'mousedown' );

		equal(
			this.tooltip._isVisible,
			false,
			'tooltip hidden after triggering window click event'
		);

		this.label.data( 'wblabel' ).removeTooltip();

		equal(
			this.tooltip._elem,
			null,
			'destroyed tooltip'
		);

	} );


}() );
