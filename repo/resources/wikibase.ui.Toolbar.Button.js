/**
 * JavaScript for 'Wikibase' property edit tool toolbar buttons
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 *
 * Events:
 * -------
 * action: Triggered when the button action is triggered by clicking or hitting enter on it
 *                   Parameters: (1) jQuery.event
 */
"use strict";

/**
 * Represents a button within a wikibase.ui.Toolbar toolbar
 *
 * @param jQuery parent
 */
window.wikibase.ui.Toolbar.Button = function( text ) {
	window.wikibase.ui.Toolbar.Label.call( this, text );
};
window.wikibase.ui.Toolbar.Button.prototype = new window.wikibase.ui.Toolbar.Label();
$.extend( window.wikibase.ui.Toolbar.Button.prototype, {
	/**
	 * @const
	 * Class which marks the toolbar button within the site html.
	 */
	UI_CLASS: 'wb-ui-toolbar-button',

	_init: function( content ) {
		wikibase.ui.Toolbar.Label.prototype._init.call( this, content );

		// disable button and attach tooltip when editing is restricted
		$( wikibase ).on(
			'restrictItemPageActions blockItemPageActions',
			$.proxy(
				function( event ) {
					this.disable();

					var messageId = ( event.type === 'blockItemPageActions' )
						? 'wikibase-blockeduser-tooltip-message'
						: 'wikibase-restrictionedit-tooltip-message';

					this.setTooltip(
						mw.message( messageId ).escaped()
					);

					this._tooltip.setGravity( 'nw' );
				}, this
			)
		);

	},

	_initElem: function( text ) {
		this._elem = $( '<a/>', {
			'class': this.UI_CLASS,
			text: text,
			href: 'javascript:;',
			click: jQuery.proxy( this.doAction, this )
		} );
	},

	destroy: function() {
		window.wikibase.ui.Toolbar.Label.prototype.destroy.call( this );
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
	 * Disables or enables the button
	 * @param bool disabled true for disabling, false for enabling the button.
	 *        If the button is disabled, it can't be clicked.
	 * @return bool whether the operation was successful
	 */
	setDisabled: function( disable ) {
		if( this.isDisabled() === disable ) {
			return true;
		}
		var text = this.getContent();
		var oldElem = this._elem;

		if( disable ) {
			// create a disabled label instead of a link
			window.wikibase.ui.Toolbar.Label.prototype._initElem.call( this, text );
		}
		else {
			this._initElem( text );
		}

		// replace old element with new one within dom
		oldElem.replaceWith( this._elem );

		// call prototypes disable function:
		return window.wikibase.ui.Toolbar.Label.prototype.setDisabled.call( this, disable );
	}

} );
