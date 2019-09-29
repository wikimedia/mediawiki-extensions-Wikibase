import Vue from 'vue';
import repeater from '@/events/repeater';

describe( 'repeater', () => {
	it( 'subscribes and listens events of a given Vue instance', () => {
		const events = [ 'test' ];
		const mockApp = {
			$on: jest.fn(),
		};

		repeater( mockApp as any, jest.fn() as any, events );
		expect( mockApp.$on.mock.calls[ 0 ][ 0 ] ).toBe( 'test' );
	} );

	it( 'emits the listened subscribed event', () => {
		const emitter = {
			emit: jest.fn(),
		};
		const events = [ 'test' ];
		const anyPayload = 'payload';
		const mockApp = new Vue( {} );

		repeater( mockApp, emitter as any, events );
		mockApp.$emit( 'test', anyPayload );
		expect( emitter.emit ).toHaveBeenCalledWith( 'test', anyPayload );
	} );
} );
