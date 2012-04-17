/**
 * JavasSript for managing editable representation of site links.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */

/**
 * Serves the input interface for a site link, extends EditableValue.
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableSiteLink = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableSiteLink.call( this, subject );
};
window.wikibase.ui.PropertyEditTool.EditableSiteLink.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableSiteLink.prototype, {

	//getInputHelpMessage: function() {
	//	return window.mw.msg( 'wikibase-description-input-help-message', mw.config.get('wbDataLangName') );
	//},

	_initToolbar: function() {
		this._toolbar = new window.wikibase.ui.PropertyEditTool.Toolbar( this._subject.parent() );

		// give the toolbar a edit group with basic edit commands:
		var editGroup = new window.wikibase.ui.PropertyEditTool.Toolbar.EditGroup();
		editGroup.displayRemoveButton = true;
		editGroup._init( this );
		this._toolbar.addElement( editGroup );
		this._toolbar.editGroup = editGroup; // remember this

		//if( this.isEmpty() ) {
		//	// enable editing from the beginning if there is no value yet!
		//	this._toolbar.editGroup.btnEdit.doAction();
		//	this.removeFocus(); // but don't set focus there for now
		//}
	},

	getApiCallParams: function() {
		return {
			action: 'wbsetdescription',
			language: wgUserLanguage,
			description: this.getValue(),
			id: mw.config.values.wbItemId
		};
	}
} );