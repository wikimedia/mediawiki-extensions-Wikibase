import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import EntityLabelRepository from '@/definitions/data-access/EntityLabelRepository';
import WikibaseRepoConfigRepository from '@/definitions/data-access/WikibaseRepoConfigRepository';
import EditDecision from '@/definitions/EditDecision';
import EditFlow from '@/definitions/EditFlow';
import { BridgeConfigOptions } from '@/presentation/plugins/BridgeConfigPlugin/BridgeConfig';
import actions from '@/store/actions';
import {
	BRIDGE_ERROR_ADD,
	BRIDGE_INIT,
	BRIDGE_SAVE,
	BRIDGE_SET_EDIT_DECISION,
	BRIDGE_SET_TARGET_VALUE,
} from '@/store/actionTypes';
import {
	ENTITY_INIT,
	ENTITY_SAVE,
} from '@/store/entity/actionTypes';
import { STATEMENTS_PROPERTY_EXISTS } from '@/store/entity/statements/getterTypes';
import { mainSnakActionTypes } from '@/store/entity/statements/mainSnakActionTypes';
import {
	APPLICATION_ERRORS_ADD,
	APPLICATION_STATUS_SET,
	EDITDECISION_SET,
	EDITFLOW_SET,
	ORIGINAL_STATEMENT_SET,
	PROPERTY_TARGET_SET,
	TARGET_LABEL_SET,
	ENTITY_TITLE_SET,
	ORIGINAL_HREF_SET,
	PAGE_TITLE_SET,
} from '@/store/mutationTypes';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	action,
	getter,
} from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import newMockStore from '@wmde/vuex-helpers/dist/newMockStore';
import Vue, { VueConstructor } from 'vue';
import PropertyDatatypeRepository from '@/definitions/data-access/PropertyDatatypeRepository';
import BridgeTracker from '@/definitions/data-access/BridgeTracker';
import {
	BridgePermissionsRepository,
	MissingPermissionsError,
	PageNotEditable,
} from '@/definitions/data-access/BridgePermissionsRepository';

const mockValidateBridgeApplicability = jest.fn();
jest.mock( '@/store/validateBridgeApplicability', () => ( {
	__esModule: true,
	default: () => mockValidateBridgeApplicability(),
} ) );

const mockBridgeConfig = jest.fn();
jest.mock( '@/presentation/plugins/BridgeConfigPlugin', () => ( {
	__esModule: true,
	default: ( vue: VueConstructor<Vue>, options?: BridgeConfigOptions ) => mockBridgeConfig( vue, options ),
} ) );

describe( 'root/actions', () => {
	const defaultEntityId = 'Q32';
	const defaultPropertyId = 'P71';
	const defaultEntityTitle = 'Entity Title';
	const entityLabelRepository = {
		getLabel: jest.fn( () => Promise.resolve() ),
	};
	const wikibaseRepoConfigRepository = {
		getRepoConfiguration: jest.fn().mockResolvedValue( {
			dataTypeLimits: {
				string: {
					maxLength: 200,
				},
			},
		} ),
	};
	const propertyDatatypeRepository: PropertyDatatypeRepository = {
		getDataType: jest.fn().mockResolvedValue( 'string' ),
	};
	const tracker: BridgeTracker = {
		trackPropertyDatatype: jest.fn(),
	};

	const editAuthorizationChecker: BridgePermissionsRepository = {
		canUseBridgeForItemAndPage: jest.fn().mockResolvedValue( [] ),
	};

	describe( BRIDGE_INIT, () => {
		function mockedStore(
			entityId?: string,
			targetProperty?: string,
			gettersOverride?: any,
		): any {
			return newMockStore( {
				state: {
					targetProperty: targetProperty || defaultPropertyId,
					[ NS_ENTITY ]: {
						id: entityId || defaultEntityId,
						[ NS_STATEMENTS ]: {
							[ entityId || defaultEntityId ]: {
								[ targetProperty || defaultPropertyId ]: [ {} ],
							},
						},
					},
				},
				getters: { ...{
					[ getter(
						NS_ENTITY,
						NS_STATEMENTS,
						STATEMENTS_PROPERTY_EXISTS,
					) ]: jest.fn().mockReturnValue( true ),
				}, ...gettersOverride },
			} );
		}

		function initAction( services: {
			entityLabelRepository?: EntityLabelRepository;
			wikibaseRepoConfigRepository?: WikibaseRepoConfigRepository;
			propertyDatatypeRepository?: PropertyDatatypeRepository;
			tracker?: BridgeTracker;
			editAuthorizationChecker?: BridgePermissionsRepository;
		} = {} ): Function {
			return ( actions as Function )(
				services.entityLabelRepository || entityLabelRepository,
				services.wikibaseRepoConfigRepository || wikibaseRepoConfigRepository,
				services.propertyDatatypeRepository || propertyDatatypeRepository,
				services.tracker || tracker,
				services.editAuthorizationChecker || editAuthorizationChecker,
			)[ BRIDGE_INIT ];
		}

		it( `commits to ${EDITFLOW_SET}`, () => {
			const editFlow = EditFlow.OVERWRITE;
			const context = mockedStore();

			const information = {
				editFlow,
				propertyId: defaultPropertyId,
				entityId: defaultEntityId,
				entityTitle: defaultEntityTitle,
			};

			return initAction()( context, information ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith(
					EDITFLOW_SET,
					editFlow,
				);
			} );
		} );

		it( `commits to ${PROPERTY_TARGET_SET}`, () => {
			const propertyId = 'P42';
			const context = mockedStore();

			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId,
				entityId: defaultEntityId,
				entityTitle: defaultEntityTitle,
			};

			return initAction()( context, information ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith(
					PROPERTY_TARGET_SET,
					propertyId,
				);
			} );
		} );

		it( `commits to ${ORIGINAL_HREF_SET}`, () => {
			const originalHref = 'https://example.com/index.php?title=Item:Q42&uselang=en#P31';
			const context = mockedStore();

			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId: 'P42',
				entityId: defaultEntityId,
				entityTitle: defaultEntityTitle,
				originalHref,
			};

			return initAction()( context, information ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith(
					ORIGINAL_HREF_SET,
					originalHref,
				);
			} );
		} );

		it( `commits to ${ENTITY_TITLE_SET}`, () => {
			const entityTitle = 'Douglas Adams';
			const context = mockedStore();

			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId: defaultPropertyId,
				entityId: defaultEntityId,
				entityTitle,
			};

			return initAction()( context, information ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith(
					ENTITY_TITLE_SET,
					entityTitle,
				);
			} );
		} );

		it( `commits to ${PAGE_TITLE_SET}`, () => {
			const pageTitle = 'Douglas_Adams';
			const context = mockedStore();

			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId: defaultPropertyId,
				entityId: defaultEntityId,
				entityTitle: defaultEntityTitle,
				pageTitle,
			};

			return initAction()( context, information ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith(
					PAGE_TITLE_SET,
					pageTitle,
				);
			} );
		} );

		it( 'tracks opening of the bridge with the expected data type', () => {
			const dataType = 'string';
			const getDataTypePromise = Promise.resolve( dataType );
			const propertyDatatypeRepository: PropertyDatatypeRepository = {
				getDataType: jest.fn().mockReturnValue( getDataTypePromise ),
			};
			const tracker: BridgeTracker = {
				trackPropertyDatatype: jest.fn(),
			};

			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId: defaultPropertyId,
			};

			return initAction( {
				propertyDatatypeRepository,
				tracker,
			} )( mockedStore(), information ).then( () => {
				expect( propertyDatatypeRepository.getDataType ).toHaveBeenCalledWith( information.propertyId );
				expect( tracker.trackPropertyDatatype ).toHaveBeenCalledWith( dataType );
			} );
		} );

		it( 'does not track opening of the bridge on permission error', () => {
			const dataType = 'string';
			const getDataTypePromise = Promise.resolve( dataType );
			const propertyDatatypeRepository: PropertyDatatypeRepository = {
				getDataType: jest.fn().mockReturnValue( getDataTypePromise ),
			};
			const tracker: BridgeTracker = {
				trackPropertyDatatype: jest.fn(),
			};
			const permissionErrors: MissingPermissionsError[] = [
				{
					type: PageNotEditable.ITEM_SEMI_PROTECTED,
					info: { right: 'editsemiprotected' },
				},
			];
			const editAuthorizationChecker: BridgePermissionsRepository = {
				canUseBridgeForItemAndPage: jest.fn().mockResolvedValue( permissionErrors ),
			};

			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId: defaultPropertyId,
			};

			return initAction( {
				propertyDatatypeRepository,
				tracker,
				editAuthorizationChecker,
			} )( mockedStore(), information ).then( () => {
				expect( propertyDatatypeRepository.getDataType ).toHaveBeenCalledWith( information.propertyId );
				expect( tracker.trackPropertyDatatype ).not.toHaveBeenCalled();
			} );
		} );

		it( `dispatches to ${action( NS_ENTITY, ENTITY_INIT )}$`, () => {
			const entityId = 'Q42';
			const context = mockedStore( entityId );

			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId: defaultPropertyId,
				entityId,
			};

			return initAction()( context, information ).then( () => {
				expect( context.dispatch ).toHaveBeenCalledWith(
					action( NS_ENTITY, ENTITY_INIT ),
					{ 'entity': entityId },
				);
			} );
		} );

		it( 'resets the BridgeConfigPlugin', () => {
			const context = mockedStore();
			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId: defaultPropertyId,
				entityId: defaultEntityId,
				client: {
					usePublish: true,
				},
			};
			const wikibaseRepoConfiguration = {
				dataTypeLimits: {
					string: {
						maxLength: 12345,
					},
				},
			};
			const wikibaseRepoConfigRepository = {
				getRepoConfiguration: jest.fn().mockResolvedValue( wikibaseRepoConfiguration ),
			};

			return initAction( { wikibaseRepoConfigRepository } )( context, information ).then( () => {
				expect( mockBridgeConfig ).toHaveBeenCalledWith(
					Vue,
					{ ...wikibaseRepoConfiguration, ...information.client },
				);
			} );
		} );

		it( `commits to ${ORIGINAL_STATEMENT_SET}`, () => {
			const entityId = 'Q42';
			const propertyId = 'P23';
			const context = mockedStore( entityId, propertyId );
			const shallowProperty = {
				type: 'statement',
				id: 'opaque statement ID',
				rank: 'normal',
				mainsnak: {
					snaktype: 'value',
					property: 'P60',
					datatype: 'string',
					datavalue: {
						type: 'string',
						value: 'a string value',
					},
				},
			};

			const shallowNestedState = { ...context.state, ...{
				[ NS_ENTITY ]: {
					id: entityId,
					[ NS_STATEMENTS ]: {
						[ entityId ]: {
							[ propertyId ]: [ shallowProperty ],
						},
					},
				},
			} };

			context.state = shallowNestedState;

			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId,
				entityId,
			};

			return initAction()( context, information ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith(
					ORIGINAL_STATEMENT_SET,
					shallowProperty,
				);
			} );

		} );

		describe( TARGET_LABEL_SET, () => {
			it( `commits to ${TARGET_LABEL_SET}, on successful label request`, () => {
				const propertyId = 'P42';
				const context = mockedStore();

				const information = {
					editFlow: EditFlow.OVERWRITE,
					propertyId,
					entityId: defaultEntityId,
				};

				const term = {
					language: 'zxx',
					value: propertyId,
				};
				const entityLabelRepository = {
					getLabel: jest.fn().mockResolvedValue( term ),
				};

				return initAction( { entityLabelRepository } )( context, information ).then( () => {
					expect( context.commit ).toHaveBeenCalledWith(
						TARGET_LABEL_SET,
						term,
					);
					expect( entityLabelRepository.getLabel ).toHaveBeenCalledWith( propertyId );
				} );
			} );

			it( 'does nothing on reject', () => {
				const propertyId = 'P42';
				const context = mockedStore();

				const information = {
					editFlow: EditFlow.OVERWRITE,
					propertyId,
					entityId: defaultEntityId,
				};

				const entityLabelRepository = {
					getLabel: jest.fn().mockRejectedValue( 'Fehler' ),
				};

				return initAction( { entityLabelRepository } )( context, information ).then( () => {
					expect( context.commit ).not.toHaveBeenCalledWith( TARGET_LABEL_SET );
					expect( entityLabelRepository.getLabel ).toHaveBeenCalledWith( propertyId );
				} );
			} );
		} );

		describe( 'Applicationstatus', () => {
			const information = {
				editFlow: EditFlow.OVERWRITE,
				propertyId: 'P123',
				entityId: 'Q123',
			};

			it( `commits to ${APPLICATION_STATUS_SET} on successful entity lookup`, () => {
				const context = mockedStore();

				return initAction()( context, information ).then( () => {
					expect( context.commit ).toHaveBeenCalledWith( APPLICATION_STATUS_SET, ApplicationStatus.READY );
				} );
			} );

			describe( 'error state', () => {
				it( `commits to ${APPLICATION_ERRORS_ADD} on fail entity lookup`, () => {
					const error = { some: 'error' };
					const context = newMockStore( {
						dispatch: () => Promise.reject( error ),
					} );

					return initAction()( context, information ).catch( () => {
						expect( context.commit ).toHaveBeenCalledWith(
							APPLICATION_ERRORS_ADD,
							[ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: error } ],
						);
					} );
				} );

				it( `commits to ${APPLICATION_ERRORS_ADD} on missing statements`, () => {
					const entityId = 'Q42';
					const targetProperty = 'P23';

					const context = mockedStore(
						entityId,
						targetProperty,
						{
							[ getter(
								NS_ENTITY,
								NS_STATEMENTS,
								STATEMENTS_PROPERTY_EXISTS,
							) ]: jest.fn().mockReturnValue( false ),
						},
					);

					return initAction()( context, information ).then( () => {
						expect(
							context.getters[ getter(
								NS_ENTITY,
								NS_STATEMENTS,
								STATEMENTS_PROPERTY_EXISTS,
							) ],
						).toHaveBeenCalledWith( entityId, targetProperty );
						expect( context.commit ).toHaveBeenCalledWith(
							APPLICATION_ERRORS_ADD,
							[ { type: ErrorTypes.INVALID_ENTITY_STATE_ERROR } ],
						);
					} );
				} );

				it( `doesn't commit to ${APPLICATION_STATUS_SET} on lack of bridge support`, () => {
					const entityId = 'Q42';
					const targetProperty = 'P23';

					const context = mockedStore(
						entityId,
						targetProperty,
					);

					mockValidateBridgeApplicability.mockImplementation(
						() => {
							context.getters.applicationStatus = ApplicationStatus.ERROR;
						},
					);

					return initAction()( context, information ).then( () => {
						expect( context.commit ).not.toHaveBeenCalledWith(
							APPLICATION_STATUS_SET,
							ApplicationStatus.READY,
						);
					} );
				} );
			} );
		} );

		it( `commits permission errors to ${APPLICATION_ERRORS_ADD} and bails`, () => {
			const permissionErrors: MissingPermissionsError[] = [
				{
					type: PageNotEditable.ITEM_SEMI_PROTECTED,
					info: { right: 'editsemiprotected' },
				},
			];
			const editAuthorizationChecker: BridgePermissionsRepository = {
				canUseBridgeForItemAndPage: jest.fn().mockResolvedValue( permissionErrors ),
			};
			const context = {
				dispatch: jest.fn(),
				commit: jest.fn(),
			};
			const entityTitle = 'Item:Q4711';
			const pageTitle = 'South_Pole_Telescope';
			const information = {
				entityTitle,
				pageTitle,
			};

			return initAction( {
				editAuthorizationChecker,
			} )( context, information ).then( () => {
				expect( editAuthorizationChecker.canUseBridgeForItemAndPage )
					.toHaveBeenCalledWith( entityTitle, pageTitle );

				expect( context.commit ).toHaveBeenCalledWith( APPLICATION_ERRORS_ADD, permissionErrors );
				expect( mockBridgeConfig ).not.toHaveBeenCalled();
			} );
		} );
	} );

	describe( BRIDGE_SET_TARGET_VALUE, () => {
		function setTargetValueAction( services: {
			entityLabelRepository?: EntityLabelRepository;
			wikibaseRepoConfigRepository?: WikibaseRepoConfigRepository;
		} = {} ): Function {
			return ( actions as Function )(
				services.entityLabelRepository || entityLabelRepository,
				services.wikibaseRepoConfigRepository || wikibaseRepoConfigRepository,
			)[ BRIDGE_SET_TARGET_VALUE ];
		}

		it( 'rejects if the store is not ready and switch into error state', async () => {
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.INITIALIZING,
				},
			} );
			await expect(
				setTargetValueAction()(
					context,
					{
						path: null,
						value: {
							type: 'string',
							value: 'a string',
						},
					},
				),
			).rejects.toBeDefined();
			expect( context.dispatch ).toHaveBeenCalledTimes( 0 );
			expect( ( context.commit as jest.Mock ).mock.calls[ 0 ][ 0 ] ).toBe( APPLICATION_ERRORS_ADD );
			const errors = ( context.commit as jest.Mock ).mock.calls[ 0 ][ 1 ];
			expect( errors ).toHaveLength( 1 );
			expect( errors[ 0 ].type ).toBe( ErrorTypes.APPLICATION_LOGIC_ERROR );
			expect( errors[ 0 ].info ).toHaveProperty( 'stack' );
		} );

		it( 'propagtes errors and switch into error state', async () => {
			const sampleError = 'sampleError';
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.READY,
					[ NS_ENTITY ]: {
						id: defaultEntityId,
					},
				},
				dispatch: jest.fn( () => {
					return new Promise( () => {
						throw new Error( sampleError );
					} );
				} ),
			} );

			await expect(
				setTargetValueAction()(
					context,
					{
						value: {
							type: 'string',
							value: 'a string',
						},
					},
				),
			).rejects.toStrictEqual( Error( sampleError ) );
			expect( context.commit ).toHaveBeenCalledWith(
				APPLICATION_ERRORS_ADD,
				[ {
					type: ErrorTypes.APPLICATION_LOGIC_ERROR,
					info: Error( sampleError ),
				} ],
			);
		} );

		it( 'determine the current path and passes down the new data value', () => {
			const entityId = 'Q42';
			const targetProperty = 'P23';
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.READY,
					targetProperty,
					[ NS_ENTITY ]: {
						id: entityId,
					},
				},
			} );

			const dataValue = {
				type: 'string',
				value: 'Töfften',
			};

			const payload = {
				path: {
					entityId,
					propertyId: targetProperty,
					index: 0,
				},
				value: dataValue,
			};

			return setTargetValueAction()(
				context,
				dataValue,
			).then( () => {
				expect( context.dispatch ).toHaveBeenCalledWith(
					action(
						NS_ENTITY,
						NS_STATEMENTS,
						mainSnakActionTypes.setStringDataValue,
					),
					payload,
				);
			} );
		} );
	} );

	describe( BRIDGE_SAVE, () => {
		function saveAction( services: {
			entityLabelRepository?: EntityLabelRepository;
			wikibaseRepoConfigRepository?: WikibaseRepoConfigRepository;
		} = {} ): Function {
			return ( actions as Function )(
				services.entityLabelRepository || entityLabelRepository,
				services.wikibaseRepoConfigRepository || wikibaseRepoConfigRepository,
			)[ BRIDGE_SAVE ];
		}

		it( 'rejects if the store is not ready and switch into error state', async () => {
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.INITIALIZING,
				},
			} );

			await expect( saveAction()( context ) ).rejects.toBeDefined();
			expect( context.dispatch ).toHaveBeenCalledTimes( 0 );
			expect( ( context.commit as jest.Mock ).mock.calls[ 0 ][ 0 ] ).toBe( APPLICATION_ERRORS_ADD );
			const errors = ( context.commit as jest.Mock ).mock.calls[ 0 ][ 1 ];
			expect( errors ).toHaveLength( 1 );
			expect( errors[ 0 ].type ).toBe( ErrorTypes.APPLICATION_LOGIC_ERROR );
			expect( errors[ 0 ].info ).toHaveProperty( 'stack' );
		} );

		it( 'propagtes errors and switch into error state', async () => {
			const sampleError = new Error( 'sampleError' );
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.READY,
				},
				dispatch: jest.fn( () => {
					return new Promise( () => {
						throw sampleError;
					} );
				} ),
			} );

			await expect( saveAction()( context ) ).rejects.toBe( sampleError );

			expect( context.commit ).toHaveBeenCalledWith(
				APPLICATION_ERRORS_ADD,
				[ {
					type: ErrorTypes.SAVING_FAILED,
					info: sampleError,
				} ],
			);
		} );

		it( `dispatches ${ENTITY_SAVE}`, () => {
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.READY,
				},
				dispatch: jest.fn( () => Promise.resolve() ),
			} );

			return saveAction()( context ).then( () => {
				expect( context.dispatch ).toHaveBeenCalledWith( action( NS_ENTITY, ENTITY_SAVE ) );
				expect( context.dispatch ).toHaveBeenCalledTimes( 1 );
			} );
		} );
	} );

	describe( BRIDGE_ERROR_ADD, () => {
		function errorAddAction( services: {
			entityLabelRepository?: EntityLabelRepository;
			wikibaseRepoConfigRepository?: WikibaseRepoConfigRepository;
		} = {} ): Function {
			return ( actions as Function )(
				services.entityLabelRepository || entityLabelRepository,
				services.wikibaseRepoConfigRepository || wikibaseRepoConfigRepository,
			)[ BRIDGE_ERROR_ADD ];
		}

		it( `commits to ${APPLICATION_ERRORS_ADD}`, () => {
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.READY,
				},
			} );
			const errors: ApplicationError[] = [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ];

			errorAddAction()( context, errors );

			expect( context.commit ).toHaveBeenCalledWith(
				APPLICATION_ERRORS_ADD,
				errors,
			);
		} );
	} );

	describe( BRIDGE_SET_EDIT_DECISION, () => {
		function setEditDecision( services: {
			entityLabelRepository?: EntityLabelRepository;
			wikibaseRepoConfigRepository?: WikibaseRepoConfigRepository;
		} = {} ): Function {
			return ( actions as Function )(
				services.entityLabelRepository || entityLabelRepository,
				services.wikibaseRepoConfigRepository || wikibaseRepoConfigRepository,
			)[ BRIDGE_SET_EDIT_DECISION ];
		}

		it( `commits to ${EDITDECISION_SET}`, () => {
			const context = newMockStore( {} );

			setEditDecision()( context, EditDecision.REPLACE );

			expect( context.commit ).toHaveBeenCalledWith(
				EDITDECISION_SET,
				EditDecision.REPLACE,
			);
		} );
	} );

} );
