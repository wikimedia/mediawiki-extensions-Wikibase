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
			var $toolbarDom = this.element.children( '.' + this.widgetBaseClass );
			if( $toolbarDom.length === 0 ) {
				$toolbarDom = mw.template( 'wikibase-toolbar', this.widgetBaseClass, '' );
				$toolbarDom.appendTo( this.element );
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
		}

	} );

}( mediaWiki, jQuery ) );
