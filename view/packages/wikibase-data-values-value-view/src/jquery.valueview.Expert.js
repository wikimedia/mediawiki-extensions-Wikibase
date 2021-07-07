$.valueview = $.valueview || {};

( function( vv ) {
	'use strict';

	/**
	 * Creates a new `Expert` definition as it is required by `jQuery.valueview.valueview`.
	 *
	 * NOTE: Just by defining a new `Expert` here, the `Expert` won't be available in a `valueview`
	 * widget automatically. The `Expert` has to be registered in a `jQuery.valueview.ExpertStore`
	 * instance which has to be injected into the `valueview` via its options.
	 *
	 * @see jQuery.valueview.Expert
	 * @see jQuery.valueview.ExpertStore
	 *
	 * @member jQuery.valueview
	 * @method expert
	 * @static
	 * @since 0.1
	 * @license GNU GPL v2+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @param {string} name Should be all-lowercase and without any special characters. Will be used
	 *        in within some DOM class attributes and
	 * @param {Function} base Constructor of the `Expert` the new `Expert` should be based on.
	 * @param {Function|Object} constructorOrExpertDefinition Constructor of the new `Expert`.
	 * @param {Object} [expertDefinition] Definition of the `Expert`.
	 * @return {jQuery.valueview.Expert} the new `Expert` constructor.
	 *
	 * @throws {Error} if the base constructor is not a function.
	 */
	vv.expert = function( name, base, constructorOrExpertDefinition, expertDefinition ) {
		var constructor = null;

		if ( expertDefinition ) {
			constructor = constructorOrExpertDefinition;
		} else {
			expertDefinition = constructorOrExpertDefinition;
		}

		if ( typeof base !== 'function' ) {
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

	// TODO: think about whether there should be a function to add multiple notifiers for widget
	//  developers or whether they should rather listen to the valueview widget while the experts
	//  can not be touched. Less performant alternative would be the usage of DOM events.
	/**
	 * Abstract class for strategies used in `jQuery.valueview` for displaying and handling a
	 * certain type of data value or data values suitable for a certain data type.
	 * The `Expert` itself is conceptually not dependent on data types. It always works with data
	 * values but the way it is presenting the edit interface could be optimized for data values
	 * suitable for a certain data type. This could for example be done by restrictions in the edit
	 * interface by reflecting a data type's validation rules.
	 *
	 * NOTE: Consider using `jQuery.valueview.expert()` to define a new `Expert` instead of
	 * inheriting from this base directly.
	 *
	 * @class jQuery.valueview.Expert
	 * @abstract
	 * @since 0.1
	 * @license GNU GPL v2+
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 *
	 * @param {HTMLElement|jQuery} viewPortNode
	 * @param {ViewState} relatedViewState
	 * @param {util.Notifier} [valueViewNotifier=util.Notifier()]
	 *        Required so the `Expert` can notify the `valueview` about certain events. The
	 *        following notification keys can be used:
	 *
	 * - change: will be sent when raw value displayed by the `Expert` changes. Either by a user
	 *   action or by calling the `rawValue()` method. First parameter is a reference to the
	 *   `Expert` itself.
	 *
	 * @param {Object} [options={}]
	 *
	 * @throws {Error} if `viewPortNode` is not or does not feature a proper DOM node.
	 * @throws {Error} relatedViewState is not a `ViewState` instance.
	 * @throws {Error} if `valueViewNotifier` is not an `util.Notifier` instance.
	 * @throws {Error} if neither `messages` nor `messageProvider` is given.
	 */
	vv.Expert = function( viewPortNode, relatedViewState, valueViewNotifier, options ) {
		if ( ( typeof relatedViewState.getFormattedValue === 'undefined' ) ) {
			throw new Error( 'No ViewState object was provided to the valueview expert' );
		}

		if ( !valueViewNotifier ) {
			valueViewNotifier = util.Notifier();
		} else if ( !( valueViewNotifier instanceof util.Notifier ) ) {
			throw new Error( 'No Notifier object was provided to the valueview expert' );
		}

		if ( viewPortNode instanceof $
			&& viewPortNode.length === 1
		) {
			viewPortNode = viewPortNode.get( 0 );
		}

		if ( !( viewPortNode.nodeType ) ) { // IE8 can't check for instanceof HTMLElement
			throw new Error( 'No sufficient DOM node provided for the valueview expert' );
		}

		this._viewState = relatedViewState;
		this._viewNotifier = valueViewNotifier;

		this.$viewPort = $( viewPortNode );

		this._options = $.extend( ( !this._options ) ? {} : this._options, options || {} );

		if ( this._options.messages ) {
			this._messageProvider = new util.HashMessageProvider( this._options.messages );
		}
		if ( this._options.messageProvider ) {
			this._messageProvider = new util.CombiningMessageProvider(
				this._options.messageProvider,
				this._messageProvider
			);
		}
		if ( !this._messageProvider ) {
			throw new Error( 'No message provider and no messages were provided to the valueview expert' );
		}

		this._extendable = new util.Extendable();
	};

	/**
	 * @class jQuery.valueview.Expert
	 */
	vv.Expert.prototype = {
		/**
		 * A unique UI class for this `Expert` definition. Should be used to prefix classes on DOM
		 * nodes within the `Expert`'s view port. If a new `Expert` definition will be created
		 * using `jQuery.valueview.Expert()`, then this will be set by that function.
		 *
		 * @property {string}
		 * @readonly
		 */
		uiBaseClass: '',

		/**
		 * The DOM node which has to be updated by the `draw()` function. Displays current state
		 * and/or input elements for user interaction during `valueview`'s edit mode.
		 *
		 * @property {jQuery}
		 * @protected
		 * @readonly
		 */
		$viewPort: null,

		/**
		 * Object representing the state of the related `valueview`.
		 *
		 * @property {ViewState}
		 * @protected
		 */
		_viewState: null,

		/**
		 * Object for publishing changes to the outside.
		 *
		 * @property {util.Notifier}
		 * @protected
		 */
		_viewNotifier: null,

		/**
		 * The `Expert`'s options, received through the constructor.
		 *
		 * @property {Object} [_options={}]
		 * @protected
		 */
		_options: null,

		/**
		 * Message provider used to fetch messages
		 *
		 * @property {util.MessageProvider}
		 * @protected
		 */
		_messageProvider: null,

		/**
		 * @property {util.Extendable} [_extendable=new util.Extendable()]
		 * @protected
		 */
		_extendable: null,

		/**
		 * @param {Object} extension
		 */
		addExtension: function( extension ) {
			this._extendable.addExtension( extension );
		},

		/**
		 * Will be called initially for new `Expert` instances.
		 *
		 * @since 0.5
		 */
		init: function() {
			this.$viewPort.addClass( this.uiBaseClass );
			this._init(); // for backwards-compatibility
			this._extendable.callExtensions( 'init' );
		},

		/**
		 * Custom `Expert` initialization routine.
		 *
		 * @protected
		 */
		_init: function() {},

		/**
		 * Destroys the `Expert`. All generated viewport output is being deleted and all resources
		 * (private members, events handlers) will be released.
		 *
		 * This will not preserve the plain text of the last represented value as one might expect
		 * when thinking about the common `jQuery.Widget`'s behavior. This is mostly because it is
		 * not the `Expert`'s responsibility to be able to serve a plain text representation of the
		 * value. If the value should be represented as plain text after the `Expert`'s
		 * construction, let the responsible controller use a value formatter for that.
		 */
		destroy: function() {
			if ( !this.$viewPort ) {
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
		 * @return {Object}
		 */
		valueCharacteristics: function() {
			return {};
		},

		/**
		 * Returns an object offering information about the related `valueview`'s current state. The
		 * `Expert` reflects that state, so everything that is true for the related view, is also
		 * true for the `Expert` (e.g. whether it is in edit mode or disabled).
		 *
		 * @return {ViewState}
		 */
		viewState: function() {
			return this._viewState;
		},

		/**
		 * @abstract
		 *
		 * @return {string|dataValues.DataValue|null} Returns either the current raw value as a
		 *  string that needs to be parsed first, or an already parsed DataValue object (e.g. from a
		 *  client-side parser, but that should be avoided), or null if the expert is sure there is
		 *  nothing to parse.
		 */
		rawValue: util.abstractMember,

		/**
		 * Will draw the user interface components for the user to edit the value.
		 *
		 * @return {Object} jQuery.Promise
		 * @return {Function} return.done
		 * @return {Function} return.fail
		 */
		draw: function() {
			this._extendable.callExtensions( 'draw' );
		},

		/**
		 * Will set the focus if there is some focusable input elements.
		 */
		focus: function() {},

		/**
		 * Makes sure that the focus will be removed from any focusable input elements.
		 */
		blur: function() {}
	};

}( $.valueview ) );
