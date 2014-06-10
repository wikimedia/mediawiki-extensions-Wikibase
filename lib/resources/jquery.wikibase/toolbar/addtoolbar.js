/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, $ ) {
	'use strict';

	var PARENT = $.wikibase.toolbarbase;

	/**
	 * "Add" toolbar widget
	 * @since 0.4
	 * @extends jQuery.wikibase.toolbarbase
	 *
	 * This widget offers an "add" button triggering a custom action when pressed.
	 *
	 * @option addButtonAction {Function} Callback to be triggered when the "add" button is pressed.
	 *
	 * @option addButtonLabel {string} The add button's label
	 *         Default value: mw.msg( 'wikibase-add' )
	 */
	$.widget( 'wikibase.addtoolbar', PARENT, {
		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			addButtonAction: null,
			addButtonLabel: mw.msg( 'wikibase-add' )
		},

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var self = this;

			PARENT.prototype._create.call( this );

			var $toolbar = mw.template( 'wikibase-toolbar', '', '' ).toolbar( {
				renderItemSeparators: true
			} );
			this.toolbar = $toolbar.data( 'toolbar' );
			this.toolbar.$innerGroup = mw.template( 'wikibase-toolbar', '', '' ).toolbar();
			this.toolbar.$btnAdd = mw.template(
				'wikibase-toolbarbutton',
				this.options.addButtonLabel,
				'javascript:void(0);'
			).toolbarbutton();
			this.toolbar.$btnAdd.addClass( this.widgetBaseClass + '-addbutton' );
			this.toolbar.$innerGroup.data( 'toolbar' ).addElement( this.toolbar.$btnAdd );
			this.toolbar.addElement( this.toolbar.$innerGroup );

			$( this.toolbar.$btnAdd ).on( 'toolbarbuttonaction', function( event ) {
				self.options.addButtonAction();
			} );

			$toolbar.appendTo(
				$( '<div/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbarParent )
			);
		}

	} );

	// We have to override this here because $.widget sets it no matter what's in
	// the prototype
	$.wikibase.addtoolbar.prototype.widgetBaseClass = 'wb-addtoolbar';

}( mediaWiki, jQuery ) );
