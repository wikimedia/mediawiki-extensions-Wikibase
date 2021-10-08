( function () {
	'use strict';

	var PARENT = $.Widget;

	/**
	 * Base prototype for all widgets using the `mw.wbTemplate` templating system.
	 * Uses `jQuery.fn.applyTemplate`.
	 * @see mw.wbTemplate
	 * @class jQuery.ui.TemplatedWidget
	 * @abstract
	 * @extends jQuery.Widget
	 * @uses jQuery.fn
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {string} options.template
	 *        Name of a template to use. The template should feature a single root node which will
	 *        be replaced by the widget's subject node.
	 * @param {*[]} [options.templateParams]
	 *        Parameters injected into the template on its initial construction. A parameter can be
	 *        what is compatible with `mw.wbTemplate` but can also be a function which will be
	 *        executed in the widget's context and provide the parameter's value by its return
	 *        value.
	 * @param {Object} [options.templateShortCuts]
	 *        A map pointing from a short-cut name to a node within the widget's template. The map
	 *        is used during the widget creation process to automatically add members to the widget
	 *        object that may be accessed during the widget's life time.
	 *        The location of the target node has to be given as a valid jQuery query expression,
	 *        i.e. `{ $foo: li.example-class > .foo }` results in being able to access the selected
	 *        node using `this.$foo` within the widget instance.
	 * @param {boolean} [options.encapsulate=false]
	 *        Whether non-native `jQuery.Widget` events shall be triggered on the widget's node only
	 *        and not bubble up the DOM tree (using `jQuery.triggerHandler()` instead of
	 *        `jQuery.trigger()`).
	 */
	/**
	 * @event disable
	 * Triggered whenever the widget is disabled (after disabled state has been set).
	 * @param {jQuery.Event}
	 * @param {boolean} Whether widget has been dis- oder enabled.
	 */
	$.widget( 'ui.TemplatedWidget', PARENT, {
		/**
		 * @see jQuery.Widget.options
		 */
		options: $.extend( true, {}, PARENT.prototype.options, {
			template: null,
			templateParams: [],
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
		 *
		 * @see jQuery.Widget._create
		 * @protected
		 *
		 * @throws {Error} if `template` option is not specified.
		 */
		_create: function () {
			if ( !this.options.template ) {
				throw new Error( 'template needs to be specified' );
			}

			// FIXME: Find sane way to detect that template is applied already.
			if ( this.element.contents().length === 0 ) {
				this._applyTemplate();
			}

			this._createTemplateShortCuts();

			PARENT.prototype._create.apply( this );
		},

		/**
		 * @see jQuery.fn.applyTemplate
		 * @private
		 */
		_applyTemplate: function () {
			var templateParams = [],
				self = this;

			// template params which are functions are callbacks to be called in the widget's context
			this.options.templateParams.forEach( function ( value ) {
				if ( typeof value === 'function' ) {
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
		 * Creates the short-cuts to DOM nodes within the template's DOM structure as specified in
		 * the `templateShortCuts` option.
		 *
		 * @private
		 *
		 * @throws {Error} if no DOM node is found using a specified selector.
		 */
		_createTemplateShortCuts: function () {
			var shortCuts = this.options.templateShortCuts,
				shortCut, shortCutSelector, $shortCutTarget;

			for ( shortCut in shortCuts ) {
				shortCutSelector = shortCuts[ shortCut ];
				$shortCutTarget = this.element.find( shortCutSelector );
				if ( $shortCutTarget.length < 1 ) {
					throw new Error( 'Template "' + this.option( 'template' ) + '" has no DOM node '
						+ ' selectable via the jQuery expression "' + shortCutSelector + '"' );
				}
				this[ shortCut ] = $shortCutTarget;

			}
		},

		/**
		 * @see jQuery.Widget.destroy
		 */
		destroy: function () {
			PARENT.prototype.destroy.call( this );

			this.element.removeClass( this.widgetBaseClass );

			// nullify references to short-cut DOM nodes
			for ( var shortCut in this.options.templateShortCuts ) {
				this[ shortCut ] = null;
			}
		},

		/**
		 * @see jQuery.Widget._setOption
		 * @protected
		 *
		 * @param {string} key
		 * @param {*} value
		 * @return {jQuery.Widget}
		 *
		 * @throws {Error} when trying to set `template`, `templateParams` or `templateShortCuts`
		 *         option.
		 */
		_setOption: function ( key, value ) {
			switch ( key ) {
				case 'template':
				case 'templateParams':
				case 'templateShortCuts':
					throw new Error( 'Can not set template related options after initialization' );
			}

			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'disabled' ) {
				this._trigger( 'disable', null, [ value ] );
			}

			return response;
		},

		/**
		 * Applies focus to the widget.
		 */
		focus: function () {
			this.element.trigger( 'focus' );
		},

		/**
		 * Clone of jQuery.Widget._trigger with the difference that `$.triggerHandler()` instead of
		 * `$.trigger()` is used to trigger the event on `this.element` if `encapsulate` option is
		 * `true`.
		 *
		 * @see jQuery.Widget._trigger
		 * @protected
		 *
		 * @param {string} type
		 * @param {jQuery.Event|string} event
		 * @param {*} data
		 * @return {boolean}
		 */
		_trigger: function ( type, event, data ) {
			var prop,
				orig,
				callback = this.options[ type ];

			data = data || {};
			event = $.Event( event );
			event.type = (
				type === this.widgetEventPrefix ? type : this.widgetEventPrefix + type
			).toLowerCase();
			// The original event may come from any element, so we need to reset the target on the
			// new event:
			event.target = this.element[ 0 ];

			// Copy original event properties over to the new event:
			orig = event.originalEvent;
			if ( orig ) {
				for ( prop in orig ) {
					if ( !( prop in event ) ) {
						event[ prop ] = orig[ prop ];
					}
				}
			}

			this.element[ this.options.encapsulate ? 'triggerHandler' : 'trigger' ]( event, data );
			return !(
				typeof callback === 'function'
					&& callback.apply( this.element[ 0 ], [ event ].concat( data ) ) === false
				|| event.isDefaultPrevented()
			);
		}
	} );

}() );
