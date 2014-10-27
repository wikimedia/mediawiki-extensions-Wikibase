/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
jQuery.valueview = jQuery.valueview || {};

( function( $, vv, util ) {
	'use strict';

	/**
	 * Creates a new expert definition as it is required by jQuery.valueview.valueview.
	 *
	 * NOTE: Just by defining a new expert here, the expert won't be available in a valueview
	 *  widget automatically. The expert has to be registered in a jQuery.valueview.ExpertStore
	 *  instance which has to be used as expert store in the valueview widget's options.
	 *
	 * @see jQuery.valueview.Expert
	 *
	 * @since 0.1
	 *
	 * @param {string} name Should be all-lowercase and without any special characters. Will be used
	 *        in within some DOM class attributes and
	 * @param {Function} base Constructor of the expert the new expert should be based on.
	 * @param {Function} [constructor] Constructor of the new expert.
	 * @param {Object} expertDefinition Definition of the expert.
	 *
	 * @return {jQuery.valueview.Expert} the new expert constructor.
	 */
	vv.expert = function( name, base, constructor, expertDefinition ) {
		if( !expertDefinition ){
			expertDefinition = constructor;
			constructor = null;
		}
		if( !$.isFunction( base ) ) {
			throw new Error( 'The expert\'s base must be a constructor function' );
		}

		// do actual inheritance from base and apply custom definition:
		return util.inherit(
			'ValueviewExpert_' + name,
			base,
			constructor,
			$.extend( expertDefinition, {
				uiBaseClass: 'valueview-expert-' + name
			} )
		);
	};

	/**
	 * Abstract class for strategies used in jQuery.valueview.valueview for displaying and handling
	 * a certain type of data value or data values suitable for a certain data type.
	 * The expert itself is conceptually not dependent on data types. It always works with data
	 * values but the way it is presenting the edit interface could be optimized for data values
	 * suitable for a certain data type. This could for example be done by restrictions in the
	 * edit interface by reflecting a data type's validation rules.
	 *
	 * NOTE: Consider using jQuery.valueview.expert to define a new expert instead of inheriting
	 *       from this base directly.
	 *
	 * @since 0.1
	 *
	 * @param {HTMLElement|jQuery} viewPortNode
	 * @param {jQuery.valueview.ViewState} relatedViewState
	 * @param {util.Notifier} [valueViewNotifier] Required so the expert can notify the valueview
	 *        about certain events. The following notification keys can be used:
	 *        - change: will be sent when raw value displayed by the expert changes. Either by a
	 *                  user action or by calling the rawValue() method. First parameter is a
	 *                  reference to the Expert itself.
	 * @param {Object} [options={}]
	 *
	 * TODO: think about whether there should be a function to add multiple notifiers for widget
	 *  developers or whether they should rather listen to the valueview widget while the experts
	 *  can not be touched. Less performant alternative would be the usage of DOM events.
	 *
	 * @constructor
	 * @abstract
	 */
	vv.Expert = function( viewPortNode, relatedViewState, valueViewNotifier, options ) {
		if( !( relatedViewState instanceof vv.ViewState ) ) {
			throw new Error( 'No ViewState object was provided to the valueview expert' );
		}

		if( !valueViewNotifier ) {
			valueViewNotifier = util.Notifier();
		}
		else if( !( valueViewNotifier instanceof util.Notifier ) ) {
			throw new Error( 'No Notifier object was provided to the valueview expert' );
		}

		if( viewPortNode instanceof $
			&& viewPortNode.length === 1
		) {
			viewPortNode = viewPortNode.get( 0 );
		}

		if( !( viewPortNode.nodeType ) ) { // IE8 can't check for instanceof HTMLELement
			throw new Error( 'No sufficient DOM node provided for the valueview expert' );
		}

		this._viewState = relatedViewState;
		this._viewNotifier = valueViewNotifier;

		this.$viewPort = $( viewPortNode );

		this._options = $.extend( ( !this._options ) ? {} : this._options, options || {} );

		var defaultMessages = this._options.messages || {},
			msgGetter = this._options.mediaWiki ? this._options.mediaWiki.msg : null;
		this._messageProvider = new util.MessageProvider( {
			defaultMessage: defaultMessages,
			messageGetter: msgGetter
		} );

		this._extendable = new util.Extendable();
	};

	vv.Expert.prototype = {
		/**
		 * A unique UI class for this Expert definition. Should be used to prefix classes on DOM
		 * nodes within the Expert's view port. If a new expert definition will be created using
		 * jQuery.valueview.Expert(), then this will be set by that function.
		 * @type String
		 */
		uiBaseClass: '',

		/**
		 * The DOM node which has to be updated by the draw() function. Displays current state
		 * and/or input elements for user interaction during valueview's edit mode.
		 * @type jQuery
		 */
		$viewPort: null,

		/**
		 * Object representing the state of the related valueview.
		 * @type jQuery.valueview.ViewState
		 */
		_viewState: null,

		/**
		 * Object for publishing changes to the outside.
		 * @type util.Notifier
		 */
		_viewNotifier: null,

		/**
		 * The expert's options, received through the constructor.
		 * @type Object
		 */
		_options: null,

		/**
		 * Message provider used to fetch messages from mediaWiki if available.
		 * @type {util.MessageProvider}
		 */
		_messageProvider: null,

		_extendable: null,

		addExtension: function( extension ){
			this._extendable.addExtension( extension );
		},

		/**
		 * Will be called initially for new expert instances.
		 *
		 * @since 0.5
		 */
		init: function() {
			this.$viewPort.addClass( this.uiBaseClass );
			this._init(); // for backwards-compatibility
			this._extendable.callExtensions( 'init' );
		},

		_init: function() {},

		/**
		 * Destroys the expert. All generated viewport output is being deleted and all resources
		 * (private members, events handlers) will be released.
		 *
		 * This will not preserve the plain text of the last represented value as one might expect
		 * when thinking about the common jQuery.Widget's behavior. This is mostly because it is
		 * not the Expert's responsibility to be able to serve a plain text representation of the
		 * value. If the value should be represented as plain text after the expert's construction,
		 * let the responsible controller use a value formatter for that.
		 *
		 * @since 0.1
		 */
		destroy: function() {
			if( !this.$viewPort ) {
				return; // destroyed already
			}
			this._extendable.callExtensions( 'destroy' );
			this.$viewPort.removeClass( this.uiBaseClass ).empty();
			this.$viewPort = null;
			this._viewState = null;
			this._viewNotifier = null;
			this._messageProvider = null;
			this._options = null;
		},

		/**
		 * Returns an object with characteristics specified for the value. The object can be used
		 * as parser options definition.
		 *
		 * This method should allow to be called statically, i. e. without a useful `this` context.
		 *
		 * TODO: This should actually move out of here together with all the advanced input features
		 *  of certain experts (time/coordinate).
		 *
		 * @since 0.1
		 */
		valueCharacteristics: function() {
			return {};
		},

		/**
		 * Returns an object offering information about the related valueview's current state.
		 * The expert reflects that state, so everything that is true for the related view, is also
		 * true for the expert (e.g. whether it is in edit mode or disabled).
		 *
		 * @since 0.1
		 *
		 * @return jQuery.valueview.ViewState
		 */
		viewState: function() {
			return this._viewState;
		},

		/**
		 * Will return the value as a string.
		 *
		 * @since 0.1
		 * @abstract
		 *
		 * @return {string} Returns the current raw value.
		 */
		rawValue: util.abstractMember,

		/**
		 * Will draw the user interface components for the user to edit the value.
		 *
		 * @since 0.1
		 */
		draw: function() {
			this._extendable.callExtensions( 'draw' );
		},

		/**
		 * Will set the focus if there is some focusable input elements.
		 *
		 * @since 0.1
		 */
		focus: function() {},

		/**
		 * Makes sure that the focus will be removed from any focusable input elements.
		 *
		 * @since 0.1
		 */
		blur: function() {}
	};

}( jQuery, jQuery.valueview, util ) );
