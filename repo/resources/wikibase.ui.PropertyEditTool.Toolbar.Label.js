/**
 * JavasSript for 'Wikibase' property edit tool toolbar label
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.Toolbar.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */

/**
 * Represents a label within a wikibase.ui.PropertyEditTool.Toolbar toolbar
 * 
 * @param jQuery parent
 */
window.wikibase.ui.PropertyEditTool.Toolbar.Label = function( appendTo ) {
	if( typeof appendTo != 'undefined' ) {
		this._init( appendTo );
	}
};
window.wikibase.ui.PropertyEditTool.Toolbar.Label.prototype = {
	/**
	 * @const
	 * Class which marks the ui element within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittoolbar-label',

	/**
	 * @var jQuery
	 */
	_elem: null,
	
	/**
	 * Initializes the ui element.
	 * This should normally be called directly by the constructor.
	 */
	_init: function( text ) {
		if( this._parent !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._initElem( text );
		//this._text = text; // for debugging
	},
	
	_initElem: function( text ) {
		this._elem = $( '<span/>', {
            'class': this.UI_CLASS,
            text: text
        } );
	},

	destroy: function() {
		// TODO
	},
	
	/**
	 * sets the buttons text
	 * @param string text
	 */
	setText: function( text ) {
		this._elem.text( $.trim( text ) );
	},
	
	/**
	 * returns the buttons text
	 * @return string
	 */
	getText: function() {
		return this._elem.text();
	},
	
	/**
	 * Returns true if the element is disabled.
	 * @return bool
	 */
	isDisabled: function() {
		return this._elem.hasClass( this.UI_CLASS + '-disabled' );
	},
	
	/**
	 * Disables or enables the element. Disabled is still visible but will be presented differently
	 * and might behave differently in some cases.
	 * @param bool disable true for disabling, false for enabling the element
	 * @return bool whether the state was changed or not.
	 */
	setDisabled: function( disable ) {
		if( typeof disable == 'undefined' ) {
			disable = true;
		}		
		if( this.isDisabled() == disable ) {
			// no point in disabling/enabling if this is the current state
			return false
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
