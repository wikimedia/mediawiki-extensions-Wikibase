import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import { createLocalVue, shallowMount } from '@vue/test-utils';
import Vuex from 'vuex';
import {
	MissingPermissionsError,
	PageNotEditable,
	ProtectedReason,
} from '@/definitions/data-access/BridgePermissionsRepository';
import ErrorPermission from '@/presentation/components/ErrorPermission.vue';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';
import ErrorUnsupportedDatatype from '@/presentation/components/ErrorUnsupportedDatatype.vue';
import ApplicationError, { ErrorTypes, UnsupportedDatatypeError } from '@/definitions/ApplicationError';
import { createTestStore } from '../../../util/store';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'ErrorWrapper', () => {
	it( 'mounts ErrorUnknown on empty applicationErrors', () => {
		const store = createTestStore( {
			state: {
				applicationErrors: [],
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { localVue, store } );
		expect( wrapper.find( ErrorUnknown ).exists() ).toBeTruthy();
		expect( wrapper.find( ErrorPermission ).exists() ).toBeFalsy();
	} );

	it( 'mounts ErrorUnknown for exclusively non-permission-related or data type related errors', () => {
		const store = createTestStore( {
			state: {
				applicationErrors: [
					{
						type: ErrorTypes.INVALID_ENTITY_STATE_ERROR,
					},
				],
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { localVue, store } );
		expect( wrapper.find( ErrorUnknown ).exists() ).toBeTruthy();
		expect( wrapper.find( ErrorPermission ).exists() ).toBeFalsy();
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
		const wrapper = shallowMount( ErrorWrapper, {
			localVue,
			store,
		} );

		const permissionErrorComponent = wrapper.find( ErrorPermission );
		expect( permissionErrorComponent.exists() ).toBeTruthy();
		expect( permissionErrorComponent.props( 'permissionErrors' ) ).toEqual( applicationErrors );
		expect( wrapper.find( ErrorUnknown ).exists() ).toBeFalsy();
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
					unsupportedDatatype: 'time',
				},
			} as UnsupportedDatatypeError,
		];
		const store = createTestStore( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, {
			localVue,
			store,
		} );

		const permissionErrorComponent = wrapper.find( ErrorPermission );
		expect( permissionErrorComponent.exists() ).toBeTruthy();
		expect( permissionErrorComponent.props( 'permissionErrors' ) ).toEqual( permissionErrors );
		expect( wrapper.find( ErrorUnknown ).exists() ).toBeFalsy();
	} );
	// eslint-disable-next-line max-len
	it( 'mounts ErrorUnsupportedDatatype when an unsupported data type error is present in the application errors', () => {
		const applicationErrors: ApplicationError[] = [
			{
				type: ErrorTypes.UNSUPPORTED_DATATYPE,
				info: {
					unsupportedDatatype: 'time',
				},
			} as UnsupportedDatatypeError,
		];
		const store = createTestStore( {
			state: {
				applicationErrors,
			},
		} );
		const wrapper = shallowMount( ErrorWrapper, { localVue, store } );
		expect( wrapper.find( ErrorUnsupportedDatatype ).exists() ).toBeTruthy();
	} );
} );
