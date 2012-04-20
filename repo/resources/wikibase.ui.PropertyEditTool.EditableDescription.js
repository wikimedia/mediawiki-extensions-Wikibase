/**
 * JavasSript for managing editable representation of an items description.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author Tobias Gritschacher
 */

/**
 * Serves the input interface for an item description, extends EditableValue.
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableDescription = function( subject ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject );
};
window.wikibase.ui.PropertyEditTool.EditableDescription.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableDescription.prototype, {
	getInputHelpMessage: function() {
		return window.mw.msg( 'wikibase-description-input-help-message', mw.config.get('wbSiteLinks') );
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
