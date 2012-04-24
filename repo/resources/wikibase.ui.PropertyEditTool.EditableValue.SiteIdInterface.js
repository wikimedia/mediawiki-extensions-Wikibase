/**
 * JavasSript for a part of an editable property value for the input for a site id
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.Interface.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * Serves the input interface to write a site code to select, this will validate whether the site
 * code is existing and will display the full site name if it is.
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface = function( subject, editableValue ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.Interface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue.Interface();
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.SiteIdInterface.prototype, {
	
	_resolvedSiteName: null,
	
	_initInputElement: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._initInputElement.call( this );
		
		this._resolvedSiteName = $( '<span/>', {
			'class': this.UI_CLASS + '-siteid'
		} )
		this._inputElem.after( this._resolvedSiteName );
	},
	
	_onInputRegistered: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.Interface.prototype._onInputRegistered.call( this );
		var text, clsToAdd, clsToRemove;
		
		var isValid = this.validate( this.getValue() );
		if( isValid ) {
			text = "Deutschland"; // TODO use global variable with site links here
			clsToAdd = '-validsiteid';
			clsToRemove = '-invalidsiteid';
		} else {
			text = '???';
			clsToAdd = '-invalidsiteid';
			clsToRemove = '-validsiteid';
		}
		this._editableValue._interfaces.pageName.setDisabled( !isValid );
		this._resolvedSiteName.text( text );		
		this._resolvedSiteName.addClass( this.UI_CLASS + clsToAdd );
		this._resolvedSiteName.removeClass( this.UI_CLASS + clsToRemove );
	},
	
	/**
	 * Returns true if the value is a vaid site id
	 * @see wikibase.ui.PropertyEditTool.EditableValue.Interface
	 * 
	 * @param string text
	 * @return bool
	 */
	validate: function( value ) {
		return $.trim( value ) === 'de' // TODO use global variable with site links here!
	}
} );
