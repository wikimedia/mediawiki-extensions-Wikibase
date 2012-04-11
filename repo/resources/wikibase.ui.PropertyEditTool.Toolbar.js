/**
 * JavasSript for edit commands for 'Wikibase' property edit tool
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.Toolbar.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author ...
 */

/**
 * Gives basic edit toolbar functionality, serves the "[edit]" button as well as the "[cancel|save]"
 * buttons and other related stuff.
 * 
 * @param jQuery parent
 */
window.wikibase.ui.PropertyEditTool.Toolbar = function( appendTo ) {
	if( typeof appendTo != 'undefined' ) {
		this._init( appendTo );
	}
};
window.wikibase.ui.PropertyEditTool.Toolbar.prototype = {
	/**
	 * @const
	 * Class which marks the toolbar within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittoolbar',
			
	/**
	 * Initializes the edit toolbar for the given element.
	 * This should normally be called directly by the constructor.
	 */
	_init: function( parent ) {
		if( this._subject !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this.appendTo( $( parent ) );
	},
	
	/**
	 * Appends the element as last child to a parent element.
	 * @param jQuery parent
	 */
	appendTo: function( parent ) {
		// TODO (this is just a dummy)
        this._createEditButton().appendTo( parent );
	},

    _actionEdit: function() {
        if( this.onActionEdit != null && !this.onActionEdit() ) { // callback
            // cancel edit
            return false;
        }

    },

    _createEditButton: function() {
        var self = this;
        return $( '<a/>', {
            'class': this.UI_CLASS + '-edit-link',
            text: window.mw.msg( 'wikibase-edit' ),
            click: function(){ self._actionEdit(); }
        } );
    },

	destroy: function() {
		// TODO
	},
	
	///////////
	// EVENTS:
	///////////

	/**
	 * Callback called after the 'edit' button was pressed.
	 * If the callback returns false, the action will be cancelled.
	 */
	onActionEdit: function() {
        alert(1);
        return true;
    },
	
	/**
	 * Callback called after the 'save' button was pressed.
	 * If the callback returns false, the action will be cancelled.
	 */
	onActionSave: null,
	
	/**
	 * Callback called after the 'cancel' button was pressed.
	 * If the callback returns false, the action will be cancelled.
	 */
	onActionCancel: null
};
