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
 * @author H. Snater
 * @author Tobias Gritschacher
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
	 * @var jQuery
	 */
	_subject: null,
	
	/**
	 * @var jQuery
	 */
	_parent: null,
	
	/**
	 * Initializes the edit toolbar for the given element.
	 * This should normally be called directly by the constructor.
	 */
	_init: function( parent ) {
		if( this._parent !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		
		this._parent = parent;
		
		this._buildToolbar( [this._createButton(this.UI_CLASS + '-edit-link', window.mw.msg( 'wikibase-edit' ), this._actionEdit )] );
	},
	
	/**
	 * Creates the toolbar with an array of buttons which will be displayed separated by "|"
	 * 
	 * @param buttons
	 */
	_buildToolbar: function( buttons ) {
		if (this._subject != null) {
			this._subject.empty();
		}
		
		this._subject = $( '<div/>', {
			'class': this.UI_CLASS
		} );
		
		this._subject
		.appendTo( this._parent )
		.append( "[" );
		
		for( var i in buttons ) {
			if( i != 0 ) {
				this._subject.append( "|" );
			}
			this._subject.append( buttons[i] );
		}
		
		this._subject.append( "]" );
	},
	
    _actionEdit: function( event ) {
        if( this.onActionEdit !== null && this.onActionEdit() === false ) { // callback
            // cancel edit
            return false;
        }
        this._buildToolbar( [
			this._createButton( this.UI_CLASS + '-save-link', window.mw.msg( 'wikibase-save' ), this._actionSave ),
			this._createButton( this.UI_CLASS + '-cancel-link', window.mw.msg( 'wikibase-cancel' ), this._actionCancel )
        ] );
    },
    
    _actionSave: function( event ) {
        if( this.onActionSave !== null && this.onActionSave() === false ) { // callback
            // cancel save
            return false;
        }
        this._buildToolbar( [
			this._createButton( this.UI_CLASS + '-edit-link', window.mw.msg( 'wikibase-edit' ), this._actionEdit )
		] );
    },
    
    _actionCancel: function( event ) {
        if( this.onActionCancel !== null && this.onActionCancel() === false ) { // callback
            // cancel cancel
            return false;
        }
        this._buildToolbar( [
			this._createButton( this.UI_CLASS + '-edit-link', window.mw.msg( 'wikibase-edit' ), this._actionEdit )
		] );
    },
    
    _createButton: function( buttonClass, text, callback ) {
        return $( '<a/>', {
            'class': buttonClass,
            text: text,
            href: 'javascript:;',
            click: jQuery.proxy( callback, this )
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
	onActionEdit: null,
	
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
