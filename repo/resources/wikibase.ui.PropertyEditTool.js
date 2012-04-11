/**
 * JavasSript for 'Wikibase' edit forms
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 * 
 * @since 0.1
 * @file wikibase.ui.PropertyEditTool.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner at wikimedia.de >
 */

/**
 * Module for 'Wikibase' extensions user interface functionality.
 */
window.wikibase.ui.PropertyEditTool = function( subject ) {
	if( typeof subject != 'undefined' ) {
		this._init( subject );
	}
};
window.wikibase.ui.PropertyEditTool.prototype = {
	/**
	 * @const
	 * Class which marks a popup within the site html.
	 */
	UI_CLASS: 'wb-ui-propertyedittool',
	
	/**
	 * Element the popup is related to.
	 * @var jQuery
	 */
	_subject: null,
	
	/**
	 * Name of the property
	 * @var string
	 */
	_key: null,
	
	_val: null,
	
	/**
	 * Element holding the properties value which will also hold the edit box when initialized.
	 * @var jQuery
	 */
	_jPropValue: null,
		
	/**
	 * Initializes the edit form for the given element.
	 * This should normally be called directly by the constructor.
	 */
	_init: function( subject ) {
		if( this._subject !== null ) {
			// initializing twice should never happen, have to destroy first!
			this.destroy();
		}
		this._subject = $( subject );
		this._subject.addClass( this.UI_CLASS + '-subject' );
		
		this._jPropValue = $( this._subject.children( '.wb-property-container-value' )[0] );
		
		//this._insertEditBoxForValue();
		//this._removeEditBoxForValue( true );
		
		//new window.wikibase.ui.PropertyEditTool.Toolbar( this._subject );
	},
	
	/**
	 * @todo: not clear yet whether this should be implemented. This would be neded if
	 *        label and value can be editied parallel, not if both get their own "edit"
	 *        button though (in this case other stuff has to be refactored probably).
	 */	
	_insertEditBoxForLabel: function() { },
	
	_insertEditBoxForValue: function() {
		this._insertEditBox( this._jPropValue );
	},
	
	/**
	 * Initializes the input box as sub element of a given element and uses the elements content
	 * as initial text.
	 * 
	 * @todo: perhaps, at a later point, we want to have an own class for the edit box(es) to handle
	 *        different kinds of snaks and in general.
	 * 
	 * @param jQuery parent
	 */
	_insertEditBox: function( parent ) {
		initText = parent.text();
		
		inputBox = $( '<input/>', {
			'class': this.UI_CLASS + '-edit-box',
			'type': 'text',
			'name': this._key,
			'value': initText
		} );
		
		parent.text( '' );
		parent.append( inputBox );
		
		// store original text value from before input box insertion:
		inputBox.data( this.UI_CLASS + '-edit-box-initial-value', initText );
		
		this._jPropValue = parent;
	},
	
	_removeEditBoxForValue: function( keepValue ) {
		this._removeEditBox( this._jPropValue, keepValue );
	},
	
	/**
	 * Destroys the edit box and displays text again instead.
	 * 
	 * @param bool keepValue if true the boxes text will be displayed, otherwise the text before
	 *        initialization will be displayed
	 */
	_removeEditBox: function( parent, keepValue ) {
		inputBox = $( parent.children( '.' + this.UI_CLASS + '-edit-box' )[0] );
		
		$value = ( ! keepValue )
			? inputBox.data( this.UI_CLASS + '-edit-box-initial-value' )
			: inputBox.attr( 'value' );
		
		inputBox.empty().remove();
		
		parent.text( $value );
	},
	
	destroy: function() {
		// TODO
	},
	
	save: function() {
		
	},
	
	/**
	 * Returns the related properties title
	 *
	 * @todo: perhaps at a later point we want to have a getProperty() method instead to return
	 *        a proper object describing the property. Also considering different kinds of snaks.
	 * 
	 * @var string
	 */
	getPropertyName: function() {
		return this._subject.children( '.wb-property-container-key' )[0].title();
	}
}