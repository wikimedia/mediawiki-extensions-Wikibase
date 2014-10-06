/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $ ) {
	'use strict';

	/**
	 * Toolbar widget base
	 * @since 0.4
	 *
	 * This widget offers basic functionality shared by all toolbar widgets.
	 *
	 * @option {jQuery} [$container]
	 *         Node the toolbar DOM shall be appended to. If omitted, the toolbar DOM will be
	 *         appended to the element, the toolbar widget is initialized on.
	 */
	$.widget( 'wikibase.toolbarbase', {
		/**
		 * @see jQuery.ui.Widget.options
		 */
		options: {
			$container: null
		},

		/**
		 * The toolbar widget.
		 * @type {jQuery.wikibase.toolbar}
		 */
		toolbar: null,

		/**
		 * The toolbar's parent node.
		 * @type {jQuery}
		 */
		$toolbarParent: null,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var $toolbarDom = this.element.children( '.' + this.widgetBaseClass );
			if( $toolbarDom.length === 0 ) {
				$toolbarDom = mw.template( 'wikibase-toolbar', this.widgetBaseClass, '' );
				$toolbarDom.appendTo( this.option( '$container' ) || this.element );
			}
			this.$toolbarParent = $toolbarDom;
		},

		/**
		 * @see $.widget.destroy
		 */
		destroy: function() {
			if ( this.toolbar ) {
				this.toolbar.destroy();
			}
			$.Widget.prototype.destroy.call( this );
			this.$toolbarParent.remove();
		},

		/**
		 * @see jQuery.Widget.disable
		 */
		disable: function() {
			return this._setState( 'disable' );
		},

		/**
		 * @see jQuery.Widget.enable
		 */
		enable: function() {
			return this._setState( 'enable' );
		},

		/**
		 * @param {string} state
		 */
		_setState: function( state ) {
			this.toolbar[state]();
			return $.Widget.prototype[state].call( this );
		}

	} );

}( mediaWiki, jQuery ) );
