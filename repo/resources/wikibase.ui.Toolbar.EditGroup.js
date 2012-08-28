/**
 * JavaScript for 'Wikibase' property edit tool toolbar groups with basic edit functionality
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, undefined ) {
"use strict";
var $PARENT = wb.ui.Toolbar.Group

/**
 * Extends the basic toolbar group element with buttons essential for editing stuff.
 * Basically '[edit]' which gets expanded to '[cancel|save]' when hit.
 * This also interacts with a given editable value.
 * @constructor
 * @see wb.ui.Toolbar.Group
 * @since 0.1
 *
 * @todo Should be refactored so it can be used independently from EditableValue.
 */
wb.ui.Toolbar.EditGroup = wb.utilities.inherit( $PARENT,
	// Overwritten constructor:
	function( editableValue ) {
		if( editableValue !== undefined ) {
			this.init( editableValue );
		}
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
	 * @var wb.ui.PropertyEditTool.EditableValue
	 */
	_editableValue: null,

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
	 * @param wb.ui.PropertyEditTool.EditableValue editableValue the editable value
	 *        the toolbar should interact with.
	 */
	init: function( editableValue ) {
		this._editableValue = editableValue;

		$PARENT.prototype.init.call( this );

		// overwrite tooltip message when editing is restricted
		$( wikibase ).on(
			'restrictItemPageActions blockItemPageActions',
			$.proxy(
				function( event ) {
					var messageId = ( event.type === 'blockItemPageActions' ) ?
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
		this.tooltipAnchor.setTooltip( this._editableValue.getInputHelpMessage() );
		this.tooltipAnchor.stateChangeable = false; // tooltip anchor has no disabled/enabled behaviour

		// now create the buttons we need for basic editing:
		var button = wb.ui.Toolbar.Button;

		this.btnEdit = new button( mw.msg( 'wikibase-edit' ) );
		$( this.btnEdit ).on( 'action', $.proxy( function( event ) {
			this._editActionHandler();
		}, this ) );

		this.btnCancel = new button( mw.msg( 'wikibase-cancel' ) );
		$( this.btnCancel ).on( 'action', $.proxy( function( event ) {
			this._cancelActionHandler();
		}, this ) );

		this.btnSave = new button( mw.msg( 'wikibase-save' ) );
		$( this.btnSave ).on( 'action', $.proxy( function( event ) {
			this._saveActionHandler();
		}, this ) );

		// add 'edit' button only for now:
		this.innerGroup.addElement( this.btnEdit );

		// initialize remove button:
		this.btnRemove = new button( mw.msg( 'wikibase-remove' ) );
		$( this.btnRemove ).on( 'action', $.proxy( function( event ) {
			this._removeActionHandler();
		}, this ) );
		if ( this.displayRemoveButton ) {
			this.innerGroup.addElement( this.btnRemove );
		}

	},

	_editActionHandler: function() {
		this.innerGroup.removeElement( this.btnEdit );
		if ( this.displayRemoveButton ) {
			this.innerGroup.removeElement( this.btnRemove );
		}
		this.innerGroup.addElement( this.btnSave );
		this.innerGroup.addElement( this.btnCancel );
		this.addElement( this.tooltipAnchor, 1 ); // add tooltip after edit commands
		this._editableValue.startEditing();
	},
	_cancelActionHandler: function() {
		this._leaveAction( false );
	},
	_saveActionHandler: function() {
		this._leaveAction( true );
	},
	_removeActionHandler: function() {
		this._editableValue.remove();
	},

	/**
	 * Changes the edit group from displaying buttons for editing to the state of displaying buttons to go into
	 * edit mode again.
	 */
	_leaveAction: function( save ) {
		// trigger the stop editing...
		var promise = this._editableValue.stopEditing( save );

		if(    promise.promisor.apiAction === wikibase.ui.PropertyEditTool.EditableValue.prototype.API_ACTION.SAVE
			|| promise.promisor.apiAction === wikibase.ui.PropertyEditTool.EditableValue.prototype.API_ACTION.NONE
		) {
			// ... when stopped, remove buttons for editing and display buttons for going back to edit mode
			promise.done( $.proxy( function() {
				this.tooltipAnchor.getTooltip().hide();
				this.removeElement( this.tooltipAnchor );
				this.innerGroup.removeElement( this.btnSave );
				this.innerGroup.removeElement( this.btnCancel );
				if ( this.displayRemoveButton ) {
					this.innerGroup.removeElement( this.btnRemove );
				}
				this.innerGroup.addElement( this.btnEdit );
				if ( this.displayRemoveButton ) {
					this.innerGroup.addElement( this.btnRemove );
				}
			}, this ) );
		}
	},

	destroy: function() {
		$PARENT.prototype.destroy.call( this );
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
	renderItemSeparators: false,

	/**
	 * If this is set to true, the edit toolbar will add a button 'remove' besides the 'edit' command.
	 * @var bool
	 */
	displayRemoveButton: false
} );

} )( mediaWiki, wikibase, jQuery );
