/**
 * JavaScript for 'Wikibase' property edit tool toolbar groups with basic edit functionality
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 *
 *
 * @event edit: Triggered when clicking (hitting enter on) the edit button.
 *        (1) {jQuery.Event}
 *
 * @event save: Triggered when clicking (hitting enter on) the save button.
 *        (1) {jQuery.Event}
 *
 * @event cancel: Triggered when clicking (hitting enter on) the cancel button.
 *        (1) {jQuery.Event}
 *
 * @event remove: Triggered when clicking (hitting enter on) the remove button.
 *        (1) {jQuery.Event}
 *
 * @event toEditMode: Triggered before switching the set of buttons to edit related buttons.
 *        (1) {jQuery.Event}
 *
 * @event toNonEditMode: Triggered before switching the set of buttons to edit button only.
 *        (1) {jQuery.Event}
 */
( function( mw, wb, $, undefined ) {
'use strict';
var PARENT = wb.ui.Toolbar.Group;

/**
 * Extends the basic toolbar group element with buttons essential for editing stuff.
 * Basically '[edit]' which gets expanded to '[cancel|save]' when hit.
 *
 * @constructor
 * @see wb.ui.Toolbar.Group
 * @since 0.3 (since 0.1 but required EditableValue back then)
 */
wb.ui.Toolbar.EditGroup = wb.utilities.inherit( PARENT,
	// Overwritten constructor:
	function( options ) {
		this.init( options );
	}, {
	/**
	 * @var wb.ui.Toolbar.Button
	 */
	btnEdit: null,

	/**
	 * @var wb.ui.Toolbar.Button
	 */
	btnCancel: null,

	/**
	 * @var wb.ui.Toolbar.Button
	 */
	btnSave: null,

	/**
	 * @var wb.ui.Toolbar.Button
	 */
	btnRemove: null,

	/**
	 * Element holding the tooltips image with the tooltip itself attached.
	 * @var wb.ui.Toolbar.Label
	 */
	tooltipAnchor: null,

	/**
	 * Inner group needed to visually separate tooltip and edit buttons, this one holds the edit buttons.
	 * @var wb.ui.Toolbar.Group
	 */
	innerGroup: null,

	/**
	 * EditGroup options.
	 * @var {Object}
	 */
	_options: null,

	/**
	 * @param {Object} [options] Custom options regarding the edit group.
	 */
	init: function( options ) {
		// default options
		this._options = {
			/**
			 * If this is set to true, the edit toolbar will add a button 'remove' besides the 'edit' command.
			 * @var bool
			 */
			displayRemoveButton: false
		};

		if ( options !== undefined ) {
			$.extend( this._options, options );
		}

		PARENT.prototype.init.call( this );

		// overwrite tooltip message when editing is restricted
		$( wb ).on(
			'restrictEntityPageActions blockEntityPageActions',
			$.proxy(
				function( event ) {
					var messageId = ( event.type === 'blockEntityPageActions' ) ?
						'wikibase-blockeduser-tooltip-message' :
						'wikibase-restrictionedit-tooltip-message';

					this.tooltipAnchor.getTooltip().setContent(
						mw.message( messageId ).escaped()
					);

					this.tooltipAnchor.getTooltip().setGravity( 'nw' );
				}, this
			)
		);
	},

	/**
	 * @see wb.ui.Toolbar.prototype._initToolbar()
	 */
	_initToolbar: function() {
		// call prototypes base function to append toolbar itself:
		wb.ui.Toolbar.prototype._initToolbar.call( this );

		// create a group inside the group so we can separate the tooltip visually
		this.innerGroup = new wb.ui.Toolbar.Group();
		this.addElement( this.innerGroup );

		this.tooltipAnchor = new wb.ui.Toolbar.Label( $( '<span/>', {
			'class': 'mw-help-field-hint',
			style: 'display:inline;text-decoration:none;',
			html: '&nbsp;' // TODO find nicer way to hack Webkit browsers to display tooltip image (see also css)
		} ) );
		// initialize the tooltip just to be able to change render variables like gravity
		this.tooltipAnchor.setTooltip( '' );
		this.tooltipAnchor.stateChangeable = false; // tooltip anchor has no disabled/enabled behavior

		// now create the buttons we need for basic editing:
		var Button = wb.ui.Toolbar.Button,
			self = this; // still use proxy when $.NativeEventHandler, so event handler has context


		this.btnEdit = new Button( mw.msg( 'wikibase-edit' ) );
		$( this.btnEdit ).on(
			'action',
			$.proxy( $.NativeEventHandler( 'edit', function() {
				this.toEditMode();
			} ), this )
		);

		this.btnCancel = new Button( mw.msg( 'wikibase-cancel' ) );
		$( this.btnCancel ).on(
			'action',
			$.proxy( $.NativeEventHandler( 'cancel', function() {
				this.toNonEditMode();
			} ), this )
		);

		this.btnSave = new Button( mw.msg( 'wikibase-save' ) );
		$( this.btnSave ).on(
			'action',
			$.proxy( $.NativeEventHandler( 'save', function() {
				this.toNonEditMode();
			} ), this )
		);

		// initialize remove button:
		this.btnRemove = new Button( mw.msg( 'wikibase-remove' ) );
		$( this.btnRemove ).on( 'action', function( event ) {
			self.triggerHandler( 'remove' );
		} );

		this.toNonEditMode(); // initializing the toolbar
	},

	/**
	 * @see wb.ui.Toolbar.Group._drawToolbar
	 */
	_drawToolbar: function() {
		PARENT.prototype._drawToolbar.call( this );
		this._elem.addClass( 'wb-ui-toolbar-editgroup' );
	},

	/**
	 * Sets/updates the toolbar tooltip's message.
	 *
	 * @param {String} message Tooltip message
	 */
	setTooltip: function( message ) {
		this.tooltipAnchor.setTooltip( message );
	},

	/**
	 * Changes the toolbar to not display the 'edit' button and display the buttons about editing
	 * instead. Will not trigger the 'action' event of any button.
	 * of the buttons.
	 *
	 * @since 0.3
	 */
	toEditMode: $.NativeEventHandler( 'toEditMode', function() {
		this.innerGroup.removeElement( this.btnEdit );
		this.innerGroup.addElement( this.btnSave );
		if ( this._options.displayRemoveButton ) {
			this.innerGroup.addElement( this.btnRemove );
		}
		this.innerGroup.addElement( this.btnCancel );
		this.addElement( this.tooltipAnchor, 1 ); // add tooltip after edit commands

		this._elem.removeClass( 'wb-ui-toolbar-editgroup-innoneditmode' );
		this._elem.addClass( 'wb-ui-toolbar-editgroup-ineditmode' );
	} ),

	/**
	 * Changes the toolbar to display the 'edit' button only. Will not trigger the 'action' event
	 * of any buttons.
	 *
	 * @since 0.3
	 */
	toNonEditMode: $.NativeEventHandler( 'toNonEditMode', function() {
		// remove buttons for editing and display buttons for going back to edit mode
		this.removeElement( this.tooltipAnchor );
		this.innerGroup.removeElement( this.btnSave );
		if ( this._options.displayRemoveButton ) {
			this.innerGroup.removeElement( this.btnRemove );
		}
		this.innerGroup.removeElement( this.btnCancel );
		this.innerGroup.addElement( this.btnEdit );

		this._elem.removeClass( 'wb-ui-toolbar-editgroup-ineditmode' );
		this._elem.addClass( 'wb-ui-toolbar-editgroup-innoneditmode' );
	} ),

	/**
	 * Destroys the EditGroup.
	 */
	destroy: function() {
		PARENT.prototype.destroy.call( this );
		if ( this.innerGroup !== null ) {
			this.innerGroup.destroy();
			this.innerGroup = null;
		}
		if ( this.tooltipAnchor !== null ) {
			this.tooltipAnchor.destroy();
			this.tooltipAnchor = null;
		}
		if ( this.btnEdit !== null ) {
			this.btnEdit.destroy();
			this.btnEdit = null;
		}
		if ( this.btnCancel !== null ) {
			this.btnCancel.destroy();
			this.btnCancel = null;
		}
		if ( this.btnSave !== null ) {
			this.btnSave.destroy();
			this.btnSave = null;
		}
		if ( this.btnRemove !== null ) {
			this.btnRemove.destroy();
			this.btnRemove = null;
		}
	},

	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * @see wb.ui.Toolbar.Group.renderItemSeparators
	 */
	renderItemSeparators: false

} );

}( mediaWiki, wikibase, jQuery ) );
