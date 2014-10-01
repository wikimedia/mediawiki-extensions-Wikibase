/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.wikibase.toolbarbase;

	/**
	 * Cache for jQuery widgets used by the edittoolbar widget.
	 * @type {Object}
	 */
	var cache = {};

	/**
	 * "Edit" toolbar widget
	 * @since 0.4
	 * @extends jQuery.wikibase.toolbarbase
	 *
	 * This widget offers a generic "edit" toolbar which will allow editing-related interaction with
	 * a given widget.
	 * The widget the toolbar shall interact with has to have implemented certain methods listed in
	 * the _requiredMethods attribute. The interaction widget may have an isEmpty() method which
	 * will, in addition to the isValid() method, be queried for en-/disabling the save button.
	 * Apart from the required methods, the interaction widget has to have defined a help message in
	 * its options that will be used as tooltip message.
	 * The toolbar will also trigger a "toggleerror" event on the interaction widget when the error
	 * tooltip that will show up gets hidden.
	 *
	 * @option {jQuery.Widget} interactionWidget
	 *         Name of the widget the toolbar shall interact with.
	 *
	 * @option {Function} [onRemove]
	 *         Function to be triggered when hitting the "remove" button. If omitted, no "remove"
	 *         button will be shown.
	 *         Default: null
	 *
	 * @event afterstartediting
	 *        Triggered after the toolbar has switched to edit mode.
	 *        (1) {jQuery.Event}
	 *
	 * @event afterstopediting
	 *        Triggered after the toolbar has switched to non-edit mode.
	 *        (1) {jQuery.Event}
	 */
	$.widget( 'wikibase.edittoolbar', PARENT, {
		/**
		 * @see jQuery.wikibase.toolbarbase.options
		 */
		options: {
			interactionWidget: null,
			onRemove: null
		},

		/**
		 * Names of methods that are required in the interaction widget to ensure proper toolbar
		 * interaction.
		 * @type {string[]}
		 */
		_requiredMethods: [
			'startEditing',
			'stopEditing',
			'isValid',
			'setError'
		],

		/**
		 * @see jQuery.Widget._create
		 */
		_create: function() {
			var self = this,
				missingMethods;

			if( !this.options.interactionWidget ) {
				throw new Error( 'Interaction widget needs to be defined' );
			}

			PARENT.prototype._create.call( this );

			if( !this.options.interactionWidget.option( 'helpMessage' ) ) {
				throw new Error( 'Interaction widget help message missing' );
			}

			missingMethods = this.checkRequiredMethods();
			if( missingMethods.length > 0 ) {
				throw new Error( 'Required method(s) missing: ' + missingMethods.join( ', ' ) );
			}

			var $toolbar = mw.template( 'wikibase-toolbar', '', '' ).toolbar();
			this.toolbar = $toolbar.data( 'toolbar' );

			var $editGroup;

			if( !cache.editGroup ) {
				// Create just a single edit group using it as a template. By cloning it, the jQuery
				// widget creation process for the edit group can be circumvented.
				$editGroup = mw.template( 'wikibase-toolbareditgroup', '', '' ).toolbareditgroup( {
					displayRemoveButton: $.isFunction( this.options.onRemove )
				} );
				cache.editGroup = $editGroup.data( 'toolbareditgroup' );
			}

			$editGroup = cache.editGroup.clone( {
				displayRemoveButton: $.isFunction( this.options.onRemove )
			} );

			var editGroup = $editGroup.data( 'toolbareditgroup' );

			this.toolbar.addElement( $editGroup );

			// Support promises instead of strings, too, since $.wikibase.claimview does not know
			// immediately after creation which help message to show.
			// TODO: This should be replaced by a dynamic getter so that views can arbitrarily
			// change their help messages anywhere in their lifecycle.
			function addTooltip( helpMessage ) {
				if( editGroup.$tooltipAnchor ) {
					editGroup.$tooltipAnchor.wbtooltip( {
						content: helpMessage
					} );
				}
			}

			var helpMessage = this.options.interactionWidget.option( 'helpMessage' );
			if( helpMessage.done && typeof helpMessage !== 'string' ) {
				helpMessage.done( addTooltip );
			} else {
				addTooltip( helpMessage );
			}

			$editGroup.on( 'toolbareditgroupedit', function( e, callback ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self.options.interactionWidget.startEditing();
			} );

			$editGroup.on( 'toolbareditgroupcancel', function( e, callback ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self.options.interactionWidget.cancelEditing();
			} );

			$editGroup.on( 'toolbareditgroupsave', function( e, callback ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self.options.interactionWidget.stopEditing();
			} );

			$editGroup.on( 'toolbareditgroupremove', function( e ) {
				e.preventDefault(); // Prevent auto-transforming toolbar to non-edit mode.
				self.toggleActionMessage( { message: 'wikibase-remove-inprogress' } );
				self.options.onRemove();
			} );

			var prefix = this.options.interactionWidget.widgetEventPrefix;

			this.element
			.on( prefix + 'afterstartediting', function( event ) {
				editGroup.toEditMode();
				self._trigger( 'afterstartediting' );
			} )
			.on( prefix + 'stopediting', function( event, dropValue ) {
				self.disable();
				// Toggling "action message" here in order to react on pressing the "enter" key.
				if ( !dropValue ) {
					self.toggleActionMessage( { message: 'wikibase-save-inprogress' } );
				}
			} )
			.on( prefix + 'afterstopediting.' + this.widgetName, function( event ) {
				editGroup.toNonEditMode();
				self.enable();
				self.toggleActionMessage( function() {
					editGroup.getButton( 'edit' ).focus();
					self._trigger( 'afterstopediting' );
				} );
			} )
			.on( prefix + 'afterstartediting ' + prefix + 'change', function( event ) {
				var isEmpty = $.isFunction( self.options.interactionWidget.isEmpty )
					&& self.options.interactionWidget.isEmpty();

				var isInitial = $.isFunction( self.options.interactionWidget.isInitialValue )
					&& self.options.interactionWidget.isInitialValue();

				if ( self.options.interactionWidget.isValid() && !isEmpty && !isInitial ) {
					editGroup.enableButton( 'save' );
				} else {
					editGroup.disableButton( 'save' );
				}
			} )
			.on( prefix + 'disable', function( event, disable ) {
				self[disable ? 'disable' : 'enable']();
			} )
			.on( prefix + 'toggleerror', function( event, error ) {
				if ( error && error instanceof wb.RepoApiError ) {
					var $anchor;

					if ( error.action === 'save' ) {
						$anchor = editGroup.getButton( 'save' );
					}
					if ( error.action === 'remove' ) {
						$anchor = editGroup.getButton( 'remove' );
					}

					self.enable();
					self.toggleActionMessage( function() {
						self.displayError( error, $anchor );
					} );
				}
			} );

			// TODO: Is this public assignment really necessary? If it is, document why.
			this.toolbar.editGroup = editGroup;

			$toolbar.appendTo(
				$( '<span/>' ).addClass( 'wb-editsection' ).appendTo( this.$toolbarParent )
			);
		},

		/**
		 * Checks whether all methods required in the interaction widget are defined and will return
		 * the names of any missing methods.
		 * @since 0.4
		 *
		 * @return {string[]}
		 */
		checkRequiredMethods: function() {
			var self = this,
				missingMethods = [];
			$.each( this._requiredMethods, function( i, methodName ) {
				if( !$.isFunction( self.options.interactionWidget[methodName] ) ) {
					missingMethods.push( methodName );
				}
			} );
			return missingMethods;
		},

		/**
		 * Toggles the message displayed instead of the toolbar while performing an (API) action
		 * (saving/removing). Omit action parameter to remove any action message and show the
		 * toolbar again.
		 * @since 0.4
		 *
		 * @param {Object|function} [options] String that is used to get the message key. Assumed to
		 *        be a callback if of type function. You may set the following options:
		 *        message:  The message key of the message to display. If not defined, the toolbar
		 *                  will be shown.
		 *        duration: Fade duration in milliseconds (default: 200).
		 * @param {function} [callback] Function to be called after toggling has been finished
		 */
		toggleActionMessage: function( options, callback ) {
			var self = this,
				actionMessageClass = this.widgetFullName + '-actionmsg',
				actionMsg = this.$toolbarParent.find( '.' + actionMessageClass );

			if ( options === undefined || $.isFunction( options ) ) {
				if ( $.isFunction( options ) ) {
					callback = options;
				}
				options = {};
			}

			if ( options.duration === undefined ) {
				options.duration = 'fast'; //defaults to 200
			}

			if ( !options.message ) { // show toolbar
				if ( !this.$toolbarParent.find( '.' + actionMessageClass ).length ) {
					// If no action message is displayed currently, just trigger the callback.
					if ( $.isFunction( callback ) ) {
						callback();
					}
				} else {
					this.$toolbarParent.find( '.' + actionMessageClass ).stop().fadeToggle(
						options.duration,
						function() {
							self.$toolbarParent.find( '.' + actionMessageClass ).remove();
							self.$toolbarParent.find( '.wikibase-toolbar' ).fadeIn(
								options.duration,
								function() {
									if ( $.isFunction( callback ) ) {
										callback();
									}
								}
							);
						}
					);
				}
			} else { // show message
				actionMsg = $( '<span/>' )
				.addClass( actionMessageClass + ' wb-actionmsg' )
				.append( $( '<span/>' ).text( mw.msg( options.message ) ) )
				.hide();

				actionMsg.appendTo( this.$toolbarParent.find( '.wb-editsection' ) );

				this.$toolbarParent.find( '.wikibase-toolbar' ).hide();

				actionMsg.hide().fadeToggle( options.duration, function() {
					if ( $.isFunction( callback ) ) {
						callback();
					}
				} );
			}
		},

		/**
		 * Displays an error message an visualizes the error state.
		 * @since 0.4
		 *
		 * @param {wb.RepoApiError} error
		 * @param {jQuery} $anchor Node the tooltip shall be attached to.
		 */
		displayError: function( error, $anchor ) {
			var self = this;

			$anchor.wbtooltip( {
				content: error, permanent: true
			} );
			$anchor.data( 'wbtooltip' ).show();

			$anchor.one( 'wbtooltipafterhide', function( e ) {
				self.element.removeClass( 'wb-error' ).addClass( 'wb-edit' );
				self.options.interactionWidget.setError();
				if( $anchor.data( 'wbtooltip' ) ) {
					$anchor.data( 'wbtooltip' ).destroy();
				}
			} );
		},

		/**
		 * @see $.wikibase.toolbarbase.destroy
		 */
		destroy: function() {
			this.toolbar.editGroup.destroy();
			PARENT.prototype.destroy.call( this );
		}

	} );

	// We have to override this here because $.widget sets it no matter what's in
	// the prototype
	$.wikibase.edittoolbar.prototype.widgetFullName = 'wb-edittoolbar';

}( mediaWiki, wikibase, jQuery ) );
