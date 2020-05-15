import subscribeToEvents from '@/mediawiki/subscribeToEvents';
import { initEvents } from '@/events';

describe( 'subscribeToEvents', () => {
	describe( initEvents.saved, () => {
		const emitter = { on: jest.fn() };
		const mwWindow = { reload: jest.fn() };

		it( 'subscribes EventEmitter on saved', () => {
			subscribeToEvents( emitter as any, { on: jest.fn() } as any, mwWindow as any );
			expect( emitter.on.mock.calls[ 0 ][ 0 ] ).toBe( initEvents.saved );
		} );

		it( 'subscribes EventEmitter on cancel', () => {
			subscribeToEvents( emitter as any, { on: jest.fn() } as any, mwWindow as any );
			expect( emitter.on.mock.calls[ 1 ][ 0 ] ).toBe( initEvents.cancel );
		} );

		it( 'subscribes EventEmitter on reload', () => {
			subscribeToEvents( emitter as any, { on: jest.fn() } as any, mwWindow as any );
			expect( emitter.on.mock.calls[ 2 ][ 0 ] ).toBe( initEvents.reload );
		} );

		it( 'subcribes WindowManger on closing', () => {
			const windowManager = { on: jest.fn() };
			subscribeToEvents( { on: jest.fn() } as any, windowManager as any, mwWindow as any );
			expect( windowManager.on.mock.calls[ 0 ][ 0 ] ).toBe( 'closing' );
		} );
	} );
} );
