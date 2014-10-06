/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $ ) {
	'use strict';

	var PARENT = $.wikibase.toolbarbase;

	/**
	 * "Move" toolbar widget
	 * @since 0.4
	 * @extends jQuery.wikibase.toolbarbase
	 *
	 * This widget offers two buttons to move a referenced object within a list up or down. With the
	 * corresponding toolbar definition being initialized on a parent item, the movetoolbar itself
	 * needs to be initialized on every list item.
	 *
	 * @event up: Triggered when the "move up" button is hit.
	 *        (1) {jQuery.Event}
	 *
	 * @event down: Triggered when the "move down" button is hit.
	 *        (1) {jQuery.Event}
	 *
	 * @dependency jQuery
	 * @dependency jQuery.Widget
	 * @dependency jQuery.wikibase.toolbarbase
	 * @dependency jQuery.wikibase.toolbarbutton
	 * @dependency mediaWiki
	 * @dependency mediaWiki.template
	 */
	$.widget( 'wikibase.movetoolbar', PARENT, {
		/**
		 * "Move up" button.
		 * @type {jQuery}
		 */
		$btnMoveUp: null,

		/**
		 * "Move down" button.
		 * @type {jQuery}
		 */
		$btnMoveDown: null,

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var self = this;

			PARENT.prototype._create.call( this );

			var $toolbar = mw.template( 'wikibase-toolbar', '', '' ).toolbar();
			this.toolbar = $toolbar.data( 'toolbar' );

			this.$btnMoveUp = this._initButton(
				mw.msg( 'wikibase-move-up' ),
				'ui-icon-triangle-1-n'
			);

			this.$btnMoveDown = this._initButton(
				mw.msg( 'wikibase-move-down' ),
				'ui-icon-triangle-1-s'
			);

			var btnMoveUp = this.$btnMoveUp.data( 'toolbarbutton' ),
				btnMoveDown = this.$btnMoveDown.data( 'toolbarbutton' );

			this.$btnMoveUp.on( 'toolbarbuttonaction.' + this.widgetName, function( event ) {
				if( !btnMoveUp.option( 'disabled' ) ) {
					self._trigger( 'up' );
				}
			} );

			this.$btnMoveDown.on( 'toolbarbuttonaction.' + this.widgetName, function( event ) {
				if( !btnMoveDown.option( 'disabled' ) ) {
					self._trigger( 'down' );
				}
			} );

			$toolbar.appendTo(
				$( '<div/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbarParent )
			);
		},

		/**
		 * @see jQuery.wikibase.toolbarbase.destroy
		 */
		destroy: function() {
			// toolbar's destroy() will tear down the buttons:
			PARENT.prototype.destroy.call( this );
		},

		/**
		 * Initializes a toolbar button and adds it to the toolbar.
		 * @since 0.4
		 *
		 * @param {string} title
		 * @param {string} iconClass
		 * @return {jQuery}
		 */
		_initButton: function( title, iconClass ) {
			var $btn = mw.template(
				'wikibase-toolbarbutton',
				$( '<span/>', {
					title: title,
					'class': 'ui-icon ' + iconClass
				} ),
				'javascript:void(0);'
			).toolbarbutton();

			this.toolbar.addElement( $btn );

			return $btn;
		}

	} );

	// We have to override this here because $.widget sets it no matter what's in
	// the prototype
	$.wikibase.movetoolbar.prototype.widgetFullName = 'wb-movetoolbar';

}( mediaWiki, jQuery ) );
