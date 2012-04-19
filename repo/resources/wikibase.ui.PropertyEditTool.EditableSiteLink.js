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
	window.wikibase.ui.PropertyEditTool.EditableValue.call( this, subject );
};
window.wikibase.ui.PropertyEditTool.EditableSiteLink.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue();
$.extend( window.wikibase.ui.PropertyEditTool.EditableSiteLink.prototype, {

	//getInputHelpMessage: function() {
	//	return window.mw.msg( 'wikibase-description-input-help-message', mw.config.get('wbDataLangName') );
	//},
	
	_initToolbar: function() {
		window.wikibase.ui.PropertyEditTool.EditableValue.prototype._initToolbar.call( this );
		this._toolbar.editGroup.displayRemoveButton = true;
		this._toolbar.draw();
	},
	
	_getToolbarParent: function() {
		// append toolbar to new td
		return $( this._subject[1] ).parent().append( $( '<td/>' ) );
	},
	
	getApiCallParams: function( removeValue ) {
		if ( removeValue === true ) {
			console.log('remove');
			return {
				action: 'wbsitelink',
				id: mw.config.values.wbItemId,
				link: 'add',
				linksite: '',
				linktitle: ''
			};
		} else {
			console.log('save');
			return {
				action: 'wbsitelink',
				id: mw.config.values.wbItemId,
				link: 'remove',
				linksite: '',
				linktitle: ''
			};
		}
	}
} );