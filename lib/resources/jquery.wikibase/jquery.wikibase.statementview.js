/**
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, $ ) {
	'use strict';

	var PARENT = $.ui.TemplatedWidget;

/**
 * View for displaying and editing Wikibase Statements.
 * @since 0.4
 * @extends jQuery.ui.TemplatedWidget
 *
 * @option {wikibase.datamodel.Statement|null} [value]
 *         The statement displayed by this view. This can only be set initially, the value function
 *         doesn't work as a setter in this view. If this is null, this view will start in edit
 *         mode, allowing the user to define the claim.
 *         Default: null
 *
 * @option {wb.store.EntityStore} entityStore
 *
 * @option {wikibase.ValueViewBuilder} valueViewBuilder
 *
 * @option {wikibase.entityChangers.ClaimsChanger} claimsChanger
 *
 * @option {wikibase.entityChangers.EntityChangersFactory} entityChangersFactory
 *
 * @option {dataTypes.DataTypeStore} dataTypeStore
 *
 * @option {string} [helpMessage]
 *         End-user message explaining how to use the statementview widget. The message is most
 *         likely to be used inside the tooltip of the toolbar corresponding to the statementview.
 *         Default: mw.msg( 'wikibase-claimview-snak-new-tooltip' )
 *
 * @event afterremove: Triggered after a reference(view) has been remove from the statementview's
 *        list of references/-views.
 *        (1) {jQuery.Event}
 */
$.widget( 'wikibase.statementview', PARENT, {
	options: {
		template: 'wikibase-statementview',
		templateParams: [
			function() { // GUID
				return ( this._statement && this._statement.getClaim().getGuid() ) || 'new';
			},
			function() { // Rank selector
				return $( '<div/>' );
			},
			function() { // Main snak
				return $( '<div/>' );
			},
			'', // Qualifiers
			'', // Toolbar placeholder
			'', // References heading
			'' // List of references
		],
		templateShortCuts: {
			$rankSelector: '.wikibase-statementview-rankselector',
			$mainSnak: '.wikibase-statementview-mainsnak > :first-child',
			$qualifiers: '.wikibase-statementview-qualifiers',
			$refsHeading: '.wikibase-statementview-references-heading',
			$references: '.wikibase-statementview-references'
		},
		value: null,
		claimsChanger: null,
		dataTypeStore: null,
		entityChangersFactory: null,
		predefined: {
			mainSnak: false
		},
		locked: {
			mainSnak: false
		},
		helpMessage: mw.msg( 'wikibase-claimview-snak-new-tooltip' )
	},

	/**
	 * Shortcut to the list item adapter in use in the reference view.
	 * @type {$.wikibase.listview.ListItemAdapter}
	 */
	_referenceviewLia: null,

	/**
	 * Shortcut to the listview holding the reference views.
	 * @type {$.wikibase.listview}
	 */
	_referencesListview: null,

	/**
	 * @type {wikibase.datamodel.Statement|null}
	 */
	_statement: null,

	/**
	 * Reference to the `listview` widget managing the qualifier `snaklistview`s. Basically, just a
	 * short-cut for `this.$qualifiers.data( 'listview' )`.
	 * @type {$.wikibase.listview}
	 */
	_qualifiers: null,

	/**
	 * Caches the `SnakList` of the qualifiers the `statementview` has been initialized with. The
	 * qualifiers are split into groups featuring the same property. Removing one of those groups
	 * results in losing the reference to those qualifiers. Therefore, `_initialQualifiers` is used
	 * to rebuild the list of qualifiers when cancelling and is used to query whether the qualifiers
	 * represent the initial state.
	 * @type {wb.datamodel.SnakList}
	 */
	_initialQualifiers: null,

	/**
	 * @type {wikibase.entityChangers.ReferencesChanger}
	 */
	_referencesChanger: null,

	/**
	 * @see jQuery.ui.TemplatedWidget._create
	 */
	_create: function() {
		if(
			!this.options.entityStore
			|| !this.options.valueViewBuilder
			|| !this.options.claimsChanger
			|| !this.options.entityChangersFactory
		) {
			throw new Error( 'Required option(s) missing' );
		}

		PARENT.prototype._create.call( this );

		this._statement = this.options.value;

		this._createRankSelector( this._statement ? this._statement.getRank() : null );
		this._createMainSnak( this._statement
			? this._statement.getClaim().getMainSnak()
			: this.option( 'predefined' ).mainSnak || null
		);

		this._initialQualifiers = this._statement
			? this._statement.getClaim().getQualifiers()
			: new wb.datamodel.SnakList();

		// TODO: Allow adding qualifiers when adding a new claim.
		if( this._statement && this._initialQualifiers.length ) {
			this._createQualifiersListview( this._initialQualifiers );
		}

		this._referencesChanger = this.options.entityChangersFactory.getReferencesChanger();
		this._createReferences( this._statement );

		this._updateHelpMessage();
	},

	/**
	 * Creates the rank selector to select the statement rank.
	 * @since 0.5
	 *
	 * @param {number} rank
	 */
	_createRankSelector: function( rank ) {
		var $rankSelector = this.$rankSelector.children().first();
		this._rankSelector = new $.wikibase.statementview.RankSelector( {
			rank: rank,
			templateParams: ['ui-state-disabled', '', '']
		}, $rankSelector );

		var self = this,
			changeEvent = ( this._rankSelector.widgetEventPrefix + 'afterchange' ).toLowerCase();

		this.$rankSelector.on( changeEvent + '.' + this.widgetName, function( event ) {
			if( self.value() ) {
				self._trigger( 'change' );
			}
		} );

		this.element
		.on( this.widgetEventPrefix + 'toggleerror.' + this.widgetName, function( event, error ) {
			if( !error ) {
				self._rankSelector.enable();
			}
		} )
		.on(
			this.widgetEventPrefix + 'afterstopediting.' + this.widgetName,
			function( event, dropValue ) {
				// FIXME: This should be the responsibility of the rankSelector
				$rankSelector.removeClass( 'ui-state-default' );
				if( dropValue && self._statement ) {
					self._rankSelector.rank( self._statement.getRank() );
				}
				self._rankSelector.disable();
			}
		);
	},

	/**
	 * @param {wikibase.datamodel.Snak|null} snak
	 * @private
	 */
	_createMainSnak: function( snak ) {
		var self = this;

		this.$mainSnak
		.on(
			[
				'snakviewchange.' + this.widgetName,
				'snakviewafterstartediting.' + this.widgetName
			].join( ' ' ),
			function( event, status ) {
				event.stopPropagation();
				self._trigger( 'change' );
			}
		)
		.on( 'snakviewstopediting.' + this.widgetName, function( event ) {
			event.stopPropagation();
		} );

		this.$mainSnak.snakview( {
			value: snak,
			locked: this.option( 'locked' ).mainSnak,
			autoStartEditing: false,
			dataTypeStore: this.option( 'dataTypeStore' ),
			entityStore: this.options.entityStore,
			valueViewBuilder: this.option( 'valueViewBuilder' )
		} );
	},

	/**
	 * Creates the `listview` widget containing the qualifier `snaklistview` widgets.
	 * @private
	 *
	 * @param {wb.datamodel.SnakList|null} [qualifiers=null]
	 */
	_createQualifiersListview: function( qualifiers ) {
		var self = this,
			groupedQualifierSnaks = null;

		// Group qualifiers by property id:
		if( qualifiers && qualifiers.length ) {
			var propertyIds = qualifiers.getPropertyOrder();

			groupedQualifierSnaks = [];

			for( var i = 0; i < propertyIds.length; i++ ) {
				groupedQualifierSnaks.push( qualifiers.getFilteredSnakList( propertyIds[i] ) );
			}
		}

		// Using the property id, qualifier snaks are split into groups of snaklistviews. These
		// snaklistviews are managed in a listview:
		var $qualifiers = this.$qualifiers.children();
		if( !$qualifiers.length ) {
			$qualifiers = $( '<div/>' ).prependTo( this.$qualifiers );
		}
		$qualifiers.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.snaklistview,
				newItemOptionsFn: function( value ) {
					return {
						value: value || null,
						singleProperty: true,
						dataTypeStore: self.option( 'dataTypeStore' ),
						entityStore: self.option( 'entityStore' ),
						valueViewBuilder: self.option( 'valueViewBuilder' )
					};
				}
			} ),
			value: groupedQualifierSnaks
		} )
		.on( 'snaklistviewstopediting.' + this.widgetName, function( event, dropValue ) {
			event.stopPropagation();
		} )
		.on( 'snaklistviewchange.' + this.widgetName
			+ ' listviewafteritemmove.' + this.widgetName,
			function( event ) {
				event.stopPropagation();
				self._trigger( 'change' );
			}
		)
		.on( 'listviewitemremoved.' + this.widgetName, function( event, value, $itemNode ) {
			if( event.target === self._qualifiers.element.get( 0 ) ) {
				self._trigger( 'change' );
				return;
			}

			// Check if last snaklistview of a qualifier listview item has been removed and
			// remove the listview item if so:
			var $snaklistview = $( event.target ).closest( ':wikibase-snaklistview' ),
				snaklistview = $snaklistview.data( 'snaklistview' );

			if( !snaklistview.value() ) {
				self._qualifiers.removeItem( snaklistview.element );
			}
		} );

		this._qualifiers = $qualifiers.data( 'listview' );
	},

	/**
	 * @param {wikibase.datamodel.Statement} [statement]
	 * @private
	 */
	_createReferences: function( statement ) {
		if( !statement ) {
			return;
		}

		var self = this,
			references = statement.getReferences();

		var $listview = this.$references.children();
		if( !$listview.length ) {
			$listview = $( '<div/>' ).prependTo( this.$references );
		}

		$listview.listview( {
			listItemAdapter: new $.wikibase.listview.ListItemAdapter( {
				listItemWidget: $.wikibase.referenceview,
				newItemOptionsFn: function( value ) {
					return {
						value: value || null,
						statementGuid: self.value().getClaim().getGuid(),
						dataTypeStore: self.option( 'dataTypeStore' ),
						entityStore: self.option( 'entityStore' ),
						valueViewBuilder: self.option( 'valueViewBuilder' ),
						referencesChanger: self._referencesChanger
					};
				}
			} ),
			value: references.toArray()
		} );

		this._referencesListview = $listview.data( 'listview' );

		this._referenceviewLia = this._referencesListview.listItemAdapter();

		$listview
		.on( 'listviewitemadded listviewitemremoved', function( event, value, $li ) {
			if( event.target === $listview.get( 0 ) ) {
				self.drawReferencesCounter();
			}
		} )
		.on( 'listviewenternewitem', function( event, $newLi ) {
			// Enter first item into the referenceview.
			self._referenceviewLia.liInstance( $newLi ).enterNewItem();

			var lia = self._referenceviewLia,
				liInstance = lia.liInstance( $newLi );

			if ( !liInstance.value() ) {
				$newLi
				.on( lia.prefixedEvent( 'afterstopediting' ), function( event, dropValue ) {
					if( dropValue ) {
						liInstance.destroy();
						$newLi.remove();
						self.drawReferencesCounter();
					} else {
						var newReferenceWithHash = liInstance.value();

						// Destroy new reference input form and add reference to list
						liInstance.destroy();
						$newLi.remove();

						// Display new reference with final GUID
						self._addReference( newReferenceWithHash );
					}
				} );
			}
		} );

		// Collapse references if there is at least one.
		if ( this._referencesListview.items().length > 0 ) {
			this.$references.css( 'display', 'none' );
		}

		// toggle for references section:
		var $toggler = $( '<a/>' ).toggler( { $subject: this.$references } );

		if( this.$refsHeading.text() ) {
			$toggler.find( '.ui-toggler-label' ).text( this.$refsHeading.text() );
			this.$refsHeading.html( $toggler );
		} else {
			this.$refsHeading.html( $toggler );
			this.drawReferencesCounter();
		}
	},

	/**
	 * Updates the `helpMessage` option according to whether the `main Snak`'s `Property` is
	 * predefined.
	 * @private
	 */
	_updateHelpMessage: function() {
		if( !this._statement && !this.options.predefined.mainSnak ) {
			return;
		}

		var property = this._statement
			? this._statement.getClaim().getMainSnak().getPropertyId()
			: this.options.predefined.mainSnak.property;

		var deferred = $.Deferred(),
			helpMessage = this.options.helpMessage;

		this.options.helpMessage = deferred.promise();

		if( property ) {
			this.options.entityStore.get( property ).done( function( fetchedProperty ) {
				if( fetchedProperty ) {
					helpMessage = mw.msg(
						'wikibase-claimview-snak-tooltip',
						wb.utilities.ui.buildPrettyEntityLabelText( fetchedProperty.getContent() )
					);
				}
				deferred.resolve( helpMessage );
			} );
		} else {
			deferred.resolve( helpMessage );
		}
	},

	/**
	 * @inheritdoc
	 */
	destroy: function() {
		this._rankSelector.destroy();
		this.$rankSelector.off( '.' + this.widgetName );

		this.$mainSnak.snakview( 'destroy' );
		this.$mainSnak.off( '.' + this.widgetName );

		this._destroyQualifiersListView();

		PARENT.prototype.destroy.call( this );
	},

	/**
	 * @private
	 */
	_destroyQualifiersListView: function() {
		if( this._qualifiers ) {
			this._qualifiers.destroy();
			this.$qualifiers
				.off( '.' + this.widgetName )
				.empty();
			this._qualifiers = null;
		}
	},

	/**
	 * @return {boolean}
	 */
	isInitialValue: function() {
		if( this._statement ) {
			if( this._statement.getRank() !== this._rankSelector.rank() ) {
				return false;
			}

			var snaklistviews = ( this._qualifiers ) ? this._qualifiers.value() : [],
				qualifiers = new wb.datamodel.SnakList();

			// Generate a SnakList object featuring all current qualifier snaks to be able to
			// compare it to the SnakList object the claimview has been initialized with:
			if( snaklistviews.length ) {
				for( var i = 0; i < snaklistviews.length; i++ ) {
					if( snaklistviews[i].value() ) {
						qualifiers.merge( snaklistviews[i].value() );
					}
				}
			}

			if( !qualifiers.equals( this._initialQualifiers ) ) {
				return false;
			}
		}

		return this.$mainSnak.data( 'snakview' ).isInitialSnak();
	},

	/**
	 * Instantiates a `Statement` with the `statementview`'s current value.
	 *
	 * @param {string} guid
	 * @return {wikibase.datamodel.Statement}
	 */
	_instantiateStatement: function( guid ) {
		var qualifiers = new wb.datamodel.SnakList(),
			snaklistviews = this._qualifiers ? this._qualifiers.value() : [];

		// Combine qualifiers grouped by property to a single SnakList:
		for( var i = 0; i < snaklistviews.length; i++ ) {
			qualifiers.merge( snaklistviews[i].value() );
		}

		return new wb.datamodel.Statement(
			new wb.datamodel.Claim(
				this.$mainSnak.data( 'snakview' ).snak(),
				qualifiers,
				guid
			),
			new wb.datamodel.ReferenceList( this.getReferences() ),
			this._rankSelector.rank()
		);
	},

	/**
	 * Adds one reference to the list and renders it in the view.
	 * @since 0.4
	 *
	 * @param {wb.datamodel.Reference} reference
	 */
	_addReference: function( reference ) {
		this._referencesListview.addItem( reference );
	},

	/**
	 * Returns all references currently set (including all pending changes).
	 *
	 * @return {wb.datamodel.Reference[]}
	 */
	getReferences: function() {
		var self = this,
			references = [];

		// If the statement is pending (not yet stored), the listview widget for the references is
		// not defined.
		if ( !this._referencesListview ) {
			return references;
		}

		$.each( this._referencesListview.items(), function( i, item ) {
			var referenceview = self._referenceviewLia.liInstance( $( item ) );
			references.push( referenceview.value() );
		} );

		return references;
	},

	/**
	 * Removes a referenceview from the list of references.
	 * @since 0.4
	 *
	 * @param {$.wikibase.referenceview} referenceview
	 */
	remove: function( referenceview ) {
		var self = this;

		referenceview.disable();

		this._referencesChanger.removeReference(
			this.value().getClaim().getGuid(),
			referenceview.value()
		)
		.done( function() {
			self._referencesListview.removeItem( referenceview.element );
			self._trigger( 'afterremove' );
		} ).fail( function( error ) {
			referenceview.enable();
			referenceview.setError( error );
		} );
	},

	/**
	 * Returns the current Statement represented by the view. If null is returned, than this is a
	 * fresh view where a new Statement is being constructed.
	 *
	 * @since 0.4
	 *
	 * @return {wikibase.datamodel.Statement|null}
	 */
	value: function() {
		return this._statement;
	},

	/**
	 * Will update the references counter in the DOM.
	 *
	 * @since 0.4
	 */
	drawReferencesCounter: function() {
		var numberOfValues = this._referencesListview.nonEmptyItems().length,
			numberOfPendingValues = this._referencesListview.items().length - numberOfValues;

		// build a nice counter, displaying fixed and pending values:
		var $counterMsg = wb.utilities.ui.buildPendingCounter(
			numberOfValues,
			numberOfPendingValues,
			'wikibase-statementview-referencesheading-pendingcountersubject',
			'wikibase-statementview-referencesheading-pendingcountertooltip' );

		// update counter, don't touch the toggle!
		this.$refsHeading.find( '.ui-toggler-label' ).empty().append( $counterMsg );
	},

	/**
	 * @since 0.4
	 *
	 * @param {boolean} [dropValue] If true, the value from before edit mode has been started will
	 *        be reinstated - basically a cancel/save switch. "false" by default. Consider using
	 *        cancelEditing() instead.
	 * @return {undefined} (allows chaining widget calls)
	 */
	stopEditing: $.NativeEventHandler( 'stopEditing', {
		// don't stop edit mode or trigger event if not in edit mode currently:
		initially: function( e, dropValue ) {
			if(
				!this.isInEditMode() || ( !this.isValid() || this.isInitialValue() ) && !dropValue
			) {
				e.cancel();
			}

			this.element.removeClass( 'wb-error' );
		},
		// start edit mode if custom event handlers didn't prevent default:
		natively: function( e, dropValue ) {
			var self = this;

			this.disable();
			this._rankSelector.disable();

			function stopEditing() {
				if( self.$mainSnak.data( 'snakview' ) ) {
					self.$mainSnak.data( 'snakview' ).stopEditing( dropValue );
				}

				self._stopEditingQualifiers( dropValue );

				self._isInEditMode = false;
				self.enable();

				self.element.removeClass( 'wb-edit' );

				// transform toolbar and snak view after save complete
				self._trigger( 'afterstopediting', null, [dropValue] );
			}

			if( dropValue ) {
				stopEditing();
			} else {
				// editing an existing claim
				this._saveStatementApiCall()
				.done( stopEditing )
				.fail( function( error ) {
					self.enable();
					self.setError( error );
				} );
			}
		}
	} ),

	/**
	 * Stops qualifiers `listview` edit mode.
	 *
	 * @param {boolean} dropValue
	 */
	_stopEditingQualifiers: function( dropValue ) {
		var snaklistviews,
			i;

		if( this._qualifiers ) {
			snaklistviews = this._qualifiers.value();

			if( !dropValue ) {
				// When saving the qualifier snaks, reset the initial qualifiers to the new ones.
				this._initialQualifiers = new wb.datamodel.SnakList();
			}

			if( snaklistviews.length ) {
				for( i = 0; i < snaklistviews.length; i++ ) {
					snaklistviews[i].stopEditing( dropValue );

					if( dropValue && !snaklistviews[i].value() ) {
						// Remove snaklistview from qualifier listview if no snakviews are left in
						// that snaklistview:
						this._qualifiers.removeItem( snaklistviews[i].element );
					} else if ( !dropValue ) {
						// Gather all the current snaks in a single SnakList to set to reset the
						// initial qualifiers:
						this._initialQualifiers.merge( snaklistviews[i].value() );
					}
				}
			}
		}

		// Destroy and (if qualifiers still exist) re-create the qualifier listview in order to
		// re-group the qualifiers by their property. This will also send out the event to erase
		// the "add qualifier" toolbar.
		this._destroyQualifiersListView();

		if( this._initialQualifiers.length > 0 ) {
			// Refill the qualifier listview with the initial (or new initial) qualifiers:
			this._createQualifiersListview( this._initialQualifiers );
		}
	},

	/**
	 * TODO: would be nice to have all API related stuff out of here to allow concentrating on
	 *       MVVM relation.
	 *
	 * @return {jQuery.Promise}
	 *         Resolved parameters:
	 *         - {wikibase.datamodel.Statement} The saved statement
	 *         Rejected parameters:
	 *         - {wikibase.api.RepoApiError}
	 */
	_saveStatementApiCall: function() {
		var self = this,
			guid;

		if( this.value() ) {
			guid = this.value().getClaim().getGuid();
		} else {
			var guidGenerator = new wb.utilities.ClaimGuidGenerator();
			guid = guidGenerator.newGuid( mw.config.get( 'wbEntityId' ) );
		}

		return this.option( 'claimsChanger' ).setStatement( this._instantiateStatement( guid ) )
		.done( function( savedStatement ) {
			// Update model of represented Statement:
			self._statement = savedStatement;
		} );
	},

	/**
	 * Exits edit mode and restores the value from before the edit mode has been started.
	 *
	 * @return {undefined} (allows chaining widget calls)
	 */
	cancelEditing: function() {
		return this.stopEditing( true );
	},

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

			this.$mainSnak.one( 'snakviewafterstartediting', function() {
				if( !self._qualifiers && self._statement ) {
					self._createQualifiersListview();
				}

				// Start edit mode of all qualifiers:
				if( self._qualifiers ) {
					var snaklistviews = self._qualifiers.value();
					if( snaklistviews.length ) {
						for( var i = 0; i < snaklistviews.length; i++ ) {
							snaklistviews[i].startEditing();
						}
					}
					// If there are no snaklistviews, there is no way for the "add qualifier"
					// toolbar to be
					self._qualifiers.element.trigger( 'qualifiersstartediting' );
				}

				self.element.addClass( 'wb-edit' );
				self._isInEditMode = true;

				// FIXME: This should be the responsibility of the rankSelector
				self._rankSelector.element.addClass( 'ui-state-default' );
				if( !self._statement ) {
					self._rankSelector.rank( wb.datamodel.Statement.RANK.NORMAL );
				}
				self._rankSelector.enable();

				self._trigger( 'afterstartediting' );
			} );

			this.$mainSnak.data( 'snakview' ).startEditing();
		}
	} ),

	/**
	 * Returns whether the statement is editable at the moment.
	 * @since 0.4
	 *
	 * @return {boolean}
	 */
	isInEditMode: function() {
		return this._isInEditMode;
	},

	/**
	 * Sets/removes error state from the widget.
	 * @since 0.4
	 *
	 * @param {wikibase.api.RepoApiError} [error]
	 */
	setError: function( error ) {
		if( error ) {
			this.element.addClass( 'wb-error' );
			this._trigger( 'toggleerror', null, [ error ] );
		} else {
			this.element.removeClass( 'wb-error' );
			this._trigger( 'toggleerror' );
		}
	},

	/**
	 * @return {boolean}
	 */
	isValid: function() {
		if( this._qualifiers ) {
			var snaklistviews = this._qualifiers.value();

			if( snaklistviews.length ) {
				for( var i = 0; i < snaklistviews.length; i++ ) {
					if( !snaklistviews[i].isValid() ) {
						return false;
					}
				}
			}
		}

		try {
			this._instantiateStatement( null );
		} catch( e ) {
			return false;
		}

		return true;
	},

	/**
	 * @inheritdoc
	 */
	_setOption: function( key, value ) {
		if( key === 'value' ) {
			throw new Error( 'Can not set value after initialization' );
		}

		var response = PARENT.prototype._setOption.apply( this, arguments );

		if( key === 'disabled' ) {
			this.$mainSnak.data( 'snakview' ).option( key, value );
			if( this._qualifiers ) {
				this._qualifiers.option( key, value );
			}
		}

		return response;
	},

	/**
	 * @inheritdoc
	 */
	focus: function() {
		this.$mainSnak.data( 'snakview' ).focus();
	}
} );

// Register toolbars:
$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'claim-qualifiers-snak',
	selector: '.wikibase-statementview-qualifiers',
	events: {
		'listviewcreate snaklistviewstartediting': function( event, toolbarController ) {
			var $target = $( event.target ),
				$qualifiers = $target.closest( '.wikibase-statementview-qualifiers' ),
				listview = $target.closest( ':wikibase-listview' ).data( 'listview' ),
				listviewInited = event.type === 'listviewcreate' && listview.items().length === 0;

			if(
				( listviewInited || event.type === 'snaklistviewstartediting' )
				&& !$qualifiers.data( 'addtoolbar' )
			) {
				$qualifiers
				.addtoolbar( {
					$container: $( '<div/>' ).appendTo( $qualifiers ),
					label: mw.msg( 'wikibase-addqualifier' )
				} )
				.off( '.addtoolbar' )
				.on( 'addtoolbaradd.addtoolbar', function( e ) {
					listview.enterNewItem();
					listview.value()[listview.value().length - 1].enterNewItem();
				} );

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'listviewdestroy snaklistviewafterstopediting',
					function( event, toolbarcontroller ) {
						var $target = $( event.target ),
							$qualifiers = $target.closest( '.wikibase-statementview-qualifiers' );

						if( $target.parent().get( 0 ) !== $qualifiers.get( 0 ) ) {
							// Not the qualifiers main listview.
							return;
						}

						toolbarcontroller.destroyToolbar( $qualifiers.data( 'addtoolbar' ) );
					}
				);

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'snaklistviewchange',
					function( event ) {
						var $target = $( event.target ),
							$qualifiers = $target.closest( '.wikibase-statementview-qualifiers' ),
							addToolbar = $qualifiers.data( 'addtoolbar' ),
							$listview = $target.closest( ':wikibase-listview' ),
							snaklistviews = $listview.data( 'listview' ).value();

						if( addToolbar ) {
							addToolbar.enable();
							for( var i = 0; i < snaklistviews.length; i++ ) {
								if( !snaklistviews[i].isValid() ) {
									addToolbar.disable();
									break;
								}
							}
						}
					}
				);

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					// FIXME: When there are qualifiers, no state change events will be thrown.
					'listviewdisable',
					function( event ) {
						var $qualifiers = $( event.target )
								.closest( '.wikibase-statementview-qualifiers' ),
							addToolbar = $qualifiers.data( 'addtoolbar' ),
							$statementview = $qualifiers.closest( ':wikibase-statementview' ),
							statementview = $statementview.data( 'statementview' );

						// Toolbar might be removed from the DOM already after having stopped edit
						// mode.
						if( addToolbar ) {
							addToolbar[statementview.option( 'disabled' ) ? 'disable' : 'enable']();
						}
					}
				);

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'listviewitemadded listviewitemremoved',
					function( event ) {
						// Enable "add" link when all qualifiers have been removed:
						var $listviewNode = $( event.target ),
							listview = $listviewNode.data( 'listview' ),
							$snaklistviewNode = $listviewNode.closest( '.wb-snaklistview' ),
							snaklistview = $snaklistviewNode.data( 'snaklistview' ),
							addToolbar = $snaklistviewNode.data( 'addtoolbar' );

						// Toolbar is not within the DOM when (re-)constructing the list in
						// non-edit-mode.
						if( !addToolbar ) {
							return;
						}

						// Disable "add" toolbar when the last qualifier has been removed:
						if( !snaklistview.isValid() && listview.items().length ) {
							addToolbar.disable();
						} else {
							addToolbar.enable();
						}
					}
				);

			}
		}
	}
} );

$.wikibase.toolbarcontroller.definition( 'removetoolbar', {
	id: 'claim-qualifiers-snak',
	selector: '.wikibase-statementview-qualifiers',
	events: {
		'snakviewstartediting': function( event, toolbarController ) {
			var $snakview = $( event.target ),
				$snaklistview = $snakview.closest( '.wb-snaklistview' ),
				snaklistview = $snaklistview.data( 'snaklistview' );

			if( !snaklistview ) {
				return;
			}

			var qualifierPorpertyGroupListview = snaklistview._listview;

			// Create toolbar for each snakview widget:
			$snakview
			.removetoolbar( {
				$container: $( '<div/>' ).appendTo( $snakview )
			} )
			.on( 'removetoolbarremove.removetoolbar', function( event ) {
				if( event.target === $snakview.get( 0 ) ) {
					qualifierPorpertyGroupListview.removeItem( $snakview );
				}
			} );

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'snaklistviewafterstopediting',
				function( event, toolbarcontroller ) {
					// Destroy the snakview toolbars:
					var $snaklistviewNode = $( event.target ),
						listview = $snaklistviewNode.data( 'snaklistview' )._listview,
						lia = listview.listItemAdapter();

					$.each( listview.items(), function( i, item ) {
						var snakview = lia.liInstance( $( item ) );
						toolbarcontroller.destroyToolbar(
							snakview.element.data( 'removetoolbar' )
						);
					} );
				}
			);

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'snaklistviewdisable',
				function( event ) {
					var $snaklistviewNode = $( event.target ),
						listview = $snaklistviewNode.data( 'snaklistview' )._listview,
						lia = listview.listItemAdapter(),
						$statementview = $snaklistviewNode.closest( ':wikibase-statementview' ),
						statementview = $statementview.data( 'statementview' );

					$.each( listview.items(), function( i, node ) {
						var $snakview = $( node ),
							snakview = lia.liInstance( $snakview ),
							removeToolbar = $snakview.data( 'removetoolbar' );

						// Item might be about to be removed not being a list item instance.
						if( !snakview || !removeToolbar ) {
							return;
						}

						$snakview.data( 'removetoolbar' )[statementview.option( 'disabled' )
							? 'disable'
							: 'enable'
						]();
					} );
				}
			);

		}
	}
} );

$.wikibase.toolbarcontroller.definition( 'addtoolbar', {
	id: 'references',
	selector: '.wikibase-statementview-references',
	events: {
		listviewcreate: function( event, toolbarController ) {
			var $listview = $( event.target ),
				listview = $listview.data( 'listview' ),
				lia = listview.listItemAdapter(),
				$node = $listview.parent();

			if( !$node.hasClass( 'wikibase-statementview-references' ) ) {
				return;
			}

			$node
			.addtoolbar( {
				$container: $( '<div/>' ).appendTo( $node ),
				label: mw.msg( 'wikibase-addreference' )
			} )
			.on( 'addtoolbaradd.addtoolbar', function( e ) {
				if( e.target !== $node.get( 0 ) ) {
					return;
				}

				listview.enterNewItem();

				// Re-focus "add" button after having added or having cancelled adding a reference:
				var eventName = lia.prefixedEvent( 'afterstopediting.addtoolbar' );
				$listview.one( eventName, function( event ) {
					$node.data( 'addtoolbar' ).focus();
				} );

				toolbarController.registerEventHandler(
					event.data.toolbar.type,
					event.data.toolbar.id,
					'listviewdestroy',
					function( event, toolbarController ) {
						var $listview = $( event.target ),
							$node = $listview.parent();

						if( !$node.hasClass( '.wikibase-statementview-references' ) ) {
							return;
						}

						toolbarController.destroyToolbar( $node.data( 'addtoolbar' ) );
						$node.off( 'addtoolbar' );
					}
				);
			} );

			toolbarController.registerEventHandler(
				event.data.toolbar.type,
				event.data.toolbar.id,
				'listviewdisable',
				function( event ) {
					if( event.target !== $listview.get( 0 ) ) {
						return;
					}
					$node.data( 'addtoolbar' )[
						listview.option( 'disabled' )
							? 'disable'
							: 'enable'
					]();
				}
			);
		}
	}
} );

$.wikibase.toolbarcontroller.definition( 'edittoolbar', {
	id: 'referenceview',
	selector: ':' + $.wikibase.referenceview.prototype.namespace
		+ '-' + $.wikibase.referenceview.prototype.widgetName,
	events: {
		referenceviewcreate: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' ),
				options = {
					interactionWidget: referenceview
				},
				$container = $referenceview.find( '.wikibase-toolbar-container' );

			if( !$container.length ) {
				$container = $( '<div/>' ).appendTo(
					$referenceview.find( '.wb-referenceview-heading' )
				);
			}

			options.$container = $container;

			if( !!referenceview.value() ) {
				options.onRemove = function() {
					var $statementview = $referenceview.closest( ':wikibase-statementview' ),
						statementview = $statementview.data( 'statementview' );
					if( statementview ) {
						statementview.remove( referenceview );
					}
				};
			}

			$referenceview.edittoolbar( options );

			$referenceview.on( 'keydown.edittoolbar', function( event ) {
				if( referenceview.option( 'disabled' ) ) {
					return;
				}
				if( event.keyCode === $.ui.keyCode.ESCAPE ) {
					referenceview.stopEditing( true );
				} else if( event.keyCode === $.ui.keyCode.ENTER ) {
					referenceview.stopEditing( false );
				}
			} );
		},
		referenceviewchange: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' ),
				edittoolbar = $referenceview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enableSave = referenceview.isValid() && !referenceview.isInitialValue();

			btnSave[enableSave ? 'enable' : 'disable']();
		},
		referenceviewdisable: function( event ) {
			var $referenceview = $( event.target ),
				referenceview = $referenceview.data( 'referenceview' );

			if( !referenceview ) {
				return;
			}

			var disable = referenceview.option( 'disabled' ),
				edittoolbar = $referenceview.data( 'edittoolbar' ),
				btnSave = edittoolbar.getButton( 'save' ),
				enableSave = ( referenceview.isValid() && !referenceview.isInitialValue() );

			edittoolbar.option( 'disabled', disable );
			if( !disable ) {
				btnSave.option( 'disabled', !enableSave );
			}
		}

		// Destroying the referenceview will destroy the toolbar. Trying to destroy the toolbar
		// in parallel will cause interference.
	}
} );

}( mediaWiki, wikibase, jQuery ) );
