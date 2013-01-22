/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, dt, $ ) {
	'use strict';

	var PARENT = $.TemplatedWidget;

/**
 * View for displaying and editing Wikibase Snaks.
 * @since 0.3
 *
 * @option value {Object|wb.Snak|null} The snak this view should represent. If set to null (by
 *         default), an empty view will be served, ready to take some input by the user. The value
 *         can be overwritten later, by using the value() or snak() functions.
 *
 * @event startediting: Called before edit mode gets initialized.
 *        (1) {jQuery.Event} event
 *
 * @event stopediting: Called before edit mode gets closed.
 *        (1) {jQuery.Event} event
 *        (2) {Boolean} dropValue Will be false if the value will be kept.
 *        (3) {wb.Snak|null} newSnak The Snak which will be displayed after editing has stopped.
 *            Normally this is the Snak representing the last state of the view during edit mode
 *            but can also be the Snak from before edit mode in case editing has been cancelled.
 *
 * @event afterstopediting: Called after edit mode got closed.
 *        (1) {jQuery.Event} event
 *        (2) {Boolean} droppedValue false if the value edited during edit mode has been preserved.
 */
$.widget( 'wikibase.snakview', PARENT, {
	widgetName: 'wikibase-snakview',
	widgetBaseClass: 'wb-snakview',

	/**
	 * (Additional) default options
	 * @see jQuery.Widget.options
	 */
	options: {
		template: 'wb-snak',
		templateShortCuts: {
			'$property': '.wb-snak-property',
			'$snakValue': '.wb-snak-value',
			'$snakTypeSelector': '.wb-snak-typeselector'
		},
		value: {
			property: null,
			snaktype: wb.PropertyValueSnak.TYPE
		}
	},

	/**
	 * The DOM node of the entity selector for choosing a property or the node with plain text of
	 * the properties label. This is a selector widget only the first time in edit mode.
	 * @type jQuery
	 */
	$property: null,

	/**
	 * The DOM node of the Snak's value or some message if the value is not supported.
	 * TODO later we will support 'novalue' and 'somevalue' snaks which will probably be displayed
	 *      in this node as well somehow.
	 * @type jQuery
	 */
	$snakValue: null,

	/**
	 * The DOM node of the Snak type selector.
	 * @type jQuery
	 */
	$snakTypeSelector: null,

	/**
	 * Variation object responsible for presenting the essential parts of a certain kind of Snak.
	 * @type jQuery.wikibase.snakview.variations.Variation
	 */
	_variation: null,

	/**
	 * Keeps track of values from previously used variations. This allows to display the same value
	 * when using a previously used variation again during one edit-mode session.
	 * @type Object
	 */
	_recentVariationValues: null,

	/**
	 * The property of the Snak currently represented by the view.
	 * @type {String}
	 */
	_propertyId: null,

	/**
	 * The Snak type of the Snak currently represented by the view.
	 * @type {String}
	 */
	_snakType: null,

	/**
	 * The Snak from before edit mode has been entered.
	 * @type wb.Snak|null
	 */
	_initialSnak: null,

	/**
	 * @type Boolean
	 */
	_isInEditMode: false,

	/**
	 * Caching whether to move the focus from the property input to the value input after pressing
	 * the TAB key.
	 * @type Boolean
	 */
	_tabToValueView: false,

	/**
	 * @see jQuery.Widget._create
	 */
	_create: function() {
		// apply template to this.element:
		PARENT.prototype._create.call( this );

		this._recentVariationValues = {};

		// set value, can be a wb.Snak or plain Object as wb.Snak.toMap() or just pieces of it
		this.value( this.option( 'value' ) || {} );

		if( !this.snak() ) {
			// If no Snak is represented, offer UI to build one.
			// This clearly implies draw() since it requires visual changes!
			this.startEditing();
		}
	},

	/**
	 * Returns an input element with initialized entity selector for selecting entities.
	 * @since 0.3
	 *
	 * @return {jQuery}
	 */
	_buildPropertySelector: function() {
		var self = this,
			language = mw.config.get( 'wgUserLanguage' );

		return $( '<input/>' ).entityselector( {
			url: mw.util.wikiScript( 'api' ),
			language: language,
			type: 'property',
			entityStore: wb.entities
		} )
		.prop( 'placeholder', mw.msg( 'wikibase-snakview-property-input-placeholder' ) )
		.on( 'blur', function( event ) {
			self._tabToValueView = false;
		} )
		.eachchange( function( oldValue ) {
			// remove out-dated variations
			if( self._variation ) {
				self.propertyId( null );
			}
		} )
		.on( 'entityselectorselect', function( e, ui ) {
			// entity chosen in entity selector but we still need the data type of the entity, so
			// we have to make a separate API call:
			var api = new wb.RepoApi();

			// display spinner as long as the value view is loading
			self.$snakValue.empty().append(
				$( '<div/>' ).append( $( '<span/>' ).addClass( 'mw-small-spinner' ) )
			);

			api.getEntities( ui.item.id, null ).done( function( response ) {
				var entity = response.entities[ ui.item.id ],
					dataTypeId = entity.datatype,
					dataType = dt.getDataType( dataTypeId ),
					label;

				if( entity.labels && entity.labels[ language ] ) {
					label = entity.labels[ language ].value;
				}

				// update local store with newest information about selected property
				// TODO: create more sophisticated local store interface rather than accessing
				//       wb.entities directly
				wb.entities[ ui.item.id ] = {
					label: label,
					datatype: dataType.getId(),
					url: ui.item.url
				};

				self.propertyId( ui.item.id );

				// Since it takes a while for the value view to gather its data from the API,
				// the property might not be valid anymore aborting the rendering of the value
				// view.
				if( self._tabToValueView && self._variation ) {
					self._variation.focus();
				}
			} );
		} );
	},

	/**
	 * @see $.widget.destroy
	 */
	destroy: function() {
		var snakTypeSelector = this._getSnakTypeSelector();
		if( snakTypeSelector ) {
			snakTypeSelector.destroy();
			snakTypeSelector.element.remove();
		}
		$.Widget.prototype.destroy.call( this );
	},

	/**
	 * Starts the edit mode where the snak can be edited.
	 * @since 0.3
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	startEditing: $.NativeEventHandler( 'startEditing', {
		// don't start edit mode or trigger event if in edit mode already:
		initially: function( e ) {
			if( this.isInEditMode() ) {
				e.cancel();
			}
		},
		// start edit mode if event doesn't prevent default:
		natively: function( e ) {
			var self = this;

			this._initialSnak = this.snak();
			this._isInEditMode = true;
			this.draw();

			// attach keyboard input events
			this.element.on( 'keydown', function( event ) {
				var propertySelector = self._getPropertySelector();

				self._leavePropertyInput = false;

				if ( event.keyCode === $.ui.keyCode.ESCAPE ) {
					self.cancelEditing();
				} else if ( event.keyCode === $.ui.keyCode.ENTER ) {
					if ( !propertySelector || event.target !== propertySelector.element[0] ) {
						self.stopEditing();
					}
				} else if ( event.keyCode === $.ui.keyCode.TAB && !self._variation ) {
					// When pressing TAB in the property input element while the value input element
					// does not yet exist, we assume that the user wants to auto-complete/select the
					// currently suggested property and tab into the value element. Since the API
					// needs to be queried to construct the correct value input, the intended action
					// needs to be cached and triggered as soon as the value input has been created.
					if ( propertySelector && event.target === propertySelector.element[0] ) {
						if ( self._getPropertySelector().validateInput() ) {
							// Have the current property input validated instead of just blurring
							// since the value input element will not appear if the property is not
							// valid and setting the focus would remain unresolved.
							// We do not blur automatically. If the user decides to manually focus
							// another element while the value input element is loading, cancel the
							// cached tab event.
							self._getPropertySelector().widget().data( 'menu' ).select(
								$.Event( 'programmatic' )
							);
							self._tabToValueView = true;
							event.preventDefault();
						} else if ( self._getPropertySelector().selectedEntity() !== null ) {
							// A property has already been selected (e.g. selecting a suggestion
							// with the keyboard's arrow keys and then pressing TAB).
							self._tabToValueView = true;
							event.preventDefault();
						}
					}
				}
				// no point in propagating event after having destroyed the event's target
				if ( !self.isInEditMode() ) {
					event.stopImmediatePropagation();
				}
			} );

			if ( this._getPropertySelector() !== null ) {
				this._getPropertySelector().element.focus();
			} else if( this._variation ) {
				this._variation.focus();
			}
		}
	} ),

	/**
	 * Ends the edit mode where the snak can be edited.
	 * @since 0.3
	 *
	 * @param {Boolean} [dropValue] If true, the value from before edit mode has been started will
	 *        be reinstated. false by default. Consider using cancelEditing() instead.
	 * @return {undefined} (allows chaining widget calls)
	 */
	stopEditing: $.NativeEventHandler( 'stopEditing', {
		// don't stop edit mode or trigger event if not in edit mode currently:
		initially: function( e, dropValue ) {
			if( !this.isInEditMode() ) {
				e.cancel();
			}
			var snak;

			if( dropValue ) {
				// cancel edit, or no variation, e.g. because no valid data type is chosen
				snak = this._initialSnak;
			} else if( !this._variation ) {
				snak = null;
			} else {
				snak = this.snak();
			}

			e.handlerArgs = [ !!dropValue, snak ];
		},
		// start edit mode if custom event handlers didn't prevent default:
		natively: function( e, dropValue, newSnak ) {
			this._isInEditMode = false;
			this._initialSnak = null;

			// update view; will remove edit interfaces and represent value statically
			this._setValue( newSnak !== null ? newSnak.toMap() : {} ); // triggers this.draw()
			// TODO: should throw an error somewhere when trying to leave edit mode while
			//  this.snak() still returns null. For now setting {} is a simple solution for non-
			//  existent error handling in the snak UI

			// forget about values set in different variations
			this._recentVariationValues = {};

			this._trigger( 'afterStopEditing', null, [ dropValue, newSnak ] );
		}
	} ),

	/**
	 * short-cut for stopEditing( false ). Closes the edit view and restores the value from before
	 * the edit mode has been started.
	 * @since 0.3
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	cancelEditing: function() {
		return this.stopEditing( true ); // stop editing and drop value
	},

	/**
	 * Returns whether the Snak is editable at the moment.
	 * @since 0.3
	 *
	 * @return Boolean
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * Returns the property selector for choosing the Snak's property. Returns null if the Snak is
	 * created already and has a Property (once created, the Property is immutable).
	 * @since 0.3
	 *
	 * @return jQuery.wikibase.entityselector|null
	 */
	_getPropertySelector: function() {
		if( this.$property ) {
			return this.$property.children().first().data( 'entityselector' ) || null;
		}
		return null;
	},

	/**
	 * Returns the Snak type selector for choosing the Snak's type. Returns null if the Snak is
	 * created already and has a Property (once created, the Property is immutable).
	 * @since 0.3
	 *
	 * @return jQuery.wikibase.snakview.SnakTypeSelector|null
	 */
	_getSnakTypeSelector: function() {
		if( this.$snakTypeSelector ) {
			return this.$snakTypeSelector.children().first().data( 'snaktypeselector' ) || null;
		}
		return null;
	},

	/**
	 * Returns the initial value from before edit mode has been entered. If not in edit mode, this
	 * will return the same as value().
	 *
	 * @return {Object}
	 */
	initialValue: function() {
		return this.isInEditMode() ? this.initialSnak().toMap() : this._getValue();
	},

	/**
	 * Just like initialValue() but returns a Snak object or null if there was no Snak set before
	 * edit mode.
	 *
	 * @return {wb.Snak|null}
	 */
	initialSnak: function() {
		return this.isInEditMode() ? this._initialSnak : this.snak();
	},

	/**
	 * Returns an object representing the currently displayed Snak. This is equivalent to the JSON
	 * structure of a Snak, except that it does not have to be complete. For example for a
	 * PropertyValueSnak where only the property and snak types are chosen but the value has not
	 * been entered yet, the returned Object would not have a field for the value either.
	 *
	 * @since 0.3
	 *
	 * @param {Object|wb.Snak|null} [value]
	 * @return {wb.Snak|null|undefined} undefined in case value() is called to set the value
	 */
	value: function( value ) {
		if( value === undefined ) {
			return this._getValue();
		}
		if( value !== null && typeof value !== 'object' ) {
			throw new Error( 'The given value has to be a plain object, an instance of' +
				' wikibase.Snak, or null' );
		}
		this._setValue( ( value instanceof wb.Snak ) ? value.toMap() : value );
	},

	/**
	 * Private getter for this.value()
	 * @since 0.3
	 *
	 * @return Object
	 */
	_getValue: function() {
		if( !this._variation ) {
			return {};
		}

		return $.extend(
			this._variation.value(), {
				property: this.propertyId(),
				snaktype: this.snakType()
			}
		);
	},

	/**
	 * Will update the view to represent a given Snak in form of a plain Object. The given object
	 * can have all fields - or a subset of fields - wb.Snak.toMap() would return.
	 *
	 * @since 0.4
	 *
	 * @param {Object|null} value
	 */
	_setValue: function( value ) {
		value = value || {};
		this.propertyId( value.property || null );
		this.snakType( value.snaktype || null );

		if( this._variation ) {
			// give other Snak information to variation object. Remove basic info since these should
			// rather be accessed via the variation's ViewState object. Also, use a fresh object so
			// the given object doesn't change for outside world.
			var valueCopy = $.extend( {}, value );
			delete valueCopy.property;
			delete valueCopy.snaktype;

			this._variation.value( valueCopy );
		}

		this.draw();
	},


	/**
	 * Returns the current Snak represented by the view or null in case the view is in edit mode,
	 * also allows to set the view to represent a given Snak.
	 *
	 * @since 0.4
	 *
	 * @param {wb.Snak|null} [snak]
	 * @return wb.Snak|null
	 */
	snak: function( snak ) {
		if( snak === undefined ) {
			// factory method will fail when essential data is not yet defined!
			// TODO: variations should have a function to ask whether fully defined yet
			try{
				// NOTE: can still be null if user didn't enter essential information in variation's UI
				return wb.Snak.newFromMap( this.value() );
			} catch( e ) {
				return null;
			}
			// TODO: have a cached version of that snak! Not only for performance, but also to allow
			//  x.snak() === x.snak() which would return false because a new instance of wb.Snak
			//  would be factored on each call. On the other hand, wb.Snak.equals() should be used.
			// NOTE: One possibility would be to use the Flyweight pattern in wb.Snak factories.
		}
		if( snak !== null && !( snak instanceof wb.Snak ) ) {
			throw new Error( 'The given value has to be null or an instance of wikibase.Snak' );
		}
		return this.value( snak );
	},

	/**
	 * Returns the property ID of the property chosen for this Snak or null if none is set.
	 * Equal to .value().getPropertyId() but might be set while .value() still returns null, e.g.
	 * if property has been selected or pre-defined while value or Snak type are not yet set.
	 *
	 * @since 0.3 (setter since 0.4)
	 *
	 * @return String|null
	 */
	propertyId: function( propertyId ) {
		if( propertyId === undefined ) {
			return this._propertyId;
		}
		if( propertyId !== this._propertyId ) {
			this._propertyId = propertyId;
			this._updateVariation();

			// re-draw areas with changes:
			this.drawSnakTypeSelector();
			this.drawVariation();
		}
	},

	/**
	 * Returns the Snak type ID in use for the Snak represented by the view or null if not defined.
	 * Equal to .value().getType() but might be set while .value() still returns null, e.g. if
	 * Snak type has been selected or pre-defined while other required information for constructing
	 * the Snak object has not been defined yet.
	 * If the first parameter is set, it will replace the current Snak type.
	 *
	 * @since 0.4
	 *
	 * @param {String|null} snakType
	 * @return String|null
	 */
	snakType: function( snakType ) {
		if( snakType === undefined ) {
			return this._snakType;
		}
		if( snakType !== this._snakType ) {
			// TODO: check whether given snak type is actually valid!
			// set snak and update variation strategy since it depends on the Snak's type:
			this._snakType = snakType;
			this._updateVariation();

			// re-draw areas with changes:
			this.drawSnakTypeSelector();
			this.drawVariation();
		}
	},

	/**
	 * Returns the jQuery.snakview variation object required for presenting the current Snak type.
	 * If a Snak type has not been defined yet, this will return null.
	 * @since 0.4
	 *
	 * @return {jQuery.wikibase.snakview.variations.Variation|null}
	 */
	variation: function() {
		return this._variation;
	},

	/**
	 * Checks whether the Snak type has been changed by the user and will build a new variation
	 * object for that type if necessary.
	 * @since 0.4
	 */
	_updateVariation: function() {
		var variationsFactory = $.wikibase.snakview.variations,
			variationType,
			snakType = this._snakType,
			VariationConstructor = variationsFactory.getVariation( snakType );

		if( this._variation
			&& ( !this._propertyId || this._variation.constructor !== VariationConstructor )
		) {
			// remember variation's value for next time variation is used during same edit mode:
			variationType = this._variation.variationSnakConstructor.TYPE;
			this._recentVariationValues[ variationType ] = this._variation.value();

			// clean destruction of old variation in case variation will change or property not set
			this._variation.destroy();
			this._variation = null;
		}

		if( !this._variation && this._propertyId && VariationConstructor ) {
			// Snak type has changed so we need another variation Object!
			this._variation = new VariationConstructor(
				new $.wikibase.snakview.ViewState( this ),
				this.$snakValue
			);
			variationType = this._variation.variationSnakConstructor.TYPE;

			// display value used last for this variation within same edit-mode session:
			this._variation.value( this._recentVariationValues[ variationType ] || {} );
		}
	},

	/**
	 * Will render the view's current state (does consider edit mode, current value, etc.).
	 * @since 0.4
	 */
	draw: function() {
		// NOTE: Order of these shouldn't matter; If for any reasons draw functions start changing
		//  the outcome of the variation (or Snak type), then something must be incredibly wrong!
		this.drawProperty();
		this.drawSnakTypeSelector();
		this.drawVariation();

		// have native $.Widget functionality add/remove state css classes
		// (see jQuery.Widget._setOption)
		PARENT.prototype.option.call( this, 'disabled', this.isDisabled() );
	},

	/**
	 * Will make sure the current Snak's property is displayed properly. If not Snak is set, then
	 * this will serve the input form for the Snak's property.
	 * @since 0.4
	 */
	drawProperty: function() {
		var $propertyDom,
			propertyId = this._propertyId,
			property = propertyId ? wb.entities[ propertyId ] : null,
			propertyLabel = '';

		if( property ) {
			propertyLabel = property.label || propertyId;
		}

		if( property || !this.isInEditMode() ) {
			// property set and can't be changed afterwards, only display label
			$propertyDom = $( document.createTextNode( propertyLabel ) );
			// TODO: display nice label with link here, just like claimlistview
		} else {
			// no property set for this Snak, serve edit view to specify it:
			var propertySelector = this._getPropertySelector();

			// TODO: use selectedEntity() or other command to set selected entity in both cases!
			if( propertySelector ) {
				// property selector in DOM already, just replace current value
				propertySelector.widget().val( propertyLabel );
				return;
			}
			// property selector in DOM already, just remove current value
			$propertyDom = this._buildPropertySelector().val( propertyLabel );

			// propagate snakview state:
			$propertyDom.data( 'entityselector' ).option( 'disabled', this.isDisabled() );
		}

		this.$property.empty().append( $propertyDom );
	},

	/**
	 * Will update the selector for choosing the Snak type to represent the currently chosen type.
	 * @since 0.4
	 */
	drawSnakTypeSelector: function() {
		var snakTypes = $.wikibase.snakview.variations.getCoveredSnakTypes(),
			selector = this._getSnakTypeSelector();

		if( !this.isInEditMode() || snakTypes.length <= 1 ) {
			if( selector ) {
				selector.destroy();
			}
			this.$snakTypeSelector.empty();
			return; // no type selector required in non-edit mode!
		}

		if( !selector ) {
			var $selector = this._buildSnakTypeSelector();
			this.$snakTypeSelector.empty().append( $selector );
			selector = $selector.data( 'snaktypeselector' );
		}

		// mark current Snak type as chosen one in the menu:
		selector.snakType( this.snakType() );

		// only show selector if a property is chosen:
		this.$snakTypeSelector[ ( this._propertyId ? 'show' : 'hide' ) ]();

		// propagate snakview state:
		if ( this.isDisabled() ) {
			selector.disable();
		} else {
			selector.enable();
		}
	},

	/**
	 * Convenience function for this.variation().draw( ... ), does not require additional check
	 * whether the variation is null.
	 *
	 * @since 0.4
	 */
	drawVariation: function() {
		// property ID will be null if not in edit mode and no Snak set or if in edit mode and user
		// didn't choose property yet.
		var propertyId = this._propertyId;

		if( propertyId && this._variation ) {
			this.variation().draw();
		} else {
			// remove any remains from previous rendering or initial template (e.g. '$4')
			this.$snakValue.empty();

			if( propertyId ) {
				// property ID selected but apparently no variation available to handle it
				this.$snakValue.append( $( '<span/>', {
					text: this.snakType()
						? mw.msg( 'wikibase-snakview-unsupportedsnaktype', this._snakType )
						: mw.msg( 'wikibase-snakview-choosesnaktype' ),
					'class': this.widgetBaseClass + '-unsupportedsnaktype'
				} ) );
				// NOTE: instead of doing this here and checking everywhere whether this._variation
				//  is set, we could as well use variations for displaying system messages like
				//  this, e.g. having a UnsupportedSnakType variation which is not registered for a
				//  specific snak type but is known to _updateVariation().
			}
		}
	},

	/**
	 * Returns the DOM of the Snak type selector for choosing what kind of Snak this is.
	 * @since 0.4
	 *
	 * @return {jQuery}
	 */
	_buildSnakTypeSelector: function() {
		var self = this,
			$anchor = $( '<span/>' ),
			// initiate snak type selector widget which is a normal widget just without a
			// jQuery.widget.bridge...
			selector = new $.wikibase.snakview.SnakTypeSelector( {}, $anchor );

		// ...add the data information nevertheless:
		$anchor.data( 'snaktypeselector', selector );

		// bind user interaction on selector to snakview's state:
		$anchor.on( 'afterchange', function( event ) {
			self.snakType( selector.snakType() );
			if( self._variation ) {
				self._variation.focus();
			}
		} );

		return $anchor;
	},

	/**
	 * Marks the Snak view disabled and triggers re-drawing the Snak view.
	 * Since the visual state should be managed completely by the draw method, toggling the css
	 * classes is done in draw() by issuing a call to $.Widget.option().
	 * @see jQuery.Widget.disable
	 * @since 0.4
	 */
	disable: function() {
		this.options.disabled = true;
		this.draw();
	},

	/**
	 * Marks the Snak view enabled and triggers re-drawing the Snak view.
	 * Since the visual state should be managed completely by the draw method, toggling the css
	 * classes is done in draw() by issuing a call to $.Widget.option().
	 * @see jQuery.Widget.enable
	 * @since 0.4
	 */
	enable: function() {
		this.options.disabled = false;
		this.draw();
	},

	/**
	 * Returns whether the Snak view is disabled.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isDisabled: function() {
		return this.option( 'disabled' );
	}
} );

}( mediaWiki, wikibase, dataTypes, jQuery ) );
