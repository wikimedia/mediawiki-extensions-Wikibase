/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	require( './jquery.wikibase.toolbaritem.js' );

	var PARENT = $.wikibase.toolbaritem;

	/**
	 * Represents a button to be used in a jQuery.wikibase.toolbar.
	 *
	 * @extends jQuery.wikibase.toolbaritem
	 *
	 * @option {string} [cssClassSuffix]
	 *         If set, another css class "wikibase-toolbar-button-<cssClassSuffix>" is added to the
	 *         button's root node.
	 *         Default: null
	 *
	 * @event action
	 *        Triggered when the button is hit while not being disabled.
	 *        - {jQuery.Event}
	 */
	$.widget( 'wikibase.toolbarbutton', PARENT, {
		/**
		 * @see jQuery.wikibase.toolbaritem.options
		 */
		options: {
			template: 'wikibase-toolbar-button',
			templateParams: [
				'', // CSS class names
				'#', // URL
				'', // Label
				'' // Title tooltip
			],
			templateShortCuts: {
				$link: 'a'
			},
			$label: null,
			title: '',
			cssClassSuffix: null
		},

		/**
		 * @see jQuery.wikibase.toolbaritem._create
		 */
		_create: function () {
			PARENT.prototype._create.call( this );

			var self = this;

			if ( this.options.cssClassSuffix ) {
				this.element.addClass( 'wikibase-toolbar-button-' + this.options.cssClassSuffix );
			}

			if ( this.$link.contents().text() === '' ) {
				this.$link.append( this._getLabel() );
			}

			if ( this.options.title ) {
				this.$link.prop( 'title', this.options.title );
			}

			this.$link
			.on( 'click.toolbarbutton keydown.toolbarbutton', function ( event ) {
				if ( event.type === 'click' || event.keyCode === $.ui.keyCode.ENTER ) {
					event.preventDefault();

					if ( !self.options.disabled ) {
						self._trigger( 'action' );
					}
				}
			} );
		},

		/**
		 * @see jQuery.wikibase.toolbaritem.destroy
		 */
		destroy: function () {
			this.$link.off( '.toolbarbutton' );
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @see jQuery.wikibase.toolbaritem._setOption
		 */
		_setOption: function ( key, value ) {
			if ( key === 'cssClassSuffix' ) {
				if ( this.options.cssClassSuffix ) {
					this.element
					.removeClass( 'wikibase-toolbar-button-' + this.options.cssClassSuffix );
				}
				this.element.addClass( 'wikibase-toolbar-button-' + value );
			} else if ( key === 'disabled' ) {
				if ( value ) {
					this.$link.attr( 'tabIndex', '-1' );
				} else {
					this.$link.removeAttr( 'tabIndex' );
				}
			}
			return PARENT.prototype._setOption.apply( this, arguments );
		},

		/**
		 * @return {jQuery}
		 */
		_getLabel: function () {
			return typeof this.options.$label === 'string'
				? $( document.createTextNode( this.options.$label ) )
				: this.options.$label;
		},

		/**
		 * @see jQuery.wikibase.toolbaritem.focus
		 */
		focus: function () {
			this.$link.trigger( 'focus' );
		},

		/**
		 * Main drawing routine.
		 */
		draw: function () {}
	} );

}() );
