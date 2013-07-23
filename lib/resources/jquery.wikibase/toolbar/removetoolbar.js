/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.wikibase.toolbarbase;

	/**
	 * "Remove" toolbar widget
	 * @since 0.4
	 * @extends jQuery.wikibase.toolbarbase
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
	$.widget( 'wikibase.removetoolbar', PARENT, {
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

			PARENT.prototype._create.call( this );

			var $toolbar = mw.template( 'wikibase-toolbar', '', '' ).toolbar(),
				toolbar = this.toolbar = $toolbar.data( 'toolbar' );

			toolbar.$innerGroup = mw.template( 'wikibase-toolbar', '', '' ).toolbar( {
				renderItemSeparators: true
			} );

			toolbar.$btnRemove = mw.template(
				'wikibase-toolbarbutton',
				this.options.label,
				'javascript:void(0);'
			).toolbarbutton();

			toolbar.$innerGroup.data( 'toolbar' ).addElement( toolbar.$btnRemove );
			toolbar.addElement( toolbar.$innerGroup );

			$( toolbar.$btnRemove ).on( 'toolbarbuttonaction', this.options.action );

			$toolbar.appendTo(
				$( '<div/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbarParent )
			);
		}

	} );

}( mediaWiki, wikibase, jQuery ) );
