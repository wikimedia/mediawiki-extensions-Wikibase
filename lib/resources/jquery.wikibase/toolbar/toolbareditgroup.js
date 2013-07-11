/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 *
 * @option {boolean} displayRemoveButton Whether to display a "remove" button.
 *         Default: false
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
	 * @type {jQuery.wikibase.toolbarbutton}
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
		PARENT.prototype._create.call( this );
		this._initToolbar();
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
			if( this.$tooltipAnchor.data( 'toolbarlabel' ) ) {
				// Might be destroyed already via PARENT's destroy():
				this.$tooltipAnchor.data( 'toolbarlabel' ).destroy();
			}
			this.$tooltipAnchor.remove();
			this.$tooltipAnchor = null;
		}

		$.each(
			[this.$btnEdit, this.$btnCancel, this.$btnSave, this.$btnRemove ],
			function( i, $btn ) {
				if ( $btn !== null ) {
					if( $btn.data( 'toolbarbutton' ) ) {
						$btn.data( 'toolbarbutton' ).destroy();
					}
					$btn.remove();
				}
			}
		);

		this.$btnEdit = null;
		this.$btnCancel = null;
		this.$btnSave = null;
		this.$btnRemove = null;

		if( $( '.' + this.widgetBaseClass ).length === 0 ) {
			$( wb ).off( '.' + this.widgetName );
		}
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

		this.$tooltipAnchor = $( '<span/>' );

		this.$tooltipAnchor
		.append( $( '<span/>', {
			'class': 'mw-help-field-hint',
			style: 'display:inline;text-decoration:none;', // TODO: Get rid of inline styles.
			html: '&nbsp;' // TODO find nicer way to hack Webkit browsers to display tooltip image (see also css)
		} ) )
		// Tooltip anchor has no disabled/enabled behavior.
		.toolbarlabel( { stateChangeable: false } )
		// Initialize the tooltip just to be able to change render variables like gravity:
		.data( 'toolbarlabel' ).setTooltip( '' );

		// Now, create the buttons we need for basic editing:
		this.$btnEdit = mw.template(
			'wikibase-toolbarbutton',
			mw.msg( 'wikibase-edit' ),
			'javascript:void(0);'
		)
		.toolbarbutton();

		this.$btnCancel = mw.template(
			'wikibase-toolbarbutton',
			mw.msg( 'wikibase-cancel' ),
			'javascript:void(0);'
		)
		.toolbarbutton();

		this.$btnSave = mw.template(
			'wikibase-toolbarbutton',
			mw.msg( 'wikibase-save' ),
			'javascript:void(0);'
		)
		.toolbarbutton();

		this.$btnRemove = mw.template(
			'wikibase-toolbarbutton',
			mw.msg( 'wikibase-remove' ),
			'javascript:void(0);'
		)
		.toolbarbutton();

		this._attachEventHandlers();

		this.toNonEditMode(); // Initialize the toolbar.
	},

	/**
	 * Attaches event handlers regarding the toolbar edit group.
	 * @since 0.4
	 */
	_attachEventHandlers: function() {
		var self = this;

		this.$btnEdit.on( 'toolbarbuttonaction.' + this.widgetName, function( event ) {
			self._trigger( 'edit', null, [ function() { self.toEditMode(); } ] );
		} );

		this.$btnCancel.on( 'toolbarbuttonaction.' + this.widgetName, function( event ) {
			self._trigger( 'cancel', null, [ function() {
				if( self.element.data( 'toolbareditgroup' ) ) {
					self.toNonEditMode();
				}
			} ] );
		} );

		this.$btnSave.on( 'toolbarbuttonaction.' + this.widgetName, function( event ) {
			self._trigger( 'save', null, [ function() {
				if( self.element.data( 'toolbareditgroup' ) && !self.isDisabled() ) {
					// Only toggle EditGroup as long as it still exists.
					// If edit group is disabled, the interaction target appears to be invalid and
					// edit mode is supposed to persist.
					self.toNonEditMode();
				}
			} ] );
		} );

		this.$btnRemove.on( 'toolbarbuttonaction.' + this.widgetName, function( event ) {
			self._trigger( 'remove' );
		} );

		// Overwrite tooltip message when editing is restricted:
		if( $( '.' + this.widgetBaseClass ).length === 0 ) {
			// Can only find edit groups that are in the DOM. However, the event handler is not needed
			// more than one. At least, remove previously attached handler to not have it registered
			// twice.
			$( wb )
			.off( '.' + this.widgetName )
			.on( 'restrictEntityPageActions.' + this.widgetName
				+ ' blockEntityPageActions.' + this.widgetName,
				function( event ) {
					var messageId = ( event.type === 'blockEntityPageActions' ) ?
						'wikibase-blockeduser-tooltip-message' :
						'wikibase-restrictionedit-tooltip-message';

					self.$tooltipAnchor.data( 'toolbarlabel' ).getTooltip().setContent(
						mw.message( messageId ).escaped()
					);

					self.$tooltipAnchor.data( 'toolbarlabel' ).getTooltip().setGravity( 'nw' );
				}
			);
		}
	},

	/**
	 * Sets/updates the toolbar tooltip's message.
	 * @since 0.4
	 *
	 * @param {string} message Tooltip message
	 */
	setTooltip: function( message ) {
		this.$tooltipAnchor.data( 'toolbarlabel' ).setTooltip( message );
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
	},

	/**
	 * Clones the toolbar edit group circumventing jQuery widget creation process.
	 * @since 0.4
	 *
	 * @param {Object} [options] Widget options to inject into cloned widget.
	 * @return {jQuery} Cloned node featuring a cloned edit group widget.
	 */
	clone: function( options ) {
		options = options || {};

		// Do not clone event bindings by using clone( true ) since these would trigger events on
		// the original element.
		var $clone = this.element.clone();

		// Since we cannot use clone( true ), copy the data attributes manually:
		$.each( this.element.data(), function( k, v ) {
			$clone.data( k, $.extend( true, {}, v ) );
		} );

		var clone = $clone.data( 'toolbareditgroup' );

		// Update clone's element:
		clone.element = $clone;

		$.extend( clone.options, options );

		// Clear items array dropping references to original object's items:
		clone._items = [];

		// Re-init inner group:
		var $innerGroup = mw.template( 'wikibase-toolbar', '', '' ).toolbar( {
			renderItemSeparators: true
		} );
		clone.innerGroup = $innerGroup.data( 'toolbar' );
		clone.addElement( $innerGroup );

		// Re-init tooltip anchor:
		clone.$tooltipAnchor = $( '<span/>' );
		clone.$tooltipAnchor
		.append( $( '<span/>', {
			'class': 'mw-help-field-hint',
			style: 'display:inline;text-decoration:none;', // TODO: Get rid of inline styles.
			html: '&nbsp;' // TODO find nicer way to hack Webkit browsers to display tooltip image (see also css)
		} ) )
		// Tooltip anchor has no disabled/enabled behavior.
		.toolbarlabel( { stateChangeable: false } )
		// Initialize the tooltip just to be able to change render variables like gravity:
		.data( 'toolbarlabel' ).setTooltip( '' );

		// Clone buttons:
		clone.$btnEdit = this.$btnEdit.data( 'toolbarbutton' ).clone();
		clone.$btnCancel = this.$btnCancel.data( 'toolbarbutton' ).clone();
		clone.$btnSave = this.$btnSave.data( 'toolbarbutton' ).clone();
		clone.$btnRemove = this.$btnRemove.data( 'toolbarbutton' ).clone();

		// Re-attach event handlers:
		clone._attachEventHandlers();

		// Re-init the toolbar contents' visibility:
		clone.toNonEditMode();

		// Re-assign "wikibase-toolbaritem" data attribute with updated clone:
		$clone.data( 'wikibase-toolbaritem', clone );

		return $clone;
	}

} );

}( mediaWiki, wikibase, jQuery ) );
