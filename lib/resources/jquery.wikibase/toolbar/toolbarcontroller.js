/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * Available toolbar types.
	 * TODO: create a registry for allowing adding additional toolbar types
	 *
	 * @type {string[]}
	 */
	var TOOLBAR_TYPES = ['addtoolbar', 'edittoolbar', 'removetoolbar'];

	/**
	 * Toolbar controller widget
	 *
	 * The toolbar controller initializes and manages toolbar widgets. Toolbar definitions are
	 * registered via the jQuery.toolbarcontroller.definition() method. When initializing the
	 * toolbar controller, the ids or widget names of the registered toolbar definitions that the
	 * controller shall initialize are passed as options.
	 *
	 * @since 0.4
	 *
	 * @option addtoolbar {string[]} List of toolbar definition ids/widget names that are registered
	 *         as "add" toolbars and shall be initialized.
	 *         Default: []
	 *
	 * @option edittoolbar {string[]} List of toolbar definition ids/widget names that are
	 *         registered as "edit" toolbars and shall be initialized.
	 *         Default: []
	 *
	 * @option removetoolbar {string[]} List of toolbar definition ids/widget names that are
	 *         registered as "remove" toolbars and shall be initialized.
	 *         Default: []
	 */
	$.widget( 'wikibase.toolbarcontroller', {
		/**
		 * Options
		 * @type {Object}
		 */
		options: {
			addtoolbar: [],
			edittoolbar: [],
			removetoolbar: []
		},

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			this.initToolbars();
		},

		/**
		 * Initializes the toolbars for the nodes that are descendants of the node the toolbar
		 * controller is initialized on.
		 *
		 * @since 0.4
		 * @param {boolean} [isPending] Whether element that triggered the toolbar
		 *        (re-)initialization is in a pending state.
		 *
		 * @throws {Error} in case a given toolbar ID is not registered for the toolbar type given.
		 * @throws {Error} if the callback provided in an event definition is not a function.
		 */
		initToolbars: function( isPending ) {
			var self = this;

			$.each( TOOLBAR_TYPES, function( i, type ) {
				$.each( self.options[type], function( j, id ) {
					var def = $.wikibase.toolbarcontroller.definition( type, id );
					if( !def ) {
						throw new Error( 'Missing toolbar controller definition for "' + id + '"' );
					}

					var options = type === 'edittoolbar' && isPending ?
						$.extend( {}, def.options, { enableRemove: !isPending } )
						: def.options;

					// The node the toolbar shall be initialized on.
					var $initNode = self.element.find( def.selector || ':' + def.widget.fullName );

					if ( def.events ) {
						// Get callbacks for "create" and "destroy" keywords as per toolbar
						// definition:
						var defaultCallbacks = createDefaultEventHandlers( type, options );

						// Detach all event handlers first in order to not end up with having the
						// handler attached multiple times. This cannot be done along with
						// re-attaching the handlers since multiple event handlers may be registered
						// for the same event.
						$.each( def.events, function( eventNames, callbackOrKeyword ) {
							var namespacedEvents = assignNamespaces(
								eventNames, [self.widgetName, self.widgetName + type]
							);
							$initNode.off( namespacedEvents );
						} );

						// Attach event handlers for toolbars that shall be created upon certain
						// events:
						$.each( def.events, function( eventNames, callbackOrKeyword ) {
							var callback =
								defaultCallbacks[ callbackOrKeyword ] || callbackOrKeyword,
								namespacedEvents = assignNamespaces(
									eventNames, [self.widgetName, self.widgetName + type]
								);

							if( !$.isFunction( callback ) ) {
								throw new Error( 'No callback or known default action given for '
									+ 'event "' + eventNames + '"' );
							}

							$initNode.on( namespacedEvents, function( event ) {
								callback( event, $( event.target ) );
							} );
						} );
					}

					if ( !def.events || def.widget ) {
						$initNode[type]( options );
					}
				} );
			} );

			this.initEventListeners();
		},

		/**
		 * Initializes event listeners for all toolbars defined in the options. This will make sure
		 * that when a new widget toolbars are defined for is initialized, its toolbar(s) will
		 * be initialized as well.
		 * @since 0.4
		 */
		initEventListeners: function() {
			var self = this;

			this.element.off( '.' + this.widgetName );

			$.each( TOOLBAR_TYPES, function( i, type ) {
				$.each( self.options[type], function( j, definitionId ) {
					var def = $.wikibase.toolbarcontroller.definition( type, definitionId ),
						eventPrefix = def.eventPrefix
							|| ( def.widget ? def.widget.prototype.widgetEventPrefix : '' ),
						baseClass = def.baseClass
							|| ( def.widget ? def.widget.prototype.widgetBaseClass : null );

					// Listen to widget's native "create" event in order to initialize toolbars
					// corresponding to the widget just instantiated.
					self.element.on( eventPrefix + 'create.' + self.widgetName, function( event ) {
						var $target = $( event.target ),
							isPending = baseClass
								&& (
									$target.hasClass( baseClass + '-new' )
									|| $target.find( baseClass + '-new' ).length > 0
								);

						if ( type === 'addtoolbar' ) {
							// Initialize toolbars that are not initialized already:
							self.initToolbars( isPending );
						} else if ( type === 'edittoolbar' ) {
							$( event.target ).edittoolbar(
								$.extend( {}, def.options, { enableRemove: !isPending } )
							);
						}
					} );

				} );
			} );

		}

	} );

	/**
	 * Returns an object with fields named after the default toolbar definition event actions
	 * ("create" and "destroy"). Each field holds a callback for handling the related action.
	 * @since 0.4
	 *
	 * @param {string} type
	 * @param {Object} toolbarInitOptions
	 * @return {Object}
	 */
	function createDefaultEventHandlers( type, toolbarInitOptions ) {
		return {
			'create': function( event ) {
				$( event.target )[type]( toolbarInitOptions );
			},
			destroy: function( event ) {
				var $node = $( event.target );
				if ( $node.data( type ) ) {
					$node.data( type ).destroy();
					$node.removeData( type );
					$node.children(
						'.' + $.wikibase[ type ].prototype.widgetBaseClass
					).remove();
				}
			}
		};
	}

	/**
	 * Assigns namespaces to event names passed in as a string.
	 * @since 0.4
	 *
	 * @param {string} eventNames
	 * @param {string[]} namespaces
	 * @return {string}
	 */
	function assignNamespaces( eventNames, namespaces ) {
		eventNames = eventNames.split( ' ' );

		// Add an empty string to assign namespaces to the last non-empty event name via join():
		eventNames.push( '' );

		return eventNames.join( '.' + namespaces.join( '.' ) + ' ' );
	}

}( mediaWiki, wikibase, jQuery ) );
