import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import EditDecision from '@/definitions/EditDecision';
import EditFlow from '@/definitions/EditFlow';
import { BridgeConfigOptions } from '@/presentation/plugins/BridgeConfigPlugin/BridgeConfig';
import {
	BRIDGE_ERROR_ADD,
	BRIDGE_INIT,
	BRIDGE_INIT_WITH_REMOTE_DATA,
	BRIDGE_REQUEST_TARGET_LABEL,
	BRIDGE_SAVE,
	BRIDGE_SET_EDIT_DECISION,
	BRIDGE_SET_TARGET_VALUE,
	BRIDGE_VALIDATE_APPLICABILITY,
	BRIDGE_VALIDATE_ENTITY_STATE,
} from '@/store/actionTypes';
import {
	ENTITY_INIT,
	ENTITY_SAVE,
} from '@/store/entity/actionTypes';
import {
	STATEMENT_RANK,
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/statements/getterTypes';
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
import Vue, { VueConstructor } from 'vue';
import PropertyDatatypeRepository from '@/definitions/data-access/PropertyDatatypeRepository';
import BridgeTracker from '@/definitions/data-access/BridgeTracker';
import {
	BridgePermissionsRepository,
	MissingPermissionsError,
	PageNotEditable,
	CascadeProtectedReason,
} from '@/definitions/data-access/BridgePermissionsRepository';
import { inject } from 'vuex-smart-module';
import { RootActions } from '@/store/actions';
import AppInformation from '@/definitions/AppInformation';
import newMockServiceContainer from '../services/newMockServiceContainer';
import newApplicationState from './newApplicationState';
import { MainSnakPath } from '@/store/statements/MainSnakPath';
import {
	SNAK_DATATYPE,
	SNAK_DATAVALUETYPE,
	SNAK_SNAKTYPE,
} from '@/store/statements/snaks/getterTypes';
import DataValue from '@/datamodel/DataValue';
import { SNAK_SET_STRING_DATA_VALUE } from '@/store/statements/snaks/actionTypes';

const mockBridgeConfig = jest.fn();
jest.mock( '@/presentation/plugins/BridgeConfigPlugin', () => ( {
	__esModule: true,
	default: ( vue: VueConstructor<Vue>, options?: BridgeConfigOptions ) => mockBridgeConfig( vue, options ),
} ) );

describe( 'root/actions', () => {
	const defaultEntityId = 'Q32';
	const defaultPropertyId = 'P71';
	function newMockAppInformation( fields: Partial<AppInformation> = {} ): AppInformation {
		return {
			editFlow: EditFlow.OVERWRITE,
			propertyId: defaultPropertyId,
			entityId: defaultEntityId,
			entityTitle: 'Entity Title',
			originalHref: '',
			pageTitle: '',
			client: { usePublish: true },
			...fields,
		};
	}

	describe( BRIDGE_INIT, () => {

		const wikibaseRepoConfigRepository = {
			getRepoConfiguration: jest.fn( () => Promise.resolve() ),
		};
		const editAuthorizationChecker: BridgePermissionsRepository = {
			canUseBridgeForItemAndPage: jest.fn().mockResolvedValue( [] ),
		};
		const propertyDatatypeRepository: PropertyDatatypeRepository = {
			getDataType: jest.fn().mockResolvedValue( 'string' ),
		};

		const listOfCommits = [
			EDITFLOW_SET,
			PROPERTY_TARGET_SET,
			ENTITY_TITLE_SET,
			ORIGINAL_HREF_SET,
			PAGE_TITLE_SET,
		];
		it(
			`commits to ${listOfCommits.join( ', ' )}`,
			async () => {
				const editFlow = EditFlow.OVERWRITE;
				const propertyId = 'P42';
				const entityTitle = 'Douglas Adams';
				const originalHref = 'https://example.com/index.php?title=Item:Q42&uselang=en#P31';
				const pageTitle = 'Client_page';
				const information = newMockAppInformation( {
					editFlow,
					propertyId,
					entityTitle,
					originalHref,
					pageTitle,
				} );

				const commit = jest.fn();
				const actions = inject( RootActions, {
					commit,
					dispatch: jest.fn(),
				} );
				// @ts-ignore
				actions.store = {
					$services: newMockServiceContainer( {
						wikibaseRepoConfigRepository,
						editAuthorizationChecker,
						propertyDatatypeRepository,
					} ),
				};

				// @ts-ignore
				actions.entityModule = {
					dispatch: jest.fn(),
				};

				await actions[ BRIDGE_INIT ]( information );
				expect( commit ).toHaveBeenCalledWith( EDITFLOW_SET, editFlow );
				expect( commit ).toHaveBeenCalledWith( PROPERTY_TARGET_SET, propertyId );
				expect( commit ).toHaveBeenCalledWith( ENTITY_TITLE_SET, entityTitle );
				expect( commit ).toHaveBeenCalledWith( ORIGINAL_HREF_SET, originalHref );
				expect( commit ).toHaveBeenCalledWith( PAGE_TITLE_SET, pageTitle );
			},
		);

		it( `dispatches to ${BRIDGE_REQUEST_TARGET_LABEL}`, async () => {
			const propertyId = 'P42';
			const information = newMockAppInformation( {
				propertyId,
			} );

			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				commit: jest.fn(),
				dispatch,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					wikibaseRepoConfigRepository,
					editAuthorizationChecker,
					propertyDatatypeRepository,
				} ),
			};

			// @ts-ignore
			actions.entityModule = {
				dispatch: jest.fn(),
			};

			await actions[ BRIDGE_INIT ]( information );
			expect( dispatch ).toHaveBeenCalledWith( BRIDGE_REQUEST_TARGET_LABEL, propertyId );
		} );

		it( `Requests repoConfig, authorization, datatype and dispatches ${ENTITY_INIT} in that order`, async () => {
			const propertyId = 'P42';
			const entityTitle = 'Douglas Adams';
			const pageTitle = 'South_Pole_Telescope';
			const entityId = 'Q42';
			const information = newMockAppInformation( {
				propertyId,
				entityTitle,
				pageTitle,
				entityId,
			} );
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				commit: jest.fn(),
				dispatch,
			} );

			const mockWikibaseRepoConfig = {};
			const wikibaseRepoConfigRepository = {
				getRepoConfiguration: jest.fn().mockResolvedValue( mockWikibaseRepoConfig ),
			};
			const mockListOfAuthorizationErrors: MissingPermissionsError[] = [];
			const editAuthorizationChecker = {
				canUseBridgeForItemAndPage: jest.fn().mockResolvedValue( mockListOfAuthorizationErrors ),
			};
			const propertyDataType = 'string';
			const propertyDatatypeRepository: PropertyDatatypeRepository = {
				getDataType: jest.fn().mockResolvedValue( propertyDataType ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					wikibaseRepoConfigRepository,
					editAuthorizationChecker,
					propertyDatatypeRepository,
				} ),
			};
			const ignoredEntityInitResult = {};
			const entityModuleDispatch = jest.fn().mockResolvedValue( ignoredEntityInitResult );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};

			await actions[ BRIDGE_INIT ]( information );
			expect( wikibaseRepoConfigRepository.getRepoConfiguration ).toHaveBeenCalled();
			expect( editAuthorizationChecker.canUseBridgeForItemAndPage ).toHaveBeenCalledWith(
				entityTitle,
				pageTitle,
			);
			expect( propertyDatatypeRepository.getDataType ).toHaveBeenCalledWith( propertyId );
			expect( entityModuleDispatch ).toHaveBeenCalledWith( ENTITY_INIT, { entity: entityId } );
			expect( dispatch ).toHaveBeenCalledTimes( 2 );
			expect( dispatch.mock.calls[ 1 ][ 0 ] ).toBe( BRIDGE_INIT_WITH_REMOTE_DATA );
			expect( dispatch.mock.calls[ 1 ][ 1 ] ).toStrictEqual( {
				information,
				results: [
					mockWikibaseRepoConfig,
					mockListOfAuthorizationErrors,
					propertyDataType,
					ignoredEntityInitResult,
				],
			} );

		} );

		it( `Commits to ${APPLICATION_ERRORS_ADD} if there is an error`, async () => {
			const information = newMockAppInformation();
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
				dispatch: jest.fn(),
			} );
			const error = new Error( 'This should be logged and propagated' );
			const wikibaseRepoConfigRepository = {
				getRepoConfiguration: jest.fn().mockRejectedValue( error ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					wikibaseRepoConfigRepository,
					editAuthorizationChecker,
					propertyDatatypeRepository,
				} ),
			};

			// @ts-ignore
			actions.entityModule = {
				dispatch: jest.fn(),
			};
			await expect( actions[ BRIDGE_INIT ]( information ) ).rejects.toBe( error );
			expect( commit ).toHaveBeenCalledWith(
				APPLICATION_ERRORS_ADD,
				[ {
					type: ErrorTypes.APPLICATION_LOGIC_ERROR,
					info: error,
				} ],
			);
		} );
	} );

	describe( `${BRIDGE_REQUEST_TARGET_LABEL}`, () => {

		it( `commits to ${TARGET_LABEL_SET} if request was successful`, async () => {
			const propertyId = 'Q42';
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const propertyLabel = 'instance of';
			const entityLabelRepository = {
				getLabel: jest.fn().mockResolvedValue( propertyLabel ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					entityLabelRepository,
				} ),
			};

			await actions[ BRIDGE_REQUEST_TARGET_LABEL ]( propertyId );
			expect( entityLabelRepository.getLabel ).toHaveBeenCalledWith( propertyId );
			expect( commit ).toHaveBeenCalledWith( TARGET_LABEL_SET, propertyLabel );
		} );

		it( 'does nothing if there was an error', async () => {
			const commit = jest.fn();
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				commit,
				dispatch,
			} );
			const error = new Error( 'ignored' );
			const entityLabelRepository = {
				getLabel: jest.fn().mockRejectedValue( error ),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					entityLabelRepository,
				} ),
			};

			await actions[ BRIDGE_REQUEST_TARGET_LABEL ]( 'Q123' );
			expect( commit ).not.toHaveBeenCalled();
			expect( dispatch ).not.toHaveBeenCalled();
		} );

	} );

	describe( `${BRIDGE_INIT_WITH_REMOTE_DATA}`, () => {
		const tracker: BridgeTracker = {
			trackPropertyDatatype: jest.fn(),
		};

		it( `commits to ${APPLICATION_ERRORS_ADD} if there are permission errors`, async () => {
			const information = newMockAppInformation();
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
				dispatch: jest.fn(),
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker,
				} ),
			};
			const permissionErrors = [ {
				type: PageNotEditable.ITEM_CASCADE_PROTECTED,
				info: {
					pages: [ 'foo' ],
				},
			} as CascadeProtectedReason ];

			await actions[ BRIDGE_INIT_WITH_REMOTE_DATA ]( { information, results: [
				{} as WikibaseRepoConfiguration,
				permissionErrors,
				'string',
				undefined,
			] } );

			expect( commit ).toHaveBeenCalledWith( APPLICATION_ERRORS_ADD, permissionErrors );
		} );

		it( 'tracks the datatype', async () => {
			const information = newMockAppInformation();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ {} ],
						},
					},
				} ),
				commit: jest.fn(),
				dispatch: jest.fn(),
				getters: jest.fn() as any,
			} );
			const propertyDataType = 'string';
			const tracker: BridgeTracker = {
				trackPropertyDatatype: jest.fn(),
			};

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker,
				} ),
			};
			await actions[ BRIDGE_INIT_WITH_REMOTE_DATA ]( { information, results: [
				{} as WikibaseRepoConfiguration,
				[],
				propertyDataType,
				undefined,
			] } );

			expect( tracker.trackPropertyDatatype ).toHaveBeenCalledWith( propertyDataType );

		} );

		it( 'resets the config plugin', async () => {
			const information = newMockAppInformation();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ {} ],
						},
					},
				} ),
				commit: jest.fn(),
				dispatch: jest.fn(),
				getters: jest.fn() as any,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker,
				} ),
			};

			const wikibaseRepoConfiguration = {
				dataTypeLimits: {
					string: {
						maxLength: 12345,
					},
				},
			};

			await actions[ BRIDGE_INIT_WITH_REMOTE_DATA ]( { information, results: [
				wikibaseRepoConfiguration,
				[],
				'string',
				undefined,
			] } );

			expect( mockBridgeConfig ).toHaveBeenCalledWith(
				Vue,
				{ ...wikibaseRepoConfiguration, ...information.client },
			);
		} );

		it( `dispatches ${BRIDGE_VALIDATE_ENTITY_STATE}`, async () => {
			const information = newMockAppInformation();
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ {} ],
						},
					},
				} ),
				commit: jest.fn(),
				dispatch,
				getters: jest.fn() as any,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker,
				} ),
			};
			const mainSnakPath = new MainSnakPath( defaultEntityId, defaultPropertyId, 0 );

			await actions[ BRIDGE_INIT_WITH_REMOTE_DATA ]( { information, results: [
				{} as WikibaseRepoConfiguration,
				[],
				'string',
				undefined,
			] } );

			expect( dispatch ).toHaveBeenCalledWith( BRIDGE_VALIDATE_ENTITY_STATE, mainSnakPath );

		} );

		it( `commits to ${ORIGINAL_STATEMENT_SET} and ${APPLICATION_STATUS_SET} if there are no errors`, async () => {
			const information = newMockAppInformation();
			const commit = jest.fn();
			const statements = {};
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ statements ],
						},
					},
				} ),
				commit,
				dispatch: jest.fn(),
				getters: jest.fn() as any,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker,
				} ),
			};

			await actions[ BRIDGE_INIT_WITH_REMOTE_DATA ]( { information, results: [
				{} as WikibaseRepoConfiguration,
				[],
				'string',
				undefined,
			] } );

			expect( commit ).toHaveBeenCalledWith( ORIGINAL_STATEMENT_SET, statements );
			expect( commit ).toHaveBeenCalledWith( APPLICATION_STATUS_SET, ApplicationStatus.READY );
		} );

		it( 'doesn\'t commit if there are errors', async () => {
			const information = newMockAppInformation();
			const commit = jest.fn();
			const actions = inject( RootActions, {
				state: newApplicationState( {
					entity: { id: defaultEntityId },
					targetProperty: defaultPropertyId,
					statements: {
						[ defaultEntityId ]: {
							[ defaultPropertyId ]: [ {} ],
						},
					},
				} ),
				commit,
				dispatch: jest.fn(),
				getters: {
					applicationStatus: ApplicationStatus.ERROR,
				} as any,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					tracker,
				} ),
			};

			await actions[ BRIDGE_INIT_WITH_REMOTE_DATA ]( { information, results: [
				{} as WikibaseRepoConfiguration,
				[],
				'string',
				undefined,
			] } );

			expect( commit ).not.toHaveBeenCalled();
		} );

	} );

	describe( `${BRIDGE_VALIDATE_ENTITY_STATE}`, () => {
		it( `commits to ${APPLICATION_ERRORS_ADD} if property is missing on entity`, () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const statementPropertyGetter = jest.fn().mockReturnValue( false );

			// @ts-ignore
			actions.statementModule = {
				getters: {
					[ STATEMENTS_PROPERTY_EXISTS ]: statementPropertyGetter,
				} as any,
			};

			const entityId = 'Q42';
			const propertyId = 'P42';
			const mainSnakPath = new MainSnakPath( entityId, propertyId, 0 );
			actions[ BRIDGE_VALIDATE_ENTITY_STATE ]( mainSnakPath );
			expect( statementPropertyGetter ).toHaveBeenCalledWith( entityId, propertyId );
			expect( commit ).toHaveBeenCalledWith(
				APPLICATION_ERRORS_ADD,
				[ { type: ErrorTypes.INVALID_ENTITY_STATE_ERROR } ],
			);
		} );

		it( `dispatches ${BRIDGE_VALIDATE_APPLICABILITY} if there are no errors`, () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				dispatch,
			} );
			const statementPropertyGetter = jest.fn().mockReturnValue( true );

			// @ts-ignore
			actions.statementModule = {
				getters: {
					[ STATEMENTS_PROPERTY_EXISTS ]: statementPropertyGetter,
				} as any,
			};
			const mainSnakPath = new MainSnakPath( 'Q42', 'P42', 0 );
			actions[ BRIDGE_VALIDATE_ENTITY_STATE ]( mainSnakPath );
			expect( dispatch ).toHaveBeenCalledWith( BRIDGE_VALIDATE_APPLICABILITY, mainSnakPath );
		} );
	} );

	describe( `${BRIDGE_VALIDATE_APPLICABILITY}`, () => {
		const statementModule = {
			getters: {
				[ STATEMENTS_IS_AMBIGUOUS ]: jest.fn().mockReturnValue( false ),
				[ STATEMENT_RANK ]: jest.fn().mockReturnValue( 'normal' ),
				[ SNAK_SNAKTYPE ]: jest.fn().mockReturnValue( 'value' ),
				[ SNAK_DATATYPE ]: jest.fn().mockReturnValue( 'string' ),
				[ SNAK_DATAVALUETYPE ]: jest.fn().mockReturnValue( 'string' ),
			} as any,
		};

		it( 'doesn\'t dispatch error if applicable', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				dispatch,
			} );

			// @ts-ignore
			actions.statementModule = statementModule;

			const entityId = 'Q42';
			const propertyId = 'P42';
			const mainSnakPath = new MainSnakPath( entityId, propertyId, 0 );
			actions[ BRIDGE_VALIDATE_APPLICABILITY ]( mainSnakPath );
			expect( dispatch ).not.toHaveBeenCalled();
			expect( statementModule.getters[ STATEMENTS_IS_AMBIGUOUS ] ).toHaveBeenCalledWith( entityId, propertyId );
			expect( statementModule.getters[ SNAK_SNAKTYPE ] ).toHaveBeenCalledWith( mainSnakPath );
			expect( statementModule.getters[ SNAK_DATAVALUETYPE ] ).toHaveBeenCalledWith( mainSnakPath );
		} );

		it( 'dispatches error on ambiguous statements', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				dispatch,
			} );

			statementModule.getters[ STATEMENTS_IS_AMBIGUOUS ].mockReturnValueOnce( true );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions[ BRIDGE_VALIDATE_APPLICABILITY ]( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				BRIDGE_ERROR_ADD,
				[ { type: ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT } ],
			);

		} );

		it( 'dispatches error on deprecated statement', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				dispatch,
			} );

			statementModule.getters[ STATEMENT_RANK ].mockReturnValueOnce( 'deprecated' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions[ BRIDGE_VALIDATE_APPLICABILITY ]( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				BRIDGE_ERROR_ADD,
				[ { type: ErrorTypes.UNSUPPORTED_DEPRECATED_STATEMENT } ],
			);
		} );

		it( 'dispatches error for non-value snak types', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				dispatch,
			} );

			statementModule.getters[ SNAK_SNAKTYPE ].mockReturnValueOnce( 'novalue' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions[ BRIDGE_VALIDATE_APPLICABILITY ]( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				BRIDGE_ERROR_ADD,
				[ { type: ErrorTypes.UNSUPPORTED_SNAK_TYPE } ],
			);

		} );

		it( 'dispatches error for non-string data types', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				dispatch,
			} );

			statementModule.getters[ SNAK_DATATYPE ].mockReturnValueOnce( 'url' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions[ BRIDGE_VALIDATE_APPLICABILITY ]( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				BRIDGE_ERROR_ADD,
				[ {
					type: ErrorTypes.UNSUPPORTED_DATATYPE,
					info: {
						unsupportedDatatype: 'url',
						supportedDatatypes: [ 'string' ],
					},
				} ],
			);
		} );

		it( 'dispatches error for non-string data value types', () => {
			const dispatch = jest.fn();
			const actions = inject( RootActions, {
				dispatch,
			} );

			statementModule.getters[ SNAK_DATAVALUETYPE ].mockReturnValueOnce( 'number' );

			// @ts-ignore
			actions.statementModule = statementModule;
			actions[ BRIDGE_VALIDATE_APPLICABILITY ]( new MainSnakPath( 'Q42', 'P42', 0 ) );

			expect( dispatch ).toHaveBeenCalledWith(
				BRIDGE_ERROR_ADD,
				[ { type: ErrorTypes.UNSUPPORTED_DATAVALUE_TYPE } ],
			);
		} );
	} );

	describe( `${BRIDGE_SET_TARGET_VALUE}`, () => {
		it( `logs an error if the application is not in ${ApplicationStatus.READY} state and rejects`, async () => {
			const commit = jest.fn();
			const state = newApplicationState();
			const actions = inject( RootActions, {
				commit,
				state,
			} );

			await expect( actions[ BRIDGE_SET_TARGET_VALUE ]( {} as DataValue ) ).rejects.toBeNull();
			expect( commit ).toHaveBeenCalledTimes( 1 );
			expect( commit.mock.calls[ 0 ][ 0 ] ).toBe( APPLICATION_ERRORS_ADD );
			const arrayOfActualErrors = commit.mock.calls[ 0 ][ 1 ];
			expect( arrayOfActualErrors.length ).toBe( 1 );
			expect( arrayOfActualErrors[ 0 ].type ).toBe( ErrorTypes.APPLICATION_LOGIC_ERROR );
			expect( arrayOfActualErrors[ 0 ].info ).toHaveProperty( 'stack' );
		} );

		it( 'dispatches a statement action to store the value in the store', async () => {
			const entityId = 'Q42';
			const targetPropertyId = 'P42';
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				targetProperty: targetPropertyId,
				entity: {
					id: entityId,
				},
			} );
			const actions = inject( RootActions, {
				state,
			} );

			const statementModuleDispatch = jest.fn().mockResolvedValue( undefined );

			// @ts-ignore
			actions.statementModule = {
				dispatch: statementModuleDispatch,
			};

			const dataValue: DataValue = {
				type: 'string',
				value: 'the value',
			};
			const payload = {
				path: new MainSnakPath( entityId, targetPropertyId, 0 ),
				value: dataValue,
			};

			await expect( actions[ BRIDGE_SET_TARGET_VALUE ]( dataValue ) ).resolves.toBe( undefined );
			expect( statementModuleDispatch ).toHaveBeenCalledWith( SNAK_SET_STRING_DATA_VALUE, payload );
		} );

		it( 'propagates an error in case it occurs in the statement action', async () => {
			const commit = jest.fn();
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
				entity: {
					id: 'Q42',
				},
			} );
			const actions = inject( RootActions, {
				commit,
				state,
			} );

			const error = new Error( 'This error should be logged and propagated' );
			const statementModuleDispatch = jest.fn().mockRejectedValue( error );

			// @ts-ignore
			actions.statementModule = {
				dispatch: statementModuleDispatch,
			};

			await expect( actions[ BRIDGE_SET_TARGET_VALUE ]( {} as DataValue ) ).rejects.toBe( error );
			expect( commit ).toHaveBeenCalledWith( APPLICATION_ERRORS_ADD, [ {
				type: ErrorTypes.APPLICATION_LOGIC_ERROR,
				info: error,
			} ] );
		} );
	} );

	describe( `${BRIDGE_SAVE}`, () => {
		it( `logs an error if the application is not in ${ApplicationStatus.READY} state and rejects`, async () => {
			const commit = jest.fn();
			const state = newApplicationState();
			const actions = inject( RootActions, {
				commit,
				state,
			} );

			await expect( actions[ BRIDGE_SAVE ]() ).rejects.toBeNull();
			expect( commit ).toHaveBeenCalledTimes( 1 );
			expect( commit.mock.calls[ 0 ][ 0 ] ).toBe( APPLICATION_ERRORS_ADD );
			const arrayOfActualErrors = commit.mock.calls[ 0 ][ 1 ];
			expect( arrayOfActualErrors.length ).toBe( 1 );
			expect( arrayOfActualErrors[ 0 ].type ).toBe( ErrorTypes.APPLICATION_LOGIC_ERROR );
			expect( arrayOfActualErrors[ 0 ].info ).toHaveProperty( 'stack' );
		} );

		it( `it dispatches ${ENTITY_SAVE}`, async () => {
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
			} );
			const actions = inject( RootActions, {
				state,
			} );

			const entityModuleDispatch = jest.fn( () => Promise.resolve() );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};
			await actions[ BRIDGE_SAVE ]();
			expect( entityModuleDispatch ).toHaveBeenCalledWith( ENTITY_SAVE );
		} );

		it( 'logs an error if saving failed and rejects', async () => {
			const state = newApplicationState( {
				applicationStatus: ApplicationStatus.READY,
			} );
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
				state,
			} );

			const error = new Error( 'This error should be logged and propagated.' );
			const entityModuleDispatch = jest.fn().mockRejectedValue( error );

			// @ts-ignore
			actions.entityModule = {
				dispatch: entityModuleDispatch,
			};
			await expect( actions[ BRIDGE_SAVE ]() ).rejects.toBe( error );
			expect( commit ).toHaveBeenCalledWith(
				APPLICATION_ERRORS_ADD,
				[ { type: ErrorTypes.SAVING_FAILED, info: error } ],
			);
		} );
	} );

	describe( `${BRIDGE_ERROR_ADD}`, () => {
		it( 'it commits the provided error', () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const errors: ApplicationError[] = [ { type: ErrorTypes.SAVING_FAILED } ];
			actions[ BRIDGE_ERROR_ADD ]( errors );
			expect( commit ).toHaveBeenCalledWith( APPLICATION_ERRORS_ADD, errors );
		} );
	} );

	describe( `${BRIDGE_SET_EDIT_DECISION}`, () => {
		it( 'commits the edit decision to the store', () => {
			const commit = jest.fn();
			const actions = inject( RootActions, {
				commit,
			} );
			const editDecision = EditDecision.REPLACE;

			actions[ BRIDGE_SET_EDIT_DECISION ]( editDecision );

			expect( commit ).toHaveBeenCalledWith( EDITDECISION_SET, editDecision );
		} );
	} );

} );
