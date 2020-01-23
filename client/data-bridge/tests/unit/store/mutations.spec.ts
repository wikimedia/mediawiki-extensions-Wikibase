import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import EditDecision from '@/definitions/EditDecision';
import EditFlow from '@/definitions/EditFlow';
import Application from '@/store/Application';
import { RootMutations } from '@/store/mutations';
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
import newApplicationState from './newApplicationState';
import { inject } from 'vuex-smart-module';

describe( 'root/mutations', () => {
	it( 'changes the targetProperty of the state', () => {
		const state: Application = newApplicationState();

		const mutations = inject( RootMutations, { state } );

		mutations[ PROPERTY_TARGET_SET ]( 'P42' );
		expect( state.targetProperty ).toBe( 'P42' );
	} );

	it( 'changes the editFlow of the state', () => {
		const editFlow: EditFlow = EditFlow.OVERWRITE;
		const state: Application = newApplicationState();

		const mutations = inject( RootMutations, { state } );
		mutations[ EDITFLOW_SET ]( editFlow );
		expect( state.editFlow ).toBe( editFlow );
	} );

	it( 'changes the originalHref of the state', () => {
		const state: Application = newApplicationState();
		const originalHref = 'https://example.com/index.php?title=Item:Q42&uselang=en#P31';

		const mutations = inject( RootMutations, { state } );

		mutations[ ORIGINAL_HREF_SET ]( originalHref );
		expect( state.originalHref ).toBe( originalHref );
	} );

	it( 'changes the applicationStatus of the state', () => {
		const state: Application = newApplicationState();
		const mutations = inject( RootMutations, { state } );
		mutations[ APPLICATION_STATUS_SET ]( ApplicationStatus.READY );
		expect( state.applicationStatus ).toBe( ApplicationStatus.READY );
	} );

	it( 'changes the targetLabel of the state', () => {
		const targetLabel = { language: 'el', value: 'πατατα' };
		const state: Application = newApplicationState();
		const mutations = inject( RootMutations, { state } );

		mutations[ TARGET_LABEL_SET ]( targetLabel );
		expect( state.targetLabel ).toBe( targetLabel );
	} );

	it( 'changes the originalStatement of the state', () => {
		const targetProperty = {
			type: 'statement' as any,
			id: 'opaque statement ID',
			rank: 'normal' as any,
			mainsnak: {
				snaktype: 'novalue' as any,
				property: 'P60',
				datatype: 'string',
			},
		};
		const state: Application = newApplicationState();

		const mutations = inject( RootMutations, { state } );

		mutations[ ORIGINAL_STATEMENT_SET ]( targetProperty );
		expect( state.originalStatement ).not.toBe( targetProperty );
		expect( state.originalStatement ).toStrictEqual( targetProperty );
	} );

	it( 'adds errors to the state', () => {
		const state: Application = newApplicationState();
		const errors: ApplicationError[] = [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ];

		const mutations = inject( RootMutations, { state } );

		mutations[ APPLICATION_ERRORS_ADD ]( errors );
		expect( state.applicationErrors ).toStrictEqual( errors );
	} );

	it( 'does not drop existing errors when adding new ones to the state', () => {
		const oldErrors: ApplicationError[] = [ { type: ErrorTypes.INVALID_ENTITY_STATE_ERROR } ];
		const state: Application = newApplicationState( { applicationErrors: oldErrors.slice() } );
		const newErrors: ApplicationError[] = [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ];

		const mutations = inject( RootMutations, { state } );

		mutations[ APPLICATION_ERRORS_ADD ]( newErrors );
		expect( state.applicationErrors ).toStrictEqual( [ ...oldErrors, ...newErrors ] );
	} );

	it( 'sets the edit decision of the state', () => {
		const state: Application = newApplicationState();
		const editDecision = EditDecision.REPLACE;

		const mutations = inject( RootMutations, { state } );

		mutations[ EDITDECISION_SET ]( editDecision );
		expect( state.editDecision ).toBe( editDecision );
	} );

	it( 'sets the entity title of the state', () => {
		const state: Application = newApplicationState();
		const entityTitle = 'Entity title';

		const mutations = inject( RootMutations, { state } );

		mutations[ ENTITY_TITLE_SET ]( entityTitle );
		expect( state.entityTitle ).toBe( entityTitle );
	} );

	it( 'sets the page title of the store', () => {
		const state: Application = newApplicationState();
		const pageTitle = 'Page_title';

		const mutations = inject( RootMutations, { state } );

		mutations[ PAGE_TITLE_SET ]( pageTitle );
		expect( state.pageTitle ).toBe( pageTitle );
	} );
} );
