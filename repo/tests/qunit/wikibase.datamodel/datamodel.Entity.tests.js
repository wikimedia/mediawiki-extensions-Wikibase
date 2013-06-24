/**
 * Abstract QUnit tests for wikibase.Entity
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.4
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( wb, $, QUnit ) {
	'use strict';

	/**
	 * Constructor to create an Object holding basic Entity tests.
	 *
	 * @param {Object} testDefinition
	 * @constructor
	 */
	function EntityTest( testDefinition ) {
		/**
		 * Returns a definition from the test definition object.
		 *
		 * @param {string} name Name of the option
		 * @return {*}
		 */
		function definition( name ) {
			var value = testDefinition[ name ];
			if( value === undefined ) {
				throw new Error( 'undefined required test definition "' + name + '"' );
			}
			return value;
		}

		/**
		 * The type of Entities being tested.
		 * @type string
		 */
		var entityType = definition( 'entityConstructor' ).TYPE;

		/**
		 * Returns a new Entity with the given data given to the constructor, or an empty Entity
		 * if first parameter is omitted.
		 *
		 * @param {Object} data
		 * @return wb.Entity
		 */
		function newEntity( data ) {
			return !data
				? wb.Entity.newEmpty( entityType )
				: new ( definition( 'entityConstructor' ) )( data );
		}

		/**
		 * Test entities. Should be used as read-only in tests, so other tests will not be
		 * influenced by changes to entities. Use newEntityByName() to get a specific mock entity
		 * as unique instance.
		 * @type Object
		 */
		var testEntities = {};
		$.each( definition( 'testData' ), function( entityDataName, data ) {
			testEntities[ entityDataName ] = newEntity( data );
		} );

		/**
		 * Returns a new Entity built from a data set given in the test definition. The data set
		 * will be identified by the given name. Guarantees to return a unique instance of the
		 * entity, so it can be modified without having an influence on other tests using the same.
		 */
		function newEntityByName( name ) {
			var entity = testEntities[ name ];
			if( entity === undefined ) {
				throw new Error( 'No Entity data provided for "' + name + '" in test definition' );
			}
			return wb.Entity.newFromMap( entity.toMap() );
		}

		/**
		 * Will test the tested Entity type's entity constructor.
		 */
		this.testConstructor = function( assert ) {
			assert.ok(
				newEntity() instanceof wb.Entity,
				'New ' + entityType + ' empty Entity created'
			);

			$.each( testEntities, function( entityName, entity ) {
				assert.ok(
					entity instanceof wb.Entity,
					'New ' + entityType + ' Entity created from "' + entityName + '" Entity data'
				);
			} );
		};

		/**
		 * Will test the Entity type's getter functions. For doing so, the test defnition's
		 * "getters" field will be used to generate the actual test cases.
		 */
		this.testGetters = function( assert ) {
			/**
			 * Does the assertions for one getter test definition
			 *
			 * @param {string} getter Name of the getter function
			 * @param {Object} getterTestDefinition
			 */
			function getterTest( getter, getterTestDefinition ) {
				var testEntityName = getterTestDefinition[0],
					testEntity = newEntityByName( testEntityName ),
					getterParams = getterTestDefinition[1],
					expected = getterTestDefinition[2];

				if( $.isFunction( expected ) ) {
					var testEntityData = $.extend(
						true, {}, definition( 'testData' )[ testEntityName ]
					);
					expected = expected.call( null, testEntityData );
				}

				var getterTestResult = testEntity[ getter ].apply( testEntity, getterParams);

				assert.deepEqual(
					getterTestResult,
					expected,
					'"' + getter + '" works on test ' + entityType + ' "' + testEntityName +
						'" and returns expected result'
				);
			}

			// get getter test definitions and run all of them:
			var getterTestDefinitions = definition( 'getters' );

			$.each( getterTestDefinitions, function( getter, perGetterTestDefinition  ) {
				$.each( perGetterTestDefinition, function( i, getterTestDefinition ) {
					getterTest( getter, getterTestDefinition );
				} );
			} );
		};

		/**
		 * Will test the Entity type's "equal" function. This will be done for all entity
		 * definitions given in the test definition's "testData" field.
		 */
		this.testEquals = function( assert ) {
			// test all entities we got in test definition for equality here:
			$.each( testEntities, function( entityName, entity ) {
				var summaryIntro = entityType + ' Entity created from "' + entityName + '" data ';

				assert.ok(
					!entity.equals( 'foo' ),
					summaryIntro + 'is not equal to some totally unrelated non-Entity value'
				);

				assert.ok(
					entity.equals( entity ),
					summaryIntro + 'is equal to itself'
				);

				// Create equal entity and create equal entity but with added/removed ID to test'
				// whether ID actually gets ignored in the process of checking for equality.
				var entityMapData = entity.toMap(),
					entityCopy = wb.Entity.newFromMap( entityMapData ),
					idAction;

				if( entityMapData.id ) {
					entityMapData.id = 'foo';
					idAction = 'added ID';
				} else {
					delete( entityMapData.id );
					idAction = 'removed ID';
				}
				var entityCopyWithSwitchedId = wb.Entity.newFromMap( entityMapData );

				assert.ok(
					entity.equals( entityCopy ) && entityCopy.equals( entity ),
					summaryIntro + 'is equal to copy of itself'
				);

				assert.ok(
					entityCopyWithSwitchedId.equals( entityCopy ),
					summaryIntro + 'is equal to copy of itself with ' + idAction
				);

				// Test this entity against all other entities given in the test definition.
				// This Entity should not be equal to any other given Entity!
				$.each( testEntities, function( otherTestEntityName, otherEntity ) {
					if( otherTestEntityName !== entityName ) {
						assert.ok(
							!entity.equals( otherEntity ),
							summaryIntro + ' is not equal to Entity based on "' +
								otherTestEntityName + '" data'
						);
					}
				} );
			} );
		};

		/**
		 * Will test the Entity type's "isSameAs" function. Similar to "equals" test.
		 */
		this.testIsSameAs = function( assert ) {
			// test all entities we got in test definition for equality here:
			$.each( testEntities, function( entityName, entity ) {
				var summaryIntro = entityType + ' Entity created from "' + entityName + '" data ';

				assert.ok(
					!entity.isSameAs( 'foo' ),
					summaryIntro + 'is not same as some totally unrelated non-Entity value'
				);

				// for following tests, we want the entity in a version with an ID, and one without
				// ID since "isSameAs" heavily depends on whether an Entity has an ID.
				var entityMapData = entity.toMap(),
					entityWithId,
					entityWithoutId;

				if( entityMapData.id ) {
					entityWithId = wb.Entity.newFromMap( entityMapData );
					delete( entityMapData.id );
					entityWithoutId = wb.Entity.newFromMap( entityMapData );
				} else {
					entityWithId = wb.Entity.newFromMap(
						$.extend( { id: 'testIsSame-uniqueId' }, entityMapData ) );
					entityWithoutId = wb.Entity.newFromMap( entityMapData );
					summaryIntro += '(but with id "' + entityWithId.getId() + '") ';
				}

				assert.ok(
					entityWithId.isSameAs( entityWithId ),
					'is equal to itself because Entity has an ID'
				);

				assert.ok(
					!entityWithoutId.isSameAs( entityWithoutId ),
					'is not equal to itself because Entity has no ID yet'
				);

				// lets trust that "isSameAs" works in case the entity has no ID anyhow and perform
				// following tests only for the version of the entity with ID:
				var entityWithIdCopy = wb.Entity.newFromMap( entityWithId.toMap() );

				assert.ok(
					entityWithId.isSameAs( entityWithIdCopy ) && entityWithIdCopy.isSameAs( entityWithId ),
					summaryIntro + 'is equal to copy of itself'
				);

				// Test this entity against all other entities given in the test definition.
				// This Entity should not be equal to any other given Entity!
				$.each( testEntities, function( otherTestEntityName, otherEntity ) {
					if( otherTestEntityName !== entityName ) {
						assert.ok(
							!entity.isSameAs( otherEntity ),
							summaryIntro + 'is not equal to Entity based on "' +
								otherTestEntityName + '" data'
						);

					}
				} );
			} );
		};
	}

	wb.tests.testEntity = function( testDefinition ) {
		var test = new EntityTest( testDefinition );

		function callTestFn( testFnName ) {
			return function( assert ) {
				test[ testFnName ]( assert );
			};
		}

		QUnit.test( 'constructor', callTestFn( 'testConstructor' ) );
		QUnit.test( 'getters', callTestFn( 'testGetters' ) );
		QUnit.test( 'equals', callTestFn( 'testEquals' ) );
		QUnit.test( 'isSameAs', callTestFn( 'testIsSameAs' ) );
	};

	/**
	 * Basic test definition for Entity tests. Requires the following fields:
	 *
	 * - entityConstructor {Function} a constructor based on wb.Entity
	 *
	 * - testData {Object} Entity definitions required for the tests. Should be in a format
	 *    compatible to the constructor given in "entityConstructor". The Object's keys serve as
	 *    identifiers for each entity data definition. One requirement is, that none of the data
	 *    should create an equal entity. All entities defined here will be checked against each
	 *    other for equality in the "testEquals" test. The following keys need to be defined:
	 *    - empty: Data for an empty entity.
	 *    - full: Data for an entity which should have some data set for each possible field.
	 *
	 * - getters {Object} Object with keys named after getter functions. Values are different test
	 *    definitions for that getter. Each definition's array values should be structured
	 *    like the following:
	 *    (1) testData Entity data
	 *    (2) getter fn parameters
	 *    (3) expected result. Can be a function returning the expected result. The first parameter
	 *        is a copy of the data set (name given in (1)) used to create the test entity.
	 *
	 * @type Object
	 */
	wb.tests.testEntity.basicTestDefinition = {
		entityConstructor: null,
		testData: {
			empty: {},
			newOne: { // doesn't have an ID defined yet
				label: { foo: 'foo' }
			},
			full: {
				id: 'foo42',
				label: {
					de: 'de-label',
					en: 'en-label',
					th: 'th-label'
				},
				description: {
					de: 'de-descr',
					en: 'en-descr',
					th: 'th-descr'
				},
				aliases: {
					de: [ 'de1', 'de2' ],
					en: [ 'en1', 'en2' ],
					th: [ 'th1', 'th2' ]
				},
				claims: [
					new wb.Claim(
						new wb.PropertyNoValueSnak( 42 ),
						new wb.SnakList()
					)
				]
			}
		},
		getters: {
			isNew: [
				[ 'empty', [], true ],
				[ 'newOne', [], true ],
				[ 'full', [], false ]
			],
			getId: [
				[ 'empty', [], null ],
				[ 'full', [], 'foo42' ]
			],
			getLabel: [
				[ 'empty', [ 'de' ], null ],
				[ 'empty', [ 'xxx' ], null ],
				[ 'full', [ 'de' ], function( data ) { return data.label.de; } ],
				[ 'full', [ 'en' ], function( data ) { return data.label.en; } ],
				[ 'full', [ 'xxx' ], null ]
			],
			getDescription: [
				[ 'empty', [ 'de' ], null ],
				[ 'empty', [ 'xxx' ], null ],
				[ 'full', [ 'de' ], function( data ) { return data.description.de; } ],
				[ 'full', [ 'en' ], function( data ) { return data.description.en; } ],
				[ 'full', [ 'xxx' ], null ]
			],
			getAliases: [
				[ 'empty', [ 'de' ], null ],
				[ 'empty', [ 'xxx' ], null ],
				[ 'full', [ 'de' ], function( data ) { return data.aliases.de; } ],
				[ 'full', [ 'en' ], function( data ) { return data.aliases.en; } ],
				[ 'full', [ 'xxx' ], null ]
			],
			getLabels: [
				[ 'empty', [], {} ],
				[ 'empty', [ [ 'de', 'en', 'xxx' ] ], {} ],
				[ 'full', [], function( data ) { return data.label; } ],
				[ 'full', [ [ 'de', 'en', 'xxx' ] ], { de: 'de-label', en: 'en-label' } ],
				[ 'full', [ [ 'xxx' ] ], {} ]
			],
			getDescriptions: [
				[ 'empty', [], {} ],
				[ 'empty', [ [ 'de', 'en', 'xxx' ] ], {} ],
				[ 'full', [], function( data ) { return data.description; } ],
				[ 'full', [ [ 'de', 'en', 'xxx' ] ], { de: 'de-descr', en: 'en-descr' } ],
				[ 'full', [ [ 'xxx' ] ], {} ]
			],
			getAllAliases: [
				[ 'empty', [], {} ],
				[ 'empty', [ [ 'de', 'en', 'xxx' ] ], {} ],
				[ 'full', [], function( data ) { return data.aliases; } ],
				[ 'full', [ [ 'de', 'en', 'xxx' ] ], { de: [ 'de1', 'de2' ], en: [ 'en1', 'en2' ] } ],
				[ 'full', [ [ 'xxx' ] ], {} ]
			],
			getClaims: [
				[ 'empty', [], [] ],
				[ 'full', [], function( data ) { return data.claims; } ]
			]
		}
	};

}( wikibase, jQuery, QUnit ) );
