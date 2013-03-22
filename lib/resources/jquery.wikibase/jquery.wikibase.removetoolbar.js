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
	 * "Remove" toolbar widget
	 * @since 0.4
	 *
	 * This widget offers a "remove" link which will allow interaction according to the action
	 * specified in the options.
	 *
	 * @option action {function} (REQUIRED) Custom action the "remove" button shall trigger. The
	 *         The function receives the following parameter:
	 *         (1) {jQuery.Event} "Remove" button's action event
	 *
	 * @option label {string} The "remove" button's label
	 *         Default value: mw.msg( 'wikibase-remove' )
	 */
	$.widget( 'wikibase.removetoolbar', {
		widgetBaseClass: 'wb-removetoolbar',

		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			action: null,
			label: mw.msg( 'wikibase-remove' )
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
			if ( !$.isFunction( this.options.action ) ) {
				throw new Error( 'jquery.wikibase.removetoolbar: action needs to be defined' );
			}

			this.$toolbarParent = mw.template( 'wb-toolbar-container', this.widgetBaseClass, '' )
				.appendTo( this.element );

			var toolbar = this.toolbar = new wb.ui.Toolbar();
			toolbar.innerGroup = new wb.ui.Toolbar.Group();
			toolbar.btnRemove = new wb.ui.Toolbar.Button( this.options.label );
			toolbar.innerGroup.addElement( toolbar.btnRemove );
			toolbar.addElement( toolbar.innerGroup );

			$( toolbar.btnRemove ).on( 'action', this.options.action );

			toolbar.appendTo(
				$( '<div/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbarParent )
			);
		},

		/**
		 * @see $.widget.destroy
		 */
		destroy: function() {
			this.toolbar.destroy();
			$.Widget.prototype.destroy.call( this );
			this.$toolbarParent.remove();
		}

	} );

}( mediaWiki, wikibase, jQuery ) );
