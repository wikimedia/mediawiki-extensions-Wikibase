/**
 * @file
 * @ingroup WikibaseLib
 *
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
	 *
	 * @option toolbarParentSelector {string} jQuery selector to find the node the toolbar DOM
	 *         structure with its buttons shall be appended to. If omitted, the DOM structure
	 *         required for the toolbar will be appended to the node the toolbar is initialized on.
	 *         Default: null (this.element will be used)
	 */
	$.widget( 'wikibase.toolbarbase', {
		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			toolbarParentSelector: null
		},

		/**
		 * The toolbar object.
		 * @type {wb.ui.Toolbar}
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
			var $toolbarDom = mw.template( 'wb-toolbar', this.widgetBaseClass, '' );
			this.$toolbarParent = $toolbarDom.appendTo(
				( this.options.toolbarParentSelector ) ?
					this.element.find( this.options.toolbarParentSelector )
					: this.element
			);
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
