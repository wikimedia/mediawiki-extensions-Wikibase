/**
 * JavasSript for a part of an editable property value
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.EditableValue.ClientPageInterface.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 * @author Daniel Werner
 */
"use strict";

/**
 * Serves the input interface to choose a wiki page from some MediaWiki installation as part of an
 * editable value
 * 
 * @param jQuery subject
 */
window.wikibase.ui.PropertyEditTool.EditableValue.ClientPageInterface = function( subject, client ) {
	window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.apply( this, arguments );
};
window.wikibase.ui.PropertyEditTool.EditableValue.ClientPageInterface.prototype = new window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface();
$.extend( window.wikibase.ui.PropertyEditTool.EditableValue.ClientPageInterface.prototype, {
	/**
	 * Information for which client this autocomplete interface should serve input suggestions
	 * @var wikibase.Client
	 */
	_client: null,
	
	/**
	 * @see wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface._init()
	 * 
	 * @param client wikibase.Client as source for the page suggestions
	 */
	_init: function( subject, client ) {
		window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype._init.apply( this, arguments );
		this.setClient( client );
	},
	
	setClient: function( client ) {
		if( typeof client !== 'object' ) {
			return;
		}
		if( this._client !== null && this._client.getId() === client.getId() ) {
			return; // no change
		}
		
		this.url = client.getApi();
		this._client = client;
				
		this._currentResults = []; // empty current suggestions...		
		if( this.isInEditMode() ) {
			this._inputElem.autocomplete( "search" ); // ...and get new suggestions
			
			/* // TODO: this should be done after "search" is finished, apparently, there is no callback for that currently...
			if( ! this.isValid() ) {
				this.setValue( '' );
			}
			*/
		}
	},
	
	getClient: function( client ) {
		return this._client;		
	},

	/**
	 * validate input
	 * @param String value
	 */
	validate: function( value ) {
		window.wikibase.ui.PropertyEditTool.EditableValue.AutocompleteInterface.prototype.validate.call( this, value );
		for ( var i in this._currentResults ) {
			if ( value === this._currentResults[i] ) {
				return true;
			}
		}
	},
	
	setValue: function( value ) {
		if( this.isInEditMode() ) {
			this._inputElem.attr( 'value', value );
		} else {
			this._getValueContainer()
			.empty()
			.append( // insert link to site in client
				this._client.getLinkTo( value )
			);
		}
	}

} );
