/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	require( './jquery.wikibase.toolbar.js' );

	var PARENT = $.wikibase.toolbar;

	/**
	 * Toolbar by default featuring a single button.
	 *
	 * @extends jQuery.wikibase.toolbar
	 *
	 * @option {string} [label]
	 *         Default: ''
	 *
	 * @option {string} [title]
	 *         Default: ''
	 *
	 * @option {string} [eventName]
	 *         Default: 'action'
	 *
	 * @option {string} [buttonCssClassSuffix]
	 *         Default: null
	 *
	 * @event <custom name> (see options)
	 *        Triggered when the default button is hit.
	 *        - {jQuery.Event}
	 */
	$.widget( 'wikibase.singlebuttontoolbar', PARENT, {
		/**
		 * @see jQuery.wikibase.toolbar.options
		 */
		options: {
			label: '',
			title: '',
			eventName: 'action',
			buttonCssClassSuffix: null
		},

		/**
		 * @see jQuery.wikibase.toolbar._create
		 */
		_create: function () {
			PARENT.prototype._create.call( this );

			if ( !this.options.$content.length ) {
				var $scrapedButton = this._scrapeButton();
				this.options.$content = this._initDefaultButton( $scrapedButton );
				if ( !$scrapedButton ) {
					this.draw();
				}
			}
		},

		/**
		 * @param {jQuery|null} $scrapedButton
		 * @return {jQuery}
		 */
		_initDefaultButton: function ( $scrapedButton ) {
			var self = this,
				$defaultButton = $scrapedButton || $( '<span>' );

			return $defaultButton.toolbarbutton( {
				$label: this.options.label,
				title: this.options.title,
				cssClassSuffix: this.options.buttonCssClassSuffix
			} )
			.on( 'toolbarbuttonaction.' + this.widgetName, function ( event ) {
				self._trigger( self.options.eventName );
			} );
		},

		/**
		 * @return {jQuery}
		 */
		_scrapeButton: function () {
			var self = this,
				$defaultButton = null;

			this.getContainer().children( '.wikibase-toolbar-button' ).each( function () {
				var $button = $( this );
				if ( $button.text() === self.options.label ) {
					$defaultButton = $button;
					return false;
				}
			} );

			return $defaultButton;
		},

		/**
		 * @see jQuery.wikibase.toolbaritem.focus
		 */
		focus: function () {
			var button = this.options.$content.first().data( 'toolbarbutton' );
			if ( button ) {
				button.focus();
			} else {
				this.element.trigger( 'focus' );
			}
		}
	} );

}() );
