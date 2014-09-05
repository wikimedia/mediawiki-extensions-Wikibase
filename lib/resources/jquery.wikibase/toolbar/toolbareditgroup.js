/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner
 *
 * @option {Object} buttonCharacteristics The characteristics of the edit group's buttons.
 *         Default: Object containing basic information to initialize the buttons.
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
( function( mw, $ ) {
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
		buttonCharacteristics: {
			edit: {
				label: mw.msg( 'wikibase-edit' ),
				href: 'javascript:void(0);',
				cssClassExt: 'editbutton',
				disabled: false
			},
			save: {
				label: mw.msg( 'wikibase-save' ),
				href: 'javascript:void(0);',
				cssClassExt: 'savebutton',
				disabled: false
			},
			remove: {
				label: mw.msg( 'wikibase-remove' ),
				href: 'javascript:void(0);',
				cssClassExt: 'removebutton',
				disabled: false
			},
			cancel: {
				label: mw.msg( 'wikibase-cancel' ),
				href: 'javascript:void(0);',
				cssClassExt: 'cancelbutton',
				disabled: false
			}
		},
		displayRemoveButton: false
	},

	/**
	 * @type {Object}
	 */
	_buttons: null,

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
	 *
	 * @throws {Error} if any button characteristic is not defined.
	 */
	_create: function() {
		var buttonCharacteristics = this.options.buttonCharacteristics;

		if(
			!buttonCharacteristics.edit
			|| !buttonCharacteristics.save
			|| !buttonCharacteristics.remove
			|| !buttonCharacteristics.cancel
		) {
			throw new Error( 'Incomplete button characteristics' );
		}

		this._buttons = {};

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

		$.each( this._buttons, function( name, $button ) {
			if ( $button !== null ) {
				if( $button.data( 'toolbarbutton' ) ) {
					$button.data( 'toolbarbutton' ).destroy();
				}
				$button.remove();
			}
		} );

		this._buttons = {};
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
		.toolbarlabel();

		this._attachEventHandler();

		this.toNonEditMode(); // Initialize the toolbar.
	},

	/**
	 * Attaches event handlers regarding the toolbar edit group.
	 * @since 0.4
	 */
	_attachEventHandler: function() {
		var self = this;

		this.element.on( 'toolbarbuttonaction.' + this.widgetName, function( event ) {
			switch( event.target ) {
				case self._buttons.edit.get( 0 ):
					self._trigger( 'edit', null, [ function() { self.toEditMode(); } ] );
					break;
				case self._buttons.save.get( 0 ):
					self._trigger( 'save', null, [ function() {
						if( self.element.data( 'toolbareditgroup' ) && !self.options.disabled ) {
							// Only toggle EditGroup as long as it still exists.
							// If edit group is disabled, the interaction target appears to be
							// invalid and edit mode is supposed to persist.
							self.toNonEditMode();
						}
					} ] );
					break;
				case self.options.displayRemoveButton && self._buttons.remove.get( 0 ):
					self._trigger( 'remove' );
					break;
				case self._buttons.cancel.get( 0 ):
					self._trigger( 'cancel', null, [ function() {
						if( self.element.data( 'toolbareditgroup' ) ) {
							self.toNonEditMode();
						}
					} ] );
					break;
			}
		} );
	},

	/**
	 * Changes the toolbar to not display the "edit" button and display the buttons about editing
	 * instead. Will not trigger the "action" event of any button.
	 * of the buttons.
	 * @since 0.4
	 */
	toEditMode: function() {
		if( this._buttons.edit ) {
			this.innerGroup.removeElement( this._buttons.edit );
		}

		this.innerGroup.addElement( this.getButton( 'save' ) );

		if ( this.options.displayRemoveButton ) {
			this.innerGroup.addElement( this.getButton( 'remove' ) );
		}

		this.innerGroup.addElement( this.getButton( 'cancel' ) );

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

		if( this._buttons.save ) {
			this.innerGroup.removeElement( this._buttons.save );
		}

		if ( this.options.displayRemoveButton && this._buttons.remove ) {
			this.innerGroup.removeElement( this._buttons.remove );
		}

		if( this._buttons.cancel ) {
			this.innerGroup.removeElement( this._buttons.cancel );
		}

		this.innerGroup.addElement( this.getButton( 'edit' ) );

		this.element
		.removeClass( this.widgetBaseClass + '-ineditmode' )
		.addClass( this.widgetBaseClass + '-innoneditmode' );

		this.draw();
	},

	/**
	 * Returns whether a specific button has been generated yet.
	 * @since 0.5
	 *
	 * @param {string} buttonName "edit"|"save"|"remove"|"cancel"
	 * @return {boolean}
	 */
	hasButton: function( buttonName ) {
		return !!this._buttons[buttonName];
	},

	/**
	 * Returns a button by its name creating the button if it has not yet been created.
	 * @since 0.5
	 *
	 * @param {string} buttonName "edit"|"save"|"remove"|"cancel"
	 * @return {jQuery}
	 */
	getButton: function( buttonName ) {
		if( !this._buttons[buttonName] ) {
			var buttonCharacteristics = this.options.buttonCharacteristics[buttonName];

			this._buttons[buttonName] = mw.template(
				'wikibase-toolbarbutton',
				buttonCharacteristics.label,
				buttonCharacteristics.href
			)
			.toolbarbutton()
			.addClass( this.widgetBaseClass + '-' + buttonCharacteristics.cssClassExt );

			if( buttonCharacteristics.disabled ) {
				this._buttons[buttonName].data( 'toolbarbutton' ).disable();
			}
		}

		return this._buttons[buttonName];
	},

	/**
	 * @see jQuery.wikibase.toolbar._setOption
	 */
	_setOption: function( key, value ) {
		var self = this;
		if( key === 'disabled' ) {
			$.each( this._buttons, function( buttonName, button ) {
				if( button ) {
					self[value ? 'disableButton' : 'enableButton']( buttonName );
				}
			} );
		}
		return PARENT.prototype._setOption.apply( this, arguments );
	},

	/**
	 * Disables a particular edit group button.
	 * @since 0.5
	 *
	 * @param {string} buttonName "edit"|"save"|"remove"|"cancel"
	 */
	disableButton: function( buttonName ) {
		this.options.buttonCharacteristics[buttonName].disabled = true;
		if( this._buttons[buttonName] ) {
			this._buttons[buttonName].data( 'toolbarbutton' ).disable();
		}
	},

	/**
	 * Enables a particular edit group button.
	 * @since 0.5
	 *
	 * @param {string} buttonName "edit"|"save"|"remove"|"cancel"
	 */
	enableButton: function( buttonName ) {
		this.options.buttonCharacteristics[buttonName].disabled = false;
		if( this._buttons[buttonName] ) {
			this._buttons[buttonName].data( 'toolbarbutton' ).enable();
		}
	},

	/**
	 * @param {string} state
	 */
	_setState: function( state ) {
		// TODO: This special handling should not be necessary: Resolve toolbar state handling.
		var self = this;

		$.each( this.options.buttonCharacteristics, function( buttonName ) {
			if( self._buttons[buttonName] ) {
				self._buttons[buttonName].data( 'toolbarbutton' )[state]();
			}
		} );

		return true;
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
		.toolbarlabel();

		// Clone buttons:
		clone._buttons.edit = this._buttons.edit ? this._buttons.edit.data( 'toolbarbutton' ).clone() : null;
		clone._buttons.cancel = this._buttons.cancel ? this._buttons.cancel.data( 'toolbarbutton' ).clone() : null;
		clone._buttons.save = this._buttons.save ? this._buttons.save.data( 'toolbarbutton' ).clone() : null;
		clone._buttons.remove = this._buttons.remove ? this._buttons.remove.data( 'toolbarbutton' ).clone() : null;

		// Re-attach event handlers:
		clone._attachEventHandler();

		// Re-init the toolbar contents' visibility:
		clone.toNonEditMode();

		// Re-assign "wikibase-toolbaritem" data attribute with updated clone:
		$clone.data( 'wikibase-toolbaritem', clone );

		return $clone;
	}

} );

}( mediaWiki, jQuery ) );
