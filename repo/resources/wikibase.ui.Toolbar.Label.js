/**
 * JavaScript for 'Wikibase' property edit tool toolbar label
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 */
( function( mw, wb, $, undefined ) {
'use strict';

/**
 * Represents a label within a wikibase.ui.Toolbar toolbar
 * @constructor
 * @since 0.1
 */
wb.ui.Toolbar.Label = function( content ) {
	if( content !== undefined ) {
		this.init( content );
	}
};
wb.ui.Toolbar.Label.prototype = {
	/**
	 * @const
	 * Class which marks the ui element within the site html.
	 */
	UI_CLASS: 'wb-ui-toolbar-label',

	/**
	 * @var jQuery
	 */
	_elem: null,


	/**
	 * Initializes the ui element.
	 * This should normally be called directly by the constructor.
	 *
	 * @param string|jQuery content
	 */
	init: function( content ) {
		if( this._parent !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._initElem( content );
		//this._text = text; // for debugging
	},

	_initElem: function( content ) {
		if ( typeof content == String ) {
			content = $.trim( content );
		}
		this._elem = $( '<span/>', {
			'class': this.UI_CLASS
		} ).append( content );
	},

	destroy: function() {
		if ( this._elem !== null ) {
			this._elem.empty().remove();
			this._elem = null;
		}
		if ( this._tooltip !== null ) {
			this.removeTooltip();
		}
	},

	/**
	 * Sets the label's content
	 *
	 * @param string|jQuery content
	 */
	setContent: function( content ) {
		this._elem.empty();
		if ( typeof content == 'string' ) {
			content = $.trim( content );
		}
		this._elem.append( content );
	},

	/**
	 * Returns the labels content. If only text was set as content, a string will be returned, if
	 * HTML nodes were set, this will return a jQuery object.
	 *
	 * @return jQuery|string
	 */
	getContent: function() {
		var contents = this._elem.contents();

		if( contents.length === 1 && contents[0].nodeType === 3 ) {
			// return the text
			return contents.text();
		} else {
			// return jQuery object
			return contents;
		}
	},

	/**
	 * Determine whether state change (enabling, disabling) is possible for this object.
	 *
	 * @return bool whether changing the state is possible
	 */
	isStateChangeable: function() {
		return this.stateChangeable;
	},

	/**
	 * Set focus on this label.
	 */
	setFocus: function() {
		this._elem.focus();
	},

	/**
	 * Remove focus from this label.
	 */
	removeFocus: function() {
		this._elem.blur();
	},


	/////////////////
	// CONFIGURABLE:
	/////////////////

	/**
	 * @var bool whether object's state is changeable (enabled, disabled)
	 */
	stateChangeable: true,


	///////////
	// EVENTS:
	///////////

	/**
	 * Callback called before the element will be set disabled. If the callback returns false, the
	 * disabling process will be cancelled.
	 */
	beforeDisable: null,

	/**
	 * Callback called before the element will be set enabled. If the callback returns false, the
	 * enabling process will be cancelled.
	 */
	beforeEnable: null
};

// add tooltip functionality to EditableValue:
wb.ui.Tooltip.Extension.useWith( wb.ui.Toolbar.Label, {
	// overwrite required functions:
	_getTooltipParent: function() {
		return this._elem;
	}
} );

// add disable/enable functionality overwriting required functions
wb.ui.StateExtension.useWith( wb.ui.Toolbar.Label, {

	/**
	 * @see wikibase.ui.StateExtension.getState
	 *
	 * @return Number state
	 */
	getState: function() {
		return ( this._elem.hasClass( this.UI_CLASS + '-disabled' ) ) ?
			this.STATE.DISABLED :
			this.STATE.ENABLED;
	},

	/**
	 * @see wikibase.ui.StateExtension.setDisabled
	 *
	 * @param Boolean disable true for disabling, false for enabling the element
	 * @return Boolean whether the requested state was applied (might also be applied already)
	 */
	setDisabled: function( disable ) {
		if ( !this.stateChangeable ) { // state is not supposed to change, no need to do anything
			return true;
		}
		if( disable === undefined ) {
			disable = true;
		}
		if( this.isDisabled() == disable ) {
			return true; // no point in disabling/enabling if this is the current state
		}
		var cls = this.UI_CLASS + '-disabled';

		if( disable ) {
			if( this.beforeDisable !== null && this.beforeDisable() === false ) { // callback
				return false; // cancel
			}
			this._elem.addClass( cls );
		} else {
			if( this.beforeEnable !== null && this.beforeEnable() === false ) { // callback
				return false; // cancel
			}
			this._elem.removeClass( cls );
		}

		return true;
	}

} );

} )( mediaWiki, wikibase, jQuery );
