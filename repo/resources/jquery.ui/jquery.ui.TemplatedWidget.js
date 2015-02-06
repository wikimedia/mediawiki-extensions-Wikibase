/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $ ) {
	'use strict';

	var PARENT =  $.Widget;

	/**
	 * Base prototype for all widgets which use the mw.wbTemplate templating system to create a
	 * basic DOM structure for internal usage.
	 *
	 * @constructor
	 * @abstract
	 * @extends jQuery.Widget
	 * @since 0.4
	 *
	 * @option template {string} Name of a template to use rather than the default. Of course, the
	 *         given template has to be fully compatible to the default one. This means that should
	 *         have all required template parameters and classes used to identify certain nodes.
	 *         The template should have a structure with a single root node which will then be
	 *         replaced by the Widget's subject node.
	 *
	 * @option templateParams {Array} Parameters given to the template on its initial construction.
	 *         A parameter can be what is compatible with wm.wbTemplate but can also be a function
	 *         which will be executed in the widget's context and provide the parameter's value by
	 *         its return value.
	 *
	 * @option templateShortCuts {Object} A map pointing from the name of a field of the Widget
	 *         object which should act as a short cut to a node within the widget's template. The
	 *         location of the target node has to be given as a valid jQuery query expression. e.g.
	 *         'li.example-class > .foo'.
	 *         When setting this as option at Widget initialization, this should match the selectors
	 *         of a custom template. The used fields should stick to what is defined in the widget's
	 *         default options definition.
	 *
	 * @option [encapsulate=false] {boolean} Whether non-native `jQuery.Widget` events shall be
	 *         triggered on the widget's node only and not bubble up the DOM tree (using
	 *         `jQuery.triggerHandler()` instead of `jQuery.trigger()`).
	 *
	 * NOTE: the template options have been fields in the prototype before. It makes kind of sense
	 *       to make them available in the options though. An issue with having 'templateShortCuts'
	 *       as a field was that inheritance would not be possible with the jQuery Widget system
	 *       since only options will get a true copy by $.widget while other objects will be
	 *       modified on the base prototype. Our workaround for this only worked for one level of
	 *       inheritance (doing the copy manually in the prototype's constructor, can't define the
	 *       constructor of the new prototype created by jQuery.widget() though).
	 *
	 * @event disable
	 *        Triggered whenever the widget is disabled (after disabled state has been set).
	 *        - {jQuery.Event}
	 *        - {boolean} Whether widget has been dis- oder enabled.
	 *
	 * @event init
	 *        Triggered after the widget is fully initialized. (`jQuery.Widget` native "create"
	 *        event is triggered after the template DOM is ready and template short-cuts are
	 *        assigned.)
	 *        - {jQuery.Event}
	 */
	$.widget( 'ui.TemplatedWidget', PARENT, {
		/**
		 * Default options
		 * @see jQuery.Widget.options
		 */
		options: $.extend( true, {}, PARENT.prototype.options, {
			template: null,
			templateParams: [],
			/**
			 * @example { '$value': '.valueview-value', '$preview': '.ui-preview' }
			 * @descr this.$preview will hold the DOM node (wrapped inside a jQuery object) which
			 *        matches above expression.
			 */
			templateShortCuts: {},
			encapsulate: false
		} ),

		/**
		 * Creates the DOM structure according to the template and assigns the template short-cuts.
		 * Consequently, when overriding `_create` in inheriting widgets, calling the parent's
		 * `_create` should be the first action in the overridden `_create`, as that ensures the
		 * basic template DOM is created and template short-cuts can be used. The function should
		 * be overridden only to perform DOM manipulation/creation while initializing should be
		 * performed in `_init`.
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			// FIXME: Find sane way to detect that template is applied already.
			if( this.element.contents().length === 0 ) {
				this._applyTemplate();
			}

			this._createTemplateShortCuts();

			PARENT.prototype._create.apply( this );
		},

		/**
		 * Initializes any additional widget logic (i.e. child widgets, event handlers). DOM
		 * creation/manipulation is supposed to be performed in `_create` which is run before
		 * `_init`.
		 * @see jQuery.Widget._init
		 * @protected
		 */
		_init: function() {
			PARENT.prototype._init.call( this );
			this._trigger( 'init' );
		},

		_applyTemplate: function() {
			var templateParams = [],
				self = this;

			// template params which are functions are callbacks to be called in the widget's context
			$.each( this.options.templateParams, function( i, value ) {
				if( $.isFunction( value ) ) {
					value = value.call( self );
				}
				templateParams.push( value );
			} );

			// the element node will be preserved, no matter whether it is of the same kind as the
			// template's root node (it is assumed that the template has a root node)
			this.element.addClass( this.widgetBaseClass );
			this.element.applyTemplate( this.option( 'template' ), templateParams );
		},

		/**
		 * Creates the short cuts to DOM nodes within the template's DOM structure as specified in
		 * this.options.templateShortCuts.
		 *
		 * @since 0.4
		 */
		_createTemplateShortCuts: function() {
			var shortCuts = this.options.templateShortCuts,
				shortCut, shortCutSelector, $shortCutTarget;

			for( shortCut in shortCuts ) {
				shortCutSelector = shortCuts[ shortCut ];
				$shortCutTarget = this.element.find( shortCutSelector );
				if( $shortCutTarget.length < 1 ) {
					throw new Error( 'Template "' + this.option( 'template' ) + '" has no DOM node' +
						' selectable via the jQuery expression "' + shortCutSelector + '"'  );
				}
				this[ shortCut ] = $shortCutTarget;

			}
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function() {
			PARENT.prototype.destroy.call( this );

			this.element.removeClass( this.widgetBaseClass );

			// nullify references to short-cut DOM nodes
			for( var shortCut in this.options.templateShortCuts ) {
				this[ shortCut ] = null;
			}
		},

		/**
		 * @see jQuery.Widget._setOption
		 */
		_setOption: function( key, value ) {
			switch( key ) {
				case 'template':
				case 'templateParams':
				case 'templateShortCuts':
					throw new Error( 'Can not set template related options after initialization' );
			}

			var response = PARENT.prototype._setOption.apply( this, arguments );

			if( key === 'disabled' ) {
				this._trigger( 'disable', null, [value] );
			}

			return response;
		},

		/**
		 * Applies focus to the widget.
		 */
		focus: function() {
			this.element.focus();
		},

		/**
		 * Clone of jQuery.Widget._trigger with the difference that `$.triggerHandler()` instead of
		 * `$.trigger()` is used to trigger the event on `this.element` if `encapsulate` option is
		 * `true`.
		 * @see jQuery.Widget._trigger
		 * @protected
		 *
		 * @param {string} type
		 * @param {jQuery.Event|string} event
		 * @param {*} data
		 * @return {boolean}
		 */
		_trigger: function( type, event, data ) {
			var prop,
				orig,
				callback = this.options[type];

			data = data || {};
			event = $.Event( event );
			event.type = (
				type === this.widgetEventPrefix ? type : this.widgetEventPrefix + type
			).toLowerCase();
			// The original event may come from any element, so we need to reset the target on the
			// new event:
			event.target = this.element[0];

			// Copy original event properties over to the new event:
			orig = event.originalEvent;
			if( orig ) {
				for( prop in orig ) {
					if( !( prop in event ) ) {
						event[prop] = orig[prop];
					}
				}
			}

			this.element[this.options.encapsulate ? 'triggerHandler' : 'trigger']( event, data );
			return !(
				$.isFunction( callback )
					&& callback.apply( this.element[0], [ event ].concat( data ) ) === false
				|| event.isDefaultPrevented()
			);
		}
	} );

}( jQuery ) );
