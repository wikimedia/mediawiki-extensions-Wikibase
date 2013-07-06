/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 *
 *
 * @event edit: Triggered when clicking (hitting enter on) the edit button.
 *        (1) {jQuery.Event}
 *        (2) {Function} Callback to be triggered after initial click action has been performed
 *            successfully ("old UI" exclusive - "new UI" uses toolbar controller for callback
 *            management).
 *
 * @event save: Triggered when clicking (hitting enter on) the save button.
 *        (1) {jQuery.Event}
 *        (2) {Function} Callback to be triggered after saving has been performed successfully
 *            ("old UI" exclusive).
 *
 * @event cancel: Triggered when clicking (hitting enter on) the cancel button.
 *        (1) {jQuery.Event}
 *        (2) {Function} Callback to be triggered after cancelling has been performed
 *            successfully ("old UI" exclusive).
 *
 * @event remove: Triggered when clicking (hitting enter on) the remove button.
 *        (1) {jQuery.Event}
 */
( function( mw, wb, $ ) {
'use strict';

var PARENT = $.wikibase.toolbar;

/**
 * Extends the basic toolbar group element with buttons essential for editing stuff.
 * Basically '[edit]' which gets expanded to '[cancel|save]' when hit.
 *
 * @constructor
 * @extends jQuery.wikibase.toolbar
 * @since 0.4
 */
$.widget( 'wikibase.toolbareditgroup', PARENT, {
	/**
	 * Options.
	 * @type {Object}
	 */
	options: {
		displayRemoveButton: false
	},

	/**
	 * Edit button.
	 * @type {jQuery}
	 */
	$btnEdit: null,

	/**
	 * Cancel button.
	 * @type {jQuery}
	 */
	$btnCancel: null,

	/**
	 * Save button.
	 * @type {jQuery}
	 */
	$btnSave: null,

	/**
	 * Remove button.
	 * @type {jQuery.wikibase.wbbutton}
	 */
	$btnRemove: null,

	/**
	 * Node holding the tooltips image with the tooltip itself attached.
	 * @type {jQuery}
	 */
	$tooltipAnchor: null,

	/**
	 * Inner group needed to visually separate tooltip from the interaction buttons which are
	 * supposed to be rendered with item separators. The inner group features the buttons.
	 * @type {jQuery.wikibase.toolbar}
	 */
	innerGroup: null,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		var self = this;

		PARENT.prototype._create.call( this );

		this._initToolbar();

		// Overwrite tooltip message when editing is restricted:
		$( wb ).on(
			'restrictEntityPageActions blockEntityPageActions',
			function( event ) {
				var messageId = ( event.type === 'blockEntityPageActions' ) ?
					'wikibase-blockeduser-tooltip-message' :
					'wikibase-restrictionedit-tooltip-message';

				self.$tooltipAnchor.data( 'wblabel' ).getTooltip().setContent(
					mw.message( messageId ).escaped()
				);

				self.$tooltipAnchor.data( 'wblabel' ).getTooltip().setGravity( 'nw' );
			}
		);
	},

	/**
	 * @see jQuery.Widget.destroy
	 */
	destroy: function() {
		PARENT.prototype.destroy.call( this );
		if ( this.innerGroup !== null ) {
			var $innerGroup = this.innerGroup.element;
			this.innerGroup.destroy();
			$innerGroup.remove();
			this.innerGroup = null;
		}
		if ( this.$tooltipAnchor !== null ) {
			if( this.$tooltipAnchor.data( 'wblabel' ) ) {
				// Might be destroyed already via PARENT's destroy():
				this.$tooltipAnchor.data( 'wblabel' ).destroy();
			}
			this.$tooltipAnchor.remove();
			this.$tooltipAnchor = null;
		}

		$.each(
			[this.$btnEdit, this.$btnCancel, this.$btnSave, this.$btnRemove ],
			function( i, $btn ) {
				if ( $btn !== null ) {
					if( $btn.data( 'wbbutton' ) ) {
						$btn.data( 'wbbutton' ).destroy();
					}
					$btn.remove();
				}
			}
		);

		this.$btnEdit = null;
		this.$btnCancel = null;
		this.$btnSave = null;
		this.$btnRemove = null;
	},

	/**
	 * Initializes the edit group.
	 * @since 0.4
	 */
	_initToolbar: function() {
		// Inner group will just feature the buttons that are rendered with item separators:
		var $innerGroup = mw.template( 'wikibase-toolbar', '', '' ).toolbar( {
			renderItemSeparators: true
		} );
		this.innerGroup = $innerGroup.data( 'toolbar' );
		this.addElement( $innerGroup );

		this.$tooltipAnchor = $( '<span/>' ).wblabel( {
			content: $( '<span/>', {
			'class': 'mw-help-field-hint',
			style: 'display:inline;text-decoration:none;', // TODO: Get rid of inline styles.
			html: '&nbsp;' // TODO find nicer way to hack Webkit browsers to display tooltip image (see also css)
			} ),
			stateChangeable: false // Tooltip anchor has no disabled/enabled behavior.
		} );
		// Initialize the tooltip just to be able to change render variables like gravity:
		this.$tooltipAnchor.data( 'wblabel' ).setTooltip( '' );

		// Now, create the buttons we need for basic editing:
		var self = this;

		this.$btnEdit = mw.template(
			'wikibase-wbbutton',
			mw.msg( 'wikibase-edit' ),
			'javascript:void(0);'
		)
		.wbbutton()
		.on( 'wbbuttonaction', function( event ) {
			self._trigger( 'edit', null, [ function() { self.toEditMode(); } ] );
		} );

		this.$btnCancel = mw.template(
			'wikibase-wbbutton',
			mw.msg( 'wikibase-cancel' ),
			'javascript:void(0);'
		)
		.wbbutton()
		.on( 'wbbuttonaction', function( event ) {
			self._trigger( 'cancel', null, [ function() {
				if( self.element.data( 'toolbareditgroup' ) ) {
					self.toNonEditMode();
				}
			} ] );
		} );

		this.$btnSave = mw.template(
			'wikibase-wbbutton',
			mw.msg( 'wikibase-save' ),
			'javascript:void(0);'
		)
		.wbbutton()
		.on( 'wbbuttonaction', function( event ) {
			self._trigger( 'save', null, [ function() {
				if( self.element.data( 'toolbareditgroup' ) && !self.isDisabled() ) {
					// Only toggle EditGroup as long as it still exists.
					// If edit group is disabled, the interaction target appears to be invalid and
					// edit mode is supposed to persist.
					self.toNonEditMode();
				}
			} ] );
		} );

		// initialize remove button:
		this.$btnRemove = mw.template(
			'wikibase-wbbutton',
			mw.msg( 'wikibase-remove' ),
			'javascript:void(0);'
		)
		.wbbutton()
		.on( 'wbbuttonaction', function( event ) {
			self._trigger( 'remove' );
		} );

		this.toNonEditMode(); // Initialize the toolbar.
	},

	/**
	 * Sets/updates the toolbar tooltip's message.
	 * @since 0.4
	 *
	 * @param {string} message Tooltip message
	 */
	setTooltip: function( message ) {
		this.$tooltipAnchor.data( 'wblabel' ).setTooltip( message );
	},

	/**
	 * Changes the toolbar to not display the "edit" button and display the buttons about editing
	 * instead. Will not trigger the "action" event of any button.
	 * of the buttons.
	 * @since 0.4
	 */
	toEditMode: function() {
		this.innerGroup.removeElement( this.$btnEdit );
		this.innerGroup.addElement( this.$btnSave );
		if ( this.options.displayRemoveButton ) {
			this.innerGroup.addElement( this.$btnRemove );
		}
		this.innerGroup.addElement( this.$btnCancel );
		this.addElement( this.$tooltipAnchor, 1 ); // Insert tooltip after interaction links.

		this.element
		.removeClass( this.widgetBaseClass + '-innoneditmode' )
		.addClass( this.widgetBaseClass + '-ineditmode' );

		this.draw();
	},

	/**
	 * Changes the toolbar to display the 'edit' button only. Will not trigger the "action" event
	 * of any buttons.
	 * @since 0.4
	 */
	toNonEditMode: function() {
		// Remove buttons for editing and display buttons for switching back to edit mode:
		this.removeElement( this.$tooltipAnchor );
		this.innerGroup.removeElement( this.$btnSave );
		if ( this.options.displayRemoveButton ) {
			this.innerGroup.removeElement( this.$btnRemove );
		}
		this.innerGroup.removeElement( this.$btnCancel );
		this.innerGroup.addElement( this.$btnEdit );

		this.element
		.removeClass( this.widgetBaseClass + '-ineditmode' )
		.addClass( this.widgetBaseClass + '-innoneditmode' );

		this.draw();
	}

} );

}( mediaWiki, wikibase, jQuery ) );
