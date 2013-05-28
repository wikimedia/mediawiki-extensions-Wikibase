/**
 * JavaScript for 'Wikibase' property edit tool toolbar buttons
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 */
( function( mw, wb, $ ) {
'use strict';
var PARENT = wb.ui.Toolbar.Label;

/**
 * Represents a button within a wikibase.ui.Toolbar toolbar
 * @constructor
 * @see wb.ui.Toolbar.Label
 * @since 0.1
 *
 * @event action: Triggered when the button action is triggered by clicking or hitting enter on it
 *        (1) jQuery.Event
 */
wb.ui.Toolbar.Button = wb.utilities.inherit( PARENT, {
	/**
	 * @const
	 * Class which marks the toolbar button within the site html.
	 */
	UI_CLASS: 'wb-ui-toolbar-button',

	/**
	 * @see wb.ui.Toolbar.Label.init
	 */
	init: function( content ) {
		PARENT.prototype.init.call( this, content );

		// disable button and attach tooltip when editing is restricted
		$( wb ).on(
			'restrictEntityPageActions blockEntityPageActions',
			$.proxy( function( event ) {
				this.disable();

				var messageId = ( event.type === 'blockEntityPageActions' )
					? 'wikibase-blockeduser-tooltip-message'
					: 'wikibase-restrictionedit-tooltip-message';

				this.setTooltip(
					mw.message( messageId ).escaped()
				);

				this._tooltip.setGravity( 'nw' );
			}, this )
		);

	},

	_initElem: function( text ) {
		this._elem = mw.template( 'wb-toolbar-button', text, 'javascript:;' )
		.addClass( this.UI_CLASS )
		.on( 'click', $.proxy( this.doAction, this ) );
	},

	/**
	 * @see wb.ui.Toolbar.Label.destroy
	 */
	destroy: function() {
		PARENT.prototype.destroy.call( this );
		if ( this._elem !== null ) {
			this._elem.remove();
		}
	},

	/**
	 * Executes the action related to the button. Can't be done if button is disabled.
	 * @return bool false if the button is disabled
	 */
	doAction: function() {
		if( this.isDisabled() ) { // can't do action when disabled!
			return false;
		}
		$( this ).triggerHandler( 'action' );
		return true;
	},

	/**
	 * @see wb.ui.Toolbar.Label.setFocus
	 */
	setFocus: function() {
		// No need to apply tab index to enabled element since it has an <a> tag as wrapping element.
		if ( this.isDisabled() ) {
			this._makeFocusable();
		}
		this._elem.focus();
	},

	/**
	 * Dis- or enables the button.
	 *
	 * @param {Number} state See wb.ui.EditableValue.STATE
	 * @return {Boolean} Whether the desired state has been applied (or had been applied already)
	 */
	_setState: function( state ) {
		var text = this.getContent(),
			oldElem = this._elem,
			hasFocus = this._elem.is( ':focus' );

		if( state === this.STATE.DISABLED ) {
			// create a disabled label instead of a link
			PARENT.prototype._initElem.call( this, text );
		} else {
			this._initElem( text );
		}

		// replace old element with new one within dom
		oldElem.replaceWith( this._elem );

		// re-focus if element was focused previously
		if ( hasFocus ) {
			this._elem.focus();
		}

		// call prototypes disable function:
		return PARENT.prototype._setState.call( this, state );
	}

} );

} )( mediaWiki, wikibase, jQuery );
