( function ( wb ) {
	'use strict';

	var PARENT = $.ui.EditableTemplatedWidget,
		datamodel = require( 'wikibase.datamodel' ),
		buildCounter = require( '../../wikibase/utilities/wikibase.utilities.ui.js' );

	/**
	 * View for displaying and editing `datamodel.Statement` objects.
	 *
	 * @see datamodel.Statement
	 * @class jQuery.wikibase.statementview
	 * @extends jQuery.ui.EditableTemplatedWidget
	 * @uses jQuery.ui.toggler
	 * @uses jQuery.wikibase.listview
	 * @uses jQuery.wikibase.listview.ListItemAdapter
	 * @uses jQuery.wikibase.referenceview
	 * @uses jQuery.wikibase.snakview
	 * @uses jQuery.wikibase.snaklistview
	 * @uses jQuery.wikibase.statementview.RankSelector
	 * @uses mediaWiki
	 * @uses datamodel.Claim
	 * @uses datamodel.SnakList
	 * @uses datamodel.ReferenceList
	 * @uses datamodel.Statement
	 * @uses wikibase.utilities.ui
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Object} options
	 * @param {datamodel.Statement|null} [options.value=null]
	 *        The `Statement` displayed by the view.
	 * @param {Function} options.getReferenceListItemAdapter
	 * @param {Function} options.buildSnakView
	 * @param {wikibase.utilities.ClaimGuidGenerator} options.guidGenerator
	 *        Required for dynamically generating GUIDs for new `Statement`s.
	 * @param {wikibase.entityIdFormatter.EntityIdPlainFormatter} options.entityIdPlainFormatter
	 *        Required for dynamically rendering plain text references to `Entity`s.
	 * @param {Object} [options.predefined={ mainSnak: false }]
	 *        Allows to predefine certain aspects of the `Statement` to be created from the view. If
	 *        this option is omitted, an empty view is created. A common use-case is adding a value to a
	 *        property existing already by specifying, for example: `{ mainSnak.property: 'P1' }`.
	 * @param {Function} options.getQualifiersListItemAdapter
	 * @param {Object} [options.locked={ mainSnak: false }]
	 *        Elements that shall be locked and may not be changed by user interaction.
	 * @param {string} [options.helpMessage=mw.msg( 'wikibase-claimview-snak-new-tooltip' )]
	 *        End-user message explaining how to use the `statementview` widget. The message is most
	 *        likely to be used inside the tooltip of the toolbar corresponding to the `statementview`.
	 */
	/**
	 * @event change
	 * Triggered whenever the view's content is changed.
	 * @param {jQuery.Event} event
	 */
	$.widget( 'wikibase.statementview', PARENT, {
		/**
		 * @inheritdoc
		 * @protected
		 */
		options: {
			template: 'wikibase-statementview',
			templateParams: [
				function () { // GUID
					return ( this.options.value && this.options.value.getClaim().getGuid() ) || 'new';
				},
				function () { // Rank name
					return ( this.options.value && this._getRankName( this.options.value.getRank() ) )
						|| 'normal';
				},
				function () { // Rank selector
					return $( '<div>' );
				},
				function () { // Main snak
					return $( '<div>' );
				},
				'', // Qualifiers
				'', // Toolbar placeholder
				'', // References heading
				'', // List of references
				'' // wikibase-initially-collapsed for wikibase-statementview-references
			],
			templateShortCuts: {
				$rankSelector: '.wikibase-statementview-rankselector',
				$mainSnak: '.wikibase-statementview-mainsnak > :first-child',
				$qualifiers: '.wikibase-statementview-qualifiers',
				$refsHeading: '.wikibase-statementview-references-heading',
				$references: '.wikibase-statementview-references'
			},
			value: null,
			entityIdPlainFormatter: null,
			predefined: {
				mainSnak: false
			},
			locked: {
				mainSnak: false
			},
			helpMessage: mw.msg( 'wikibase-claimview-snak-new-tooltip' ),
			fireStartEditingHook: mw.hook( 'wikibase.statement.startEditing' ).fire,
			fireStopEditingHook: mw.hook( 'wikibase.statement.stopEditing' ).fire
		},

		/**
		 * @property {jQuery.wikibase.snakview|null}
		 * @private
		 */
		_mainSnakSnakView: null,

		/**
		 * @property {jQuery.wikibase.statementview.RankSelector|null}
		 * @private
		 */
		_rankSelector: null,

		/**
		 * Shortcut to the `listview` managing the `referenceview`s.
		 *
		 * @property {jQuery.wikibase.listview|null}
		 * @private
		 */
		_referencesListview: null,

		/**
		 * Reference to the `listview` widget managing the qualifier `snaklistview`s.
		 *
		 * @property {jQuery.wikibase.listview|null}
		 * @private
		 */
		_qualifiers: null,

		/**
		 * Reference to the `toggler` widget managing expanding/collapsing
		 *
		 * @property {jQuery}
		 * @private
		 */
		_$toggler: null,

		/**
		 * @property {Object}
		 * @private
		 */
		_referenceAdder: null,

		/**
		 * @inheritdoc
		 * @protected
		 *
		 * @throws {Error} if a required option is not specified properly.
		 */
		_create: function () {
			if ( !this.options.getReferenceListItemAdapter
				|| !this.options.buildSnakView
				|| !this.options.entityIdPlainFormatter
				|| !this.options.guidGenerator
				|| !this.options.getQualifiersListItemAdapter
			) {
				throw new Error( 'Required option not specified properly' );
			}

			var isEmpty = this.element.is( ':empty' );
			PARENT.prototype._create.call( this );

			if ( isEmpty ) {
				this.draw();
			} else {
				this._createReferencesToggler();
			}

			this._referenceAdder = this.options.getAdder(
				this._enterNewReference.bind( this ),
				this.$references,
				mw.msg( 'wikibase-addreference' )
			);
			this.element.toggleClass( 'wb-new', this.options.value === null );
		},

		/**
		 * @private
		 *
		 * @param {number} rank
		 */
		_createRankSelector: function ( rank ) {
			if ( this._rankSelector ) {
				return;
			}

			var $rankSelector = this.$rankSelector.children().first();
			this._rankSelector = new $.wikibase.statementview.RankSelector( {
				value: rank,
				templateParams: [ 'ui-state-disabled', '', '' ],
				// TODO: Directionality should be determined on entityview level and forwarded to here
				isRTL: $( document.documentElement ).prop( 'dir' ) === 'rtl'
			}, $rankSelector );

			var self = this,
				changeEvent = ( this._rankSelector.widgetEventPrefix + 'change' ).toLowerCase();

			this.$rankSelector.on( changeEvent + '.' + this.widgetName, function ( event ) {
				if ( self.value() ) {
					self._trigger( 'change' );
				}
			} );
		},

		/**
		 * @private
		 *
		 * @param {number} rank
		 * @return {string|null}
		 */
		_getRankName: function ( rank ) {
			for ( var rankName in datamodel.Statement.RANK ) {
				if ( rank === datamodel.Statement.RANK[ rankName ] ) {
					return rankName.toLowerCase();
				}
			}

			return null;
		},

		/**
		 * @private
		 */
		_createMainSnak: function () {
			if ( this.$mainSnak.data( 'snakview' ) ) {
				this._mainSnakSnakView = this.$mainSnak.data( 'snakview' );
				return;
			}

			var snak = this.options.value
				? this.options.value.getClaim().getMainSnak()
				: this.options.predefined.mainSnak || null;
			var self = this;

			this.$mainSnak
			.on( 'snakviewchange.' + this.widgetName, function ( event, status ) {
				event.stopPropagation();
				self._trigger( 'change' );
			} );

			this._mainSnakSnakView = this.options.buildSnakView(
				{
					locked: this.options.locked.mainSnak,
					autoStartEditing: false
				},
				snak,
				this.$mainSnak
			);
		},

		/**
		 * @private
		 *
		 * @param {datamodel.SnakList|null} [qualifiers=null]
		 */
		_createQualifiersListview: function ( qualifiers ) {
			if ( this._qualifiers ) {
				return;
			}

			var self = this,
				groupedQualifierSnaks = null;

			// Group qualifiers by property id:
			if ( qualifiers && qualifiers.length ) {
				groupedQualifierSnaks = qualifiers.getGroupedSnakLists();
			}

			// Using the property id, qualifier snaks are split into groups of snaklistviews. These
			// snaklistviews are managed in a listview:
			var $qualifiers = this.$qualifiers.children( '.wikibase-listview' );
			if ( !$qualifiers.length ) {
				$qualifiers = $( '<div>' ).prependTo( this.$qualifiers );
			}
			$qualifiers.listview( {
				listItemAdapter: this.options.getQualifiersListItemAdapter( function ( snaklistview ) {
					self._qualifiers.removeItem( snaklistview.element );
				} ),
				value: groupedQualifierSnaks
			} )
			.on( 'snaklistviewchange.' + this.widgetName,
				function ( event ) {
					event.stopPropagation();
					self._trigger( 'change' );
				}
			);

			this._qualifiers = $qualifiers.data( 'listview' );
		},

		/**
		 * @private
		 *
		 * @param {datamodel.Reference[]} [references]
		 */
		_createReferencesListview: function ( references ) {
			var self = this;

			var $listview = this.$references.children( '.wikibase-listview' );
			if ( !$listview.length ) {
				$listview = $( '<div>' ).prependTo( this.$references );
			} else if ( $listview.data( 'listview' ) ) {
				return;
			}

			var lia = this.options.getReferenceListItemAdapter(
				function ( referenceview ) {
					self._referencesListview.removeItem( referenceview.element );
					self._drawReferencesCounter();
					self._trigger( 'change' );
				}
			);

			$listview.listview( {
				listItemAdapter: lia,
				value: references
			} );

			this._referencesListview = $listview.data( 'listview' );

			$listview
			.on( lia.prefixedEvent( 'change.' + this.widgetName ), function ( event ) {
				event.stopPropagation();
				self._drawReferencesCounter();
				self._trigger( 'change' );
			} );

			this._createReferencesToggler();
		},

		_createReferencesToggler: function () {
			if ( this._$toggler ) {
				return;
			}

			var expanded, text;

			if ( this._referencesListview ) {
				expanded = this._referencesListview.items().length === 0;
				this.$references.toggleClass( 'wikibase-initially-collapsed', !expanded );
			} else {
				expanded = !this.$references.hasClass( 'wikibase-initially-collapsed' );
			}

			// toggle for references section:
			this._$toggler = $( '<a>' ).toggler( {
				$subject: this.$references,
				visible: expanded
			} );

			text = this.$refsHeading.text();
			if ( text ) {
				this._$toggler.find( '.ui-toggler-label' ).text( text );
				this.$refsHeading.empty().append( this._$toggler );
			} else {
				this.$refsHeading.empty().append( this._$toggler );
				this._drawReferencesCounter();
			}
		},

		/**
		 * @inheritdoc
		 */
		getHelpMessage: function () {
			var deferred = $.Deferred(),
				helpMessage = this.options.helpMessage;

			if ( !this.options.value && !this.options.predefined.mainSnak ) {
				deferred.resolve( helpMessage );
			} else {
				var property = this.options.value
					? this.options.value.getClaim().getMainSnak().getPropertyId()
					: this.options.predefined.mainSnak.property;

				if ( property ) {
					this.options.entityIdPlainFormatter.format( property ).done( function ( formattedEntityId ) {
						deferred.resolve( mw.msg( 'wikibase-claimview-snak-tooltip', formattedEntityId ) );
					} );
				} else {
					deferred.resolve( helpMessage );
				}
			}

			return deferred.promise();
		},

		/**
		 * @inheritdoc
		 */
		destroy: function () {
			if ( this._rankSelector ) {
				this._rankSelector.destroy();
				this.$rankSelector.off( '.' + this.widgetName );
				this._rankSelector = null;
			}

			if ( this._mainSnakSnakView ) {
				this._mainSnakSnakView.destroy();
				this.$mainSnak.off( '.' + this.widgetName );
				this._mainSnakSnakView = null;
			}

			if ( this._qualifiers ) {
				this._destroyQualifiersListView();
			}
			if ( this._referencesListview ) {
				this._destroyReferencesListview();
			}

			PARENT.prototype.destroy.call( this );
		},

		/**
		 * @private
		 */
		_destroyQualifiersListView: function () {
			this._qualifiers.destroy();
			this.$qualifiers
				.off( '.' + this.widgetName );
			this._qualifiers = null;

			if ( this._qualifierAdder ) {
				this._qualifierAdder.destroy();
				this._qualifierAdder = null;
			}
		},

		/**
		 * @private
		 */
		_destroyReferencesListview: function () {
			this._referencesListview.destroy();
			this.$references
				.off( '.' + this.widgetName )
				.empty();
			this._referencesListview = null;
			this._referenceAdder.destroy();
			this._referenceAdder = null;
		},

		/**
		 * @inheritdoc
		 */
		draw: function () {
			this._createRankSelector( this.options.value
				? this.options.value.getRank()
				: datamodel.Statement.RANK.NORMAL
			);
			this._createMainSnak();

			if (
				this.isInEditMode()
				|| this.options.value
				&& this.options.value.getClaim().getQualifiers().length
			) {
				this._createQualifiersListview(
					this.options.value
						? this.options.value.getClaim().getQualifiers()
						: new datamodel.SnakList()
				);
			}
			this._createReferencesListview(
				this.options.value ? this.options.value.getReferences().toArray() : []
			);

			return $.Deferred().resolve().promise();
		},

		/**
		 * Instantiates a `Statement` with the `statementview`'s current value.
		 *
		 * @private
		 *
		 * @param {string} guid
		 * @return {datamodel.Statement|null}
		 */
		_instantiateStatement: function ( guid ) {
			if ( !this.isInEditMode() ) {
				mw.log.warn( 'statementview::_instantiateStatement should only be called in edit mode' );
				return null;
			}

			var mainSnak = this._mainSnakSnakView.snak();
			if ( !mainSnak ) {
				return null;
			}

			var qualifiers = this._getQualifiers();
			if ( !qualifiers ) {
				return null;
			}

			var references = this._getReferences();
			if ( !references ) {
				return null;
			}

			return new datamodel.Statement(
				new datamodel.Claim( mainSnak, qualifiers, guid ),
				references,
				this._rankSelector.value()
			);
		},

		_enterNewReference: function () {
			var listview = this._referencesListview,
				lia = listview.listItemAdapter();

			listview.enterNewItem().done( function ( $referenceview ) {
				var referenceview = lia.liInstance( $referenceview );

				// Enter first item into the referenceview.
				referenceview.enterNewItem();
			} ).done( this._drawReferencesCounter.bind( this ) );
		},

		/**
		 * @private
		 *
		 * @return {datamodel.SnakList|null}
		 */
		_getQualifiers: function () {
			var qualifiers = new datamodel.SnakList();

			if ( this._qualifiers ) {
				var snaklistviews = this._qualifiers.value();

				// Combine qualifiers grouped by property to a single SnakList:
				for ( var i = 0; i < snaklistviews.length; i++ ) {
					var value = snaklistviews[ i ].value();
					if ( !value ) {
						return null;
					}
					qualifiers.merge( value );
				}
			}

			return qualifiers;
		},

		/**
		 * Returns all `Reference`s currently specified in the view (including all pending changes).
		 *
		 * @private
		 *
		 * @return {datamodel.ReferenceList|null}
		 */
		_getReferences: function () {
			var references = [];

			if ( !this._referencesListview.value().every( function ( referenceview ) {
				var value = referenceview.value();
				references.push( value );
				return value;
			} ) ) {
				return null;
			}

			return new datamodel.ReferenceList( references );
		},

		/**
		 * Returns the current `Statement` represented by the view, considering all pending changes not
		 * yet stored. Use `this.option( 'value' )` to retrieve the stored/original `Statement`.
		 *
		 * @return {datamodel.Statement|null}
		 */
		value: function ( newValue ) {
			if ( typeof newValue !== 'undefined' ) {
				return this.option( 'value', newValue );
			}
			if ( this.isInEditMode() ) {
				var guid = this.options.value ? this.options.value.getClaim().getGuid() : this.options.guidGenerator.newGuid();
				return this._instantiateStatement( guid );
			} else {
				return this.options.value;
			}
		},

		/**
		 * Updates the visual `Reference`s counter.
		 *
		 * @private
		 */
		_drawReferencesCounter: function () {
			var numberOfValues = 0;

			if ( this._referencesListview ) {
				numberOfValues = this._referencesListview.items().length;
			}

			var $counterMsg = buildCounter(
				'wikibase-statementview-references-counter',
				numberOfValues
			);

			// update counter, don't touch the toggle!
			this.$refsHeading.find( '.ui-toggler-label' ).empty().append( $counterMsg );
		},

		_startEditing: function () {
			var self = this;

			if ( this.options.value ) {
				this.options.fireStartEditingHook( this.options.value.getClaim().getGuid() );
			}
			this._qualifierAdder = this.options.getAdder(
				function () {
					var listview = self._qualifiers;
					listview.enterNewItem();

					var snaklistview = listview.value()[ listview.value().length - 1 ];
					snaklistview.enterNewItem().done( function () {
						snaklistview.focus();
					} );
				},
				this.$qualifiers,
				mw.msg( 'wikibase-addqualifier' )
			);

			return $.when(
				this._createMainSnak(),
				this.draw(),
				this._mainSnakSnakView.startEditing(),
				this._rankSelector.startEditing(),
				this._qualifiers.startEditing(),
				this._startEditingReferences()
			);
		},

		/**
		 * @protected
		 */
		_startEditingReferences: function () {
			this._referencesListview.startEditing();
			this._expandReferencesToggler();
		},

		/**
		 * @protected
		 */
		_expandReferencesToggler: function () {
			var toggler = this._$toggler.data( 'toggler' );
			if ( toggler.isCollapsed() ) {
				toggler.toggle();
			}
		},

		_stopEditing: function ( dropValue ) {
			if ( !dropValue ) {
				this.element.find( '.wikibase-snakview-indicators' ).empty();
			}
			if ( this.options.value ) {
				this.options.fireStopEditingHook( this.options.value.getClaim().getGuid() );
			}

			// TODO: this should return a promise
			this._stopEditingQualifiers( dropValue );

			return $.when(
				this._stopEditingReferences( dropValue ),
				this._mainSnakSnakView.stopEditing( dropValue ),
				this._rankSelector.stopEditing( dropValue )
			);
		},

		/**
		 * @protected
		 */
		_recreateReferences: function () {
			this._referencesListview.option( 'value', this.options.value
				? this.options.value.getReferences().toArray() : [] );

			this._drawReferencesCounter();
		},

		/**
		 * @private
		 *
		 * @param {boolean} [dropValue=false]
		 */
		_stopEditingReferences: function ( dropValue ) {
			this._recreateReferences(); // FIXME: Should not be necessary if _setOption would do the right thing for values
			return this._referencesListview.stopEditing( dropValue );
		},

		/**
		 * @private
		 *
		 * @param {boolean} [dropValue=false]
		 */
		_stopEditingQualifiers: function ( dropValue ) {
			var snaklistviews,
				i;

			snaklistviews = this._qualifiers.value();

			if ( snaklistviews.length ) {
				for ( i = 0; i < snaklistviews.length; i++ ) {
					snaklistviews[ i ].stopEditing( dropValue );

					if ( dropValue && !snaklistviews[ i ].value() ) {
						// Remove snaklistview from qualifier listview if no snakviews are left in
						// that snaklistview:
						this._qualifiers.removeItem( snaklistviews[ i ].element );
					}
				}
			}

			// Destroy and (if qualifiers still exist) re-create the qualifier listview in order to
			// re-group the qualifiers by their property. This will also send out the event to erase
			// the "add qualifier" toolbar.
			this._destroyQualifiersListView();

			var qualifiers = this.options.value ? this.options.value.getClaim().getQualifiers() : [];

			if ( qualifiers.length > 0 ) {
				// Refill the qualifier listview with the initial (or new initial) qualifiers:
				this._createQualifiersListview( qualifiers );
			}
		},

		/**
		 * @inheritdoc
		 * @protected
		 */
		_setOption: function ( key, value ) {
			var response = PARENT.prototype._setOption.apply( this, arguments );

			if ( key === 'disabled' ) {
				if ( this._mainSnakSnakView ) {
					this._mainSnakSnakView.option( key, value );
				}
				if ( this._qualifiers ) {
					this._qualifiers.option( key, value );
				}
				if ( this._rankSelector ) {
					this._rankSelector.option( key, value );
				}
				if ( this._referencesListview ) {
					this._referencesListview.option( key, value );
				}
				this._referenceAdder[ value ? 'disable' : 'enable' ]();
			}

			if ( key === 'value' ) {
				this.element.toggleClass( 'wb-new', value === null );

				if ( value !== null ) {
					if ( value.getClaim().getGuid() ) {
						this.element.addClass( 'wikibase-statement-' + value.getClaim().getGuid() );
					}

					if ( this._mainSnakSnakView ) {
						this._mainSnakSnakView.option( key, value.getClaim().getMainSnak() );
					}
					if ( this._qualifiers ) {
						this._qualifiers.option( key, value.getClaim().getQualifiers().getGroupedSnakLists() );
					}
					if ( this._rankSelector ) {
						this._rankSelector.option( key, value.getRank() );
					}
					if ( this._referencesListview ) {
						this._referencesListview.option( key, value.getReferences().toArray() );
					}
				}
			}

			return response;
		},

		/**
		 * @inheritdoc
		 */
		focus: function () {
			this._mainSnakSnakView.focus();
		}
	} );

}( wikibase ) );
