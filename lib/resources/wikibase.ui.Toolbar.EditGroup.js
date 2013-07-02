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
	 * Node holding the tooltips image with the tooltip itself attached.
	 * @type {jQuery}
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

					this.tooltipAnchor.data( 'toolbarlabel' ).getTooltip().setContent(
						mw.message( messageId ).escaped()
					);

					this.tooltipAnchor.data( 'toolbarlabel' ).getTooltip().setGravity( 'nw' );
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

		this.tooltipAnchor = $( '<span/>', {
			'class': 'mw-help-field-hint',
			style: 'display:inline;text-decoration:none;',
			html: '&nbsp;' // TODO find nicer way to hack Webkit browsers to display tooltip image (see also css)
		} ).toolbarlabel( {
			stateChangeable: false // tooltip anchor has no disabled/enabled behavior
		} );
		// initialize the tooltip just to be able to change render variables like gravity
		this.tooltipAnchor.data( 'toolbarlabel' ).setTooltip( '' );

		// now create the buttons we need for basic editing:
		var self = this; // still use proxy when $.NativeEventHandler, so event handler has context

		this.btnEdit = mw.template(
			'wikibase-toolbarbutton',
			mw.msg( 'wikibase-edit' ),
			'javascript:void(0);'
		)
		.toolbarbutton()
		.on( 'toolbarbuttonaction', $.proxy( $.NativeEventHandler( 'edit', function() {
			self.toEditMode();
		} ), this ) );

		this.btnCancel = mw.template(
			'wikibase-toolbarbutton',
			mw.msg( 'wikibase-cancel' ),
			'javascript:void(0);'
		)
		.toolbarbutton()
		.on( 'toolbarbuttonaction', $.proxy( $.NativeEventHandler( 'cancel', function() {
			self.toNonEditMode();
		} ), this ) );

		this.btnSave = mw.template(
			'wikibase-toolbarbutton',
			mw.msg( 'wikibase-save' ),
			'javascript:void(0);'
		)
		.toolbarbutton()
		.on( 'toolbarbuttonaction', $.proxy( $.NativeEventHandler( 'save', function() {
			self.toNonEditMode();
		} ), this ) );

		// initialize remove button:
		this.btnRemove = mw.template(
			'wikibase-toolbarbutton',
			mw.msg( 'wikibase-remove' ),
			'javascript:void(0);'
		)
		.toolbarbutton()
		.on( 'toolbarbuttonaction', function( event ) {
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
		this.tooltipAnchor.data( 'toolbarlabel' ).setTooltip( message );
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
			if( this.tooltipAnchor.data( 'toolbarlabel' ) ) {
				// Might be destroyed already via PARENT's destroy():
				this.tooltipAnchor.data( 'toolbarlabel' ).destroy();
			}
			this.tooltipAnchor.remove();
			this.tooltipAnchor = null;
		}
		if ( this.btnEdit !== null ) {
			if( this.btnEdit.data( 'toolbarbutton' ) ) {
				this.btnEdit.data( 'toolbarbutton' ).destroy();
			}
			this.btnEdit.remove();
			this.btnEdit = null;
		}
		if ( this.btnCancel !== null ) {
			if( this.btnCancel.data( 'toolbarbutton' ) ) {
				this.btnCancel.data( 'toolbarbutton' ).destroy();
			}
			this.btnCancel.remove();
			this.btnCancel = null;
		}
		if ( this.btnSave !== null ) {
			if( this.btnSave.data( 'toolbarbutton' ) ) {
				this.btnSave.data( 'toolbarbutton' ).destroy();
			}
			this.btnSave.remove();
			this.btnSave = null;
		}
		if ( this.btnRemove !== null ) {
			if( this.btnRemove.data( 'toolbarbutton' ) ) {
				this.btnRemove.data( 'toolbarbutton' ).destroy();
			}
			this.btnRemove.remove();
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
