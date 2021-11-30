import ErrorPermissionInfo from '@/presentation/components/ErrorPermissionInfo.vue';
import { shallowMount } from '@vue/test-utils';
import MessageKeys from '@/definitions/MessageKeys';

const TOGGLE_SELECTOR = '.wb-db-error-permission-info__toggle';
const BODY_SELECTOR = '.wb-db-error-permission-info__body';

describe( 'ErrorPermissionInfo', () => {
	it( 'matches the snapshot in closed state', () => {
		const messageHeader = 'header';
		const messageBody = 'body';
		const expandedByDefault = false;
		const wrapper = shallowMount( ErrorPermissionInfo, {
			propsData: { messageHeader, messageBody, expandedByDefault },
		} );
		expect( wrapper.element ).toMatchSnapshot();
	} );

	it( 'matches the snapshot in opened state', () => {
		const messageHeader = 'header';
		const messageBody = 'body';
		const expandedByDefault = true;
		const wrapper = shallowMount( ErrorPermissionInfo, {
			propsData: { messageHeader, messageBody, expandedByDefault },
		} );
		expect( wrapper.element ).toMatchSnapshot();
	} );

	it( 'uses correct message for toggle', () => {
		const toggleText = 'mytoggle';
		const messageGet = jest.fn().mockReturnValue( toggleText );
		const wrapper = shallowMount( ErrorPermissionInfo, {
			propsData: { messageHeader: 'header', messageBody: 'body' },
			global: {
				mocks: {
					$messages: {
						KEYS: MessageKeys,
						getText: messageGet,
					},
				},
			},
		} );
		expect( messageGet ).toHaveBeenCalledTimes( 1 );
		expect( messageGet ).toHaveBeenCalledWith( MessageKeys.PERMISSIONS_MORE_INFO );
		expect( wrapper.find( TOGGLE_SELECTOR ).text() )
			.toBe( toggleText );
	} );

	it( 'Link toggles body visibility and link class', async () => {
		const expandedByDefault = false;
		const wrapper = shallowMount( ErrorPermissionInfo, {
			propsData: { messageHeader: 'header', messageBody: 'body', expandedByDefault },
		} );

		expect( wrapper.find( BODY_SELECTOR ).exists() )
			.toBeFalsy();

		await wrapper.find( TOGGLE_SELECTOR ).trigger( 'click' );

		expect( wrapper.find( TOGGLE_SELECTOR ).classes() )
			.toContain( 'wb-db-error-permission-info__toggle--open' );
		expect( wrapper.find( BODY_SELECTOR ).exists() )
			.toBeTruthy();

		await wrapper.find( TOGGLE_SELECTOR ).trigger( 'click' );

		expect( wrapper.find( TOGGLE_SELECTOR ).classes() )
			.toContain( 'wb-db-error-permission-info__toggle--closed' );
		expect( wrapper.find( BODY_SELECTOR ).exists() )
			.toBeFalsy();
	} );

} );
