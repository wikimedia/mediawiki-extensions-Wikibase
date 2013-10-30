/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * Toolbar widget base
	 * @since 0.4
	 *
	 * This widget offers basic functionality shared by all toolbar widgets.
	 */
	$.widget( 'wikibase.toolbarbase', {
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
			var $toolbarDom = mw.template( 'wikibase-toolbar', this.widgetBaseClass, '' );
			this.$toolbarParent = $toolbarDom.appendTo( this.element );
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
		}

	} );

}( mediaWiki, wikibase, jQuery ) );
