/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT =  $.Widget;

	/**
	 * Base prototype for all widgets which use the mw.template templating system to create a basic
	 * DOM structure for internal usage.
	 *
	 * @constructor
	 * @abstract
	 * @extends jQuery.Widget
	 * @since 0.4
	 *
	 * @option template {String} Name of a template to use rather than the default. Of course, the
	 *         given template has to be fully compatible to the default one. This means that should
	 *         have all required template parameters and classes used to identify certain nodes.
	 *         The template should have a structure with a single root node which will then be
	 *         replaced by the Widget's subject node.
	 */
	$.TemplatedWidget = wb.utilities.inherit( PARENT, function() {
		// this constructor will only be called in $.widget() when registering a new widget with
		// this prototype as base. What we do here is to break the prototype chain so those fields
		// will not be modified when inherited!
		// basically, this is the same as what jQuery.widget() does with jQuery.Widget.options.
		this.widgetTemplateParams = $.extend( {}, this.widgetTemplateParams );
		this.widgetTemplateShortCuts = $.extend( {}, this.widgetTemplateShortCuts );
	}, {
		/**
		 * Default options
		 * @see jQuery.Widget.options
		 */
		options: $.extend( true, {}, PARENT.prototype.options, {
			template: null
		} ),

		/**
		 * The name of the template used by default. The template should have a structure with a
		 * single root node which will then be replaced by the Widget's subject node.
		 *
		 * @since 0.4
		 * @type String
		 */
		widgetTemplate: null,

		/**
		 * Parameters given to the template on its initial construction. A parameter can be what is
		 * compatible with mw.template but can also be a function which will be executed in the
		 * widget's context and provide the parameter's value by its return value.
		 *
		 * @since 0.4
		 * @type Array
		 */
		widgetTemplateParams: [],

		/**
		 * A map pointing from the name of a field of the Widget object which should act as a short
		 * cut to a node within the widget's template. The location of the target node has to be
		 * given as a valid jQuery query expression. e.g. 'li.example-class > .foo'.
		 *
		 * @example { '$value': '.valueview-value', '$preview': '.valueview-preview' }
		 * @descr this.$preview will hold the DOM node (wrapped inside a jQuery object) which
		 *        matches above expression.
		 *
		 * @since 0.4
		 * @type Object
		 */
		widgetTemplateShortCuts: {},

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var templateParams = [],
				self = this;

			// template params which are functions are callbacks to be called in the widget's context
			$.each( this.widgetTemplateParams, function() {
				var value = this;
				if( $.isFunction( value ) ) {
					value = value.call( self );
				}
				templateParams.push( value );
			} );

			// the element node will be preserved, no matter whether it is of the same kind as the
			// template's root node (it is assumed that the template has a root node)
			this.element.addClass( this.widgetBaseClass );
			this.element.applyTemplate( this._getTemplateName(), templateParams );

			this._createTemplateShortCuts();
		},

		/**
		 * Returns the name of the widget's template in use.
		 * @return {String}
		 */
		_getTemplateName: function() {
			return  this.option( 'template' ) || this.widgetTemplate;
		},

		/**
		 * Creates the short cuts to DOM nodes within the template's DOM structure as specified in
		 * this.widgetTemplateShortCuts.
		 *
		 * @since 0.4
		 */
		_createTemplateShortCuts: function() {
			var shortCuts = this.widgetTemplateShortCuts,
				shortCut, shortCutSelector, $shortCutTarget;

			for( shortCut in shortCuts ) {
				shortCutSelector = shortCuts[ shortCut ];
				$shortCutTarget = this.element.find( shortCutSelector );
				if( $shortCutTarget.length < 1 ) {
					throw new Error( 'Template "' + this._getTemplateName() + '" has no DOM node' +
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
			for( var shortCut in this.widgetTemplateShortCuts ) {
				this[ shortCut ] = null;
			}
		},

		/**
		 * @see jQuery.widget._setOption
		 * We are using this to disallow changing the 'template' option afterwards
		 */
		_setOption: function( key, value ) {
			if( key === 'template' ) {
				throw new Error( 'Can not set template after initialization' );
			}
			PARENT.prototype._setOption.call( key, value );
		}
	} );

}( mediaWiki, wikibase, jQuery ) );
