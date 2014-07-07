/**
 * Abstract QUnit tests for wikibase.Entity
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.4
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
		 * @param {object} [data] (optional)
		 * @return wb.datamodel.Entity
		 */
		function newEntity( data ) {
			return !data
				? wb.datamodel.Entity.newEmpty( entityType )
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
			return wb.datamodel.Entity.newFromMap( entity.toMap() );
		}

		/**
		 * Will test the tested Entity type's entity constructor.
		 */
		this.testConstructor = function( assert ) {
			assert.ok(
				newEntity() instanceof wb.datamodel.Entity,
				'New ' + entityType + ' empty Entity created'
			);

			$.each( testEntities, function( entityName, entity ) {
				assert.ok(
					entity instanceof wb.datamodel.Entity,
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

	}

	wb.datamodel.tests = wb.datamodel.tests || {};
	wb.datamodel.tests.testEntity = function( testDefinition ) {
		var test = new EntityTest( testDefinition );

		function callTestFn( testFnName ) {
			return function( assert ) {
				test[ testFnName ]( assert );
			};
		}

		QUnit.test( 'constructor', callTestFn( 'testConstructor' ) );
		QUnit.test( 'getters', callTestFn( 'testGetters' ) );
	};

	/**
	 * Basic test definition for Entity tests. Requires the following fields:
	 *
	 * - entityConstructor {Function} a constructor based on wb.datamodel.Entity
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
	wb.datamodel.tests.testEntity.basicTestDefinition = {
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
					new wb.datamodel.Claim(
						new wb.datamodel.PropertyNoValueSnak( 42 ),
						new wb.datamodel.SnakList()
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
