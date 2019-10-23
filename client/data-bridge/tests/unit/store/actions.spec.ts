import EditFlow from '@/definitions/EditFlow';
import actions from '@/store/actions';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	ENTITY_INIT,
	ENTITY_SAVE,
} from '@/store/entity/actionTypes';
import {
	BRIDGE_INIT,
	BRIDGE_SAVE,
	BRIDGE_SET_TARGET_VALUE,
} from '@/store/actionTypes';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET,
	APPLICATION_STATUS_SET,
	TARGET_LABEL_SET,
	ORIGINAL_STATEMENT_SET,
} from '@/store/mutationTypes';
import { STATEMENTS_PROPERTY_EXISTS } from '@/store/entity/statements/getterTypes';
import { mainSnakActionTypes } from '@/store/entity/statements/mainSnakActionTypes';
import newMockStore from '@wmde/vuex-helpers/dist/newMockStore';
import { action, getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import EntityLabelRepository from '@/definitions/data-access/EntityLabelRepository';
import WikibaseRepoConfigRepository from '@/definitions/data-access/WikibaseRepoConfigRepository';
import Vue, { VueConstructor } from 'vue';
import { BridgeConfigOptions } from '@/presentation/plugins/BridgeConfigPlugin';

const mockValidateBridgeApplicability = jest.fn().mockReturnValue( true );
jest.mock( '@/store/validateBridgeApplicability', () => ( {
	__esModule: true,
	default: ( context: any ) => mockValidateBridgeApplicability( context ),
} ) );

const mockBridgeConfig = jest.fn();
jest.mock( '@/presentation/plugins/BridgeConfigPlugin', () => ( {
	__esModule: true,
	default: ( vue: VueConstructor<Vue>, options?: BridgeConfigOptions ) => mockBridgeConfig( vue, options ),
} ) );

describe( 'root/actions', () => {
	const defaultEntityId = 'Q32';
	const defaultPropertyId = 'P71';
	const entityLabelRepository = {
		getLabel: jest.fn( () => Promise.resolve() ),
	};
	const wikibaseRepoConfigRepository = {
		getRepoConfiguration: jest.fn( () => Promise.resolve( {
			dataTypeLimits: {
				string: {
					maxLength: 200,
				},
			},
		} ) ),
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
					) ]: jest.fn( () => true ),
				}, ...gettersOverride },
			} );
		}

		function initAction( services: {
			entityLabelRepository?: EntityLabelRepository;
			wikibaseRepoConfigRepository?: WikibaseRepoConfigRepository;
		} = {} ): Function {
			return ( actions as Function )(
				services.entityLabelRepository || entityLabelRepository,
				services.wikibaseRepoConfigRepository || wikibaseRepoConfigRepository,
			)[ BRIDGE_INIT ];
		}

		it( `commits to ${EDITFLOW_SET}`, () => {
			const editFlow = EditFlow.OVERWRITE;
			const context = mockedStore();

			const information = {
				editFlow,
				propertyId: defaultPropertyId,
				entityId: defaultEntityId,
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
			};

			return initAction()( context, information ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith(
					PROPERTY_TARGET_SET,
					propertyId,
				);
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
				getRepoConfiguration: jest.fn( () => Promise.resolve( wikibaseRepoConfiguration ) ),
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
					getLabel: jest.fn( () => {
						return Promise.resolve( term );
					} ),
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
					getLabel: jest.fn( () => {
						return Promise.reject( 'Fehler' );
					} ),
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
				it( `commits to ${APPLICATION_STATUS_SET} on fail entity lookup`, () => {
					const context = newMockStore( {
						dispatch: () => Promise.reject(),
					} );

					return initAction()( context, information ).catch( () => {
						expect( context.commit ).toHaveBeenCalledWith(
							APPLICATION_STATUS_SET,
							ApplicationStatus.ERROR,
						);
					} );
				} );

				it( `commits to ${APPLICATION_STATUS_SET} on missing statements`, () => {
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
							) ]: jest.fn( () => false ),
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
							APPLICATION_STATUS_SET,
							ApplicationStatus.ERROR,
						);
					} );
				} );

				it( `commits to ${APPLICATION_STATUS_SET} on lack of bridge support`, () => {
					mockValidateBridgeApplicability.mockReturnValue( false );

					const entityId = 'Q42';
					const targetProperty = 'P23';

					const context = mockedStore(
						entityId,
						targetProperty,
					);

					return initAction()( context, information ).then( () => {
						expect( context.commit ).toHaveBeenCalledWith(
							APPLICATION_STATUS_SET,
							ApplicationStatus.ERROR,
						);
					} );
				} );
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
			expect( context.commit ).toHaveBeenCalledWith(
				APPLICATION_STATUS_SET,
				ApplicationStatus.ERROR,
			);
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
				APPLICATION_STATUS_SET,
				ApplicationStatus.ERROR,
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
			expect( context.commit ).toHaveBeenCalledWith(
				APPLICATION_STATUS_SET,
				ApplicationStatus.ERROR,
			);
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
				APPLICATION_STATUS_SET,
				ApplicationStatus.ERROR,
			);
		} );

		it( `dispatches ${ENTITY_SAVE}`, () => {
			const context = newMockStore( {
				state: {
					applicationStatus: ApplicationStatus.READY,
				},
				dispatch: jest.fn( () => {
					return Promise.resolve();
				} ),
			} );

			return saveAction()( context ).then( () => {
				expect( context.dispatch ).toHaveBeenCalledWith( action( NS_ENTITY, ENTITY_SAVE ) );
				expect( context.dispatch ).toHaveBeenCalledTimes( 1 );
			} );
		} );
	} );
} );
