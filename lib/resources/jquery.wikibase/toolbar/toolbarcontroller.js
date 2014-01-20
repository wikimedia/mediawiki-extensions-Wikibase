/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $ ) {
	'use strict';

	/**
	 * Available toolbar types.
	 * TODO: create a registry for allowing adding additional toolbar types
	 *
	 * @type {string[]}
	 */
	var TOOLBAR_TYPES = ['addtoolbar', 'edittoolbar', 'removetoolbar', 'movetoolbar'];

	/**
	 * Toolbar controller widget
	 *
	 * The toolbar controller initializes and manages toolbar widgets. Toolbar definitions are
	 * registered via the jQuery.toolbarcontroller.definition() method. When initializing the
	 * toolbar controller, the ids of the registered toolbar definitions that the controller shall
	 * initialize are passed as options.
	 * The toolbar controller does not parse the existing DOM structure when being initialized, it
	 * listens to the events registered by a toolbar definition. In order to have the desired
	 * toolbars created by the controller, it needs to be initialized on some parent node of the DOM
	 * structure it should manage toolbars in before the events defined in toolbar definitions are
	 * triggered (e.g. before a widget, a toolbar shall interact with, is created).
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
	 *
	 * @option movetoolbar {string[]} List of toolbar definition ids/widget names that are
	 *         registered as "move" toolbars and shall be initialized.
	 *         Default: []
	 */
	$.widget( 'wikibase.toolbarcontroller', {
		/**
		 * @type {Object}
		 */
		options: {
			addtoolbar: [],
			edittoolbar: [],
			removetoolbar: [],
			movetoolbar: []
		},

		/**
		 * @type {Object}
		 */
		_registeredEventHandlers: {},

		/**
		 * @see jQuery.Widget._create
		 *
		 * @throws {Error} in case a given toolbar ID is not registered for the toolbar type given.
		 */
		_create: function() {
			var self = this;

			$.each( TOOLBAR_TYPES, function( i, type ) {
				$.each( self.options[type], function( j, id ) {
					var def = $.wikibase.toolbarcontroller.definition( type, id );

					if( !def ) {
						throw new Error( 'Missing toolbar controller definition for "' + id + '"' );
					}

					$.each( def.events, function( eventNames, callback ) {
						self.registerEventHandler( type, id, eventNames, callback );
					} );
				} );
			} );
		},

		/**
		 * Registers an event handler.
		 * @since 0.5
		 *
		 * @param {string} toolbarType
		 * @param {string} toolbarId
		 * @param {string} eventNames Space-separated string containing the event names to register
		 *                 a callback handler for. It is assumed that this string is uniquely used
		 *                 for a toolbar type with a specific id: For one toolbar, no additional
		 *                 handler should be registered with exactly the same eventNames string.
		 * @param {Function} callback
		 * @param {boolean} [replace] If true, the event handler currently registered for the
		 *                  specified argument combination will be replaced.
		 *
		 * @throws {Error} if the callback provided in an event definition is not a function.
		 */
		registerEventHandler: function( toolbarType, toolbarId, eventNames, callback, replace ) {
			if( !$.isFunction( callback ) ) {
				throw new Error( 'No callback or known default action given for event "'
					+ eventNames + '"' );
			}

			if( !this._registeredEventHandlers[toolbarType] ) {
				this._registeredEventHandlers[toolbarType] = {};
			}

			if( !this._registeredEventHandlers[toolbarType][toolbarId] ) {
				this._registeredEventHandlers[toolbarType][toolbarId] = {};
			}

			if( !replace && this._registeredEventHandlers[toolbarType][toolbarId][eventNames] ) {
				return;
			}

			var self = this;

			// The namespace needs to be very specific since instances of the the same
			// toolbar may listen to the same event(s) on the same node:
			var eventNamespaces = [this.widgetName, this.widgetName + toolbarType + toolbarId],
				namespacedEventNames = assignNamespaces( eventNames, eventNamespaces );

			this.element
			// Prevent attaching event handlers twice:
			.off( namespacedEventNames )
			.on( namespacedEventNames, function( event ) {
				var callbacks = self._findCallbacks( event );

				if( callbacks ) {
					event.data = event.data || {};

					event.data.toolbar = {
						id: toolbarId,
						type: toolbarType
					};

					for( var i = 0; i < callbacks.length; i++ ) {
						callbacks[i]( event, self );
					}
				}
			} );

			this._registeredEventHandlers[toolbarType][toolbarId][eventNames] = callback;
		},

		/**
		 * Deregister an event handler for certain events and removes the actual event handler
		 * registration from the toolbar controller's node if the event is not referenced by
		 * remaining handlers.
		 * @since 0.5
		 *
		 * @param {string} toolbarType
		 * @param {string} toolbarId
		 * @param {string} eventNames It is assumed that the specified eventNames string is exactly
		 *                 the same as the one specified when registering a handler.
		 *
		 * @throws {Error} if no event handler is registered for the specified set of arguments.
		 */
		deregisterEventHandler: function( toolbarType, toolbarId, eventNames ) {
			if(
				!this._registeredEventHandlers[toolbarType]
				|| !this._registeredEventHandlers[toolbarType][toolbarId]
				|| !this._registeredEventHandlers[toolbarType][toolbarId][eventNames]
			) {
				throw new Error( 'No event handler registered for event names "' + eventNames + '" '
					+ ' on ' + toolbarType + ' with id ' + toolbarId );
			}

			// Remove handler from registered event handler cache:
			delete this._registeredEventHandlers[toolbarType][toolbarId][eventNames];

			// Check each single event name if is not not used by another callback:
			var registeredHandlers =  this._registeredEventHandlers[toolbarType][toolbarId],
				eventNamesToRemove = eventNames.split( ' ' );

			$.each( registeredHandlers, function( regEventNames, c ) {
				var regSingleEventNames = regEventNames.split( ' ' );

				eventNamesToRemove = $.grep( eventNamesToRemove, function( eventName ) {
					return ( $.inArray( eventName, regSingleEventNames ) === -1 );
				} );

				if( !eventNamesToRemove.length ) {
					// No event names to remove left.
					return false;
				}
			} );

			if( !eventNamesToRemove.length ) {
				// All events whose handlers shall be removed are still referenced by other
				// handlers.
				return;
			}

			var eventNamespaces = [this.widgetName, this.widgetName + toolbarType + toolbarId],
				namespacedEventNamesToRemove = assignNamespaces(
					eventNamesToRemove.join( ' ' ), eventNamespaces
				);

			this.element.off( namespacedEventNamesToRemove );
		},

		/**
		 * Finds the event handler callback(s) for a certain event.
		 * @since 0.5
		 *
		 * @param {jQuery.Event} event
		 * @return {Function[]|null}
		 */
		_findCallbacks: function( event ) {
			var self = this,
				$target = $( event.target ),
				callbacks = [];

			$.each( TOOLBAR_TYPES, function( i, type ) {
				$.each( self.options[type], function( j, id ) {
					var def = $.wikibase.toolbarcontroller.definition( type, id );

					if(
						!self._registeredEventHandlers[type]
						|| !self._registeredEventHandlers[type][id]
					) {
						return true;
					}

					$.each( self._registeredEventHandlers[type][id], function( eventNames, c ) {
						var eventNameMatch = $.inArray( event.type, eventNames.split( ' ' ) ) !== -1,
							selectorMatch = self.element.find( def.selector ).has( $target );

						if( eventNameMatch && selectorMatch ) {
							callbacks.push( c );
						}
					} );
				} );
			} );

			return ( callbacks.length ) ? callbacks : null;
		},

		/**
		 * Destroys a toolbar.
		 * @since 0.5
		 *
		 * @param {jquery.wikibase.toolbarbase} toolbar
		 */
		destroyToolbar: function( toolbar ) {
			// Toolbar might have been removed from the DOM already by some other destruction
			// mechanism.
			if( toolbar ) {
				toolbar.destroy();
				toolbar.element.removeData( toolbar.widgetName );
				toolbar.toolbar.element.remove();
			}
		}

	} );

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

}( jQuery ) );
