import { DataType } from '@wmde/wikibase-datamodel-types';
import MessageKeys from '@/definitions/MessageKeys';
import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import { shallowMount } from '@vue/test-utils';
import {
	MissingPermissionsError,
	PageNotEditable,
	ProtectedReason,
} from '@/definitions/data-access/BridgePermissionsRepository';
import ErrorPermission from '@/presentation/components/ErrorPermission.vue';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';
import ErrorUnsupportedDatatype from '@/presentation/components/ErrorUnsupportedDatatype.vue';
import ErrorDeprecatedStatement from '@/presentation/components/ErrorDeprecatedStatement.vue';
import ErrorAmbiguousStatement from '@/presentation/components/ErrorAmbiguousStatement.vue';
import ErrorUnsupportedSnakType from '@/presentation/components/ErrorUnsupportedSnakType.vue';
import ErrorSaving from '@/presentation/components/ErrorSaving.vue';
import ErrorSavingAssertUser from '@/presentation/components/ErrorSavingAssertUser.vue';
import ErrorSavingEditConflict from '@/presentation/components/ErrorSavingEditConflict.vue';
import ApplicationError, { ErrorTypes, UnsupportedDatatypeError } from '@/definitions/ApplicationError';
import { createTestStore } from '../../../util/store';

describe( 'ErrorWrapper', () => {
	it( 'mounts ErrorUnknown on empty applicationErrors', () => {
		const store = createTestStore( {
			state: {
				applicationErrors: [],
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );
		expect( wrapper.findComponent( ErrorUnknown ).exists() ).toBe( true );
		expect( wrapper.findComponent( ErrorPermission ).exists() ).toBe( false );
	} );

	it( 'mounts ErrorUnknown for unknown errors', () => {
		const store = createTestStore( {
			state: {
				applicationErrors: [
					{
						type: ErrorTypes.INVALID_ENTITY_STATE_ERROR,
					},
				],
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );
		expect( wrapper.findComponent( ErrorUnknown ).exists() ).toBe( true );
		expect( wrapper.findComponent( ErrorPermission ).exists() ).toBe( false );
	} );

	it( 'shows ErrorPermission if a permission error is contained in the application errors', () => {
		const applicationErrors: ApplicationError[] = [
			{
				type: PageNotEditable.ITEM_SEMI_PROTECTED,
			} as ProtectedReason,
		];
		const store = createTestStore( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );

		const permissionErrorComponent = wrapper.findComponent( ErrorPermission );
		expect( permissionErrorComponent.exists() ).toBe( true );
		expect( permissionErrorComponent.props( 'permissionErrors' ) ).toEqual( applicationErrors );
		expect( wrapper.findComponent( ErrorUnknown ).exists() ).toBe( false );
	} );

	it( 'shows only ErrorPermission even if permission errors are mixed with other application errors', () => {
		const permissionErrors: MissingPermissionsError[] = [
			{
				type: PageNotEditable.ITEM_SEMI_PROTECTED,
				info: {
					right: 'editsemiprotected',
				},
			},
			{
				type: PageNotEditable.PAGE_CASCADE_PROTECTED,
				info: {
					pages: [ 'Page' ],
				},
			},
		];
		const applicationErrors: ApplicationError[] = [
			{
				type: ErrorTypes.APPLICATION_LOGIC_ERROR,
				info: {},
			},
			...permissionErrors,
			{
				type: ErrorTypes.INVALID_ENTITY_STATE_ERROR,
			},
			{
				type: ErrorTypes.UNSUPPORTED_DATATYPE,
				info: {
					unsupportedDatatype: 'time' as DataType,
				},
			} as UnsupportedDatatypeError,
			{
				type: ErrorTypes.UNSUPPORTED_DEPRECATED_STATEMENT,
			},
			{
				type: ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT,
			},
		];
		const store = createTestStore( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );

		const permissionErrorComponent = wrapper.findComponent( ErrorPermission );
		expect( permissionErrorComponent.exists() ).toBe( true );
		expect( permissionErrorComponent.props( 'permissionErrors' ) ).toEqual( permissionErrors );
		expect( wrapper.findComponent( ErrorUnknown ).exists() ).toBe( false );
	} );

	// eslint-disable-next-line max-len
	it( 'mounts ErrorUnsupportedDatatype when an unsupported data type error is present in the application errors', () => {
		const applicationErrors: ApplicationError[] = [
			{
				type: ErrorTypes.UNSUPPORTED_DATATYPE,
				info: {
					unsupportedDatatype: 'time' as DataType,
				},
			} as UnsupportedDatatypeError,
		];
		const store = createTestStore( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );
		expect( wrapper.findComponent( ErrorUnsupportedDatatype ).exists() ).toBe( true );
	} );

	// eslint-disable-next-line max-len
	it( 'mounts ErrorDeprecatedStatement when a deprecated statement error is present in the application errors', () => {
		const applicationErrors: ApplicationError[] = [
			{
				type: ErrorTypes.UNSUPPORTED_DEPRECATED_STATEMENT,
			},
		];
		const store = createTestStore( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );

		expect( wrapper.findComponent( ErrorDeprecatedStatement ).exists() ).toBe( true );
	} );

	it( 'mounts ErrorAmbiguousStatement when an ambiguous statement error is present in the application errors', () => {
		const applicationErrors: ApplicationError[] = [
			{
				type: ErrorTypes.UNSUPPORTED_AMBIGUOUS_STATEMENT,
			},
		];
		const store = createTestStore( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );

		expect( wrapper.findComponent( ErrorAmbiguousStatement ).exists() ).toBe( true );
	} );

	it( 'mounts ErrorUnsupportedSnakType on unsupported snak type application error', () => {
		const applicationErrors: ApplicationError[] = [
			{
				type: ErrorTypes.UNSUPPORTED_SNAK_TYPE,
				info: {
					snakType: 'somevalue',
				},
			},
		];
		const store = createTestStore( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );
		expect( wrapper.findComponent( ErrorUnsupportedSnakType ).exists() ).toBe( true );
	} );

	it( 'mounts ErrorSaving on saving error', () => {
		const applicationErrors: ApplicationError[] = [
			{
				type: ErrorTypes.SAVING_FAILED,
			},
		];
		const store = createTestStore( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );
		expect( wrapper.findComponent( ErrorSaving ).exists() ).toBe( true );
	} );

	it( 'mounts ErrorSavingAssertUser on assertuser error', () => {
		const applicationErrors: ApplicationError[] = [
			{
				type: ErrorTypes.ASSERT_USER_FAILED,
			},
		];
		const store = createTestStore( {
			state: {
				applicationErrors,
			},
		} );
		const loginUrl = 'https://client.example/login';
		const $clientRouter = {
			getPageUrl: jest.fn().mockReturnValue( loginUrl ),
		};
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ], mocks: { $clientRouter } } } );
		expect( wrapper.findComponent( ErrorSavingAssertUser ).exists() ).toBe( true );
		expect( $clientRouter.getPageUrl ).toHaveBeenCalledWith(
			'Special:UserLogin',
			{
				warning: MessageKeys.LOGIN_WARNING,
			},
		);
		expect( wrapper.findComponent( ErrorSavingAssertUser ).props( 'loginUrl' ) ).toBe( loginUrl );
	} );

	it( 'repeats ErrorUnknown\'s "relaunch" event', () => {
		const store = createTestStore( {
			state: {
				applicationErrors: [
					{
						type: ErrorTypes.INVALID_ENTITY_STATE_ERROR,
					},
				],
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );
		wrapper.findComponent( ErrorUnknown ).vm.$emit( 'relaunch' );
		expect( wrapper.emitted( 'relaunch' ) ).toHaveLength( 1 );
	} );

	it( 'mounts ErrorSavingEditConflict on edit conflict error', () => {
		const applicationErrors: ApplicationError[] = [
			{
				type: ErrorTypes.EDIT_CONFLICT,
			},
		];
		const store = createTestStore( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );
		expect( wrapper.findComponent( ErrorSavingEditConflict ).exists() ).toBe( true );
	} );

	it( 'repeats ErrorSavingEditConflict\'s "reload" event', () => {
		const store = createTestStore( {
			state: {
				applicationErrors: [
					{
						type: ErrorTypes.EDIT_CONFLICT,
					},
				],
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { global: { plugins: [ store ] } } );
		wrapper.findComponent( ErrorSavingEditConflict ).vm.$emit( 'reload' );
		expect( wrapper.emitted( 'reload' ) ).toHaveLength( 1 );
	} );
} );
