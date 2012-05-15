/**
 * JavasSript for 'Wikibase' property edit tool toolbar label
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.Toolbar.Label.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 */
"use strict";

/**
 * Represents a label within a wikibase.ui.Toolbar toolbar
 *
 * @param string|jQuery content
 */
window.wikibase.ui.Toolbar.Label = function( content ) {
	if( typeof content != 'undefined' ) {
		this._init( content );
	}
};
window.wikibase.ui.Toolbar.Label.prototype = {
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
	 * @var wikibase.ui.Tooltip tooltip attached to this label
	 */
	_tooltip: null,
	
	/**
	 * Initializes the ui element.
	 * This should normally be called directly by the constructor.
	 * 
	 * @param string|jQuery content
	 */
	_init: function( content ) {
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
		if ( this._elem != null ) {
			this._elem.empty().remove();
			this._elem = null;
		}
		if ( this.tooltip != null ) {
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
		
		if( contents.length == 1 && contents[0].nodeType === 3 ) {
			// return the text
			return contents.text();
		} else {
			// return jQuery object
			return contents;
		}
	},

	/**
	 * Attaches a tooltip message to this label
	 *
	 * @param string|window.wikibase.ui.Tooltip tooltip message to be displayed as tooltip or already built tooltip
	 */
	setTooltip: function( tooltip ) {
		// if last tooltip was visible, we make the new one visible as well
		var wasVisible = false;

		if ( this._tooltip !== null ) {
			// remove existing tooltip first!
			this.removeTooltip();
			wasVisible = this._tooltip.isVisible();
		}
		if ( typeof tooltip == 'string' ) {
			// build new tooltip from string:
			this._elem.attr( 'title', tooltip );
			this._tooltip = new window.wikibase.ui.Tooltip( this._elem, tooltip );
		}
		else if ( tooltip instanceof window.wikibase.ui.Tooltip ) {
			this._tooltip = tooltip;
		}
		// restore previous tooltips visibility:
		if( this._tooltip !== null ) {
			if( wasVisible ) {
				this._tooltip.show();
			} else {
				this._tooltip.hide();
			}
		}
	},

	/**
	 * remove a tooltip message attached to this label
	 *
	 * @return bool whether a tooltip was set
	 */
	removeTooltip: function() {
		if ( this._tooltip !== null ) {
			this._tooltip.destroy();
			this._tooltip = null;
			return true;
		}
		return false;
	},

	/**
	 * Returns the labels tooltip or null in case none is set yet.
	 *
	 * @return window.wikibase.ui.Tooltip|null
	 */
	getTooltip: function() {
		return this._tooltip;
	},
	
	/**
	 * Returns true if the element is disabled.
	 * 
	 * @return bool
	 */
	isDisabled: function() {
		return this._elem.hasClass( this.UI_CLASS + '-disabled' );
	},
	
	/**
	 * Disables or enables the element. Disabled is still visible but will be presented differently
	 * and might behave differently in some cases.
	 * 
	 * @param bool disable true for disabling, false for enabling the element
	 * @return bool whether the state was changed or not.
	 */
	setDisabled: function( disable ) {
		if( typeof disable == 'undefined' ) {
			disable = true;
		}		
		if( this.isDisabled() == disable ) {
			// no point in disabling/enabling if this is the current state
			return false;
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
	},
	
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
