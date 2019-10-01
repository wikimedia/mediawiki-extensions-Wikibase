import {
	OOUIWindow,
	WindowManager,
} from '@/@types/mediawiki/MwWindow';
import Events from '@/events';
import { EventEmitter } from 'events';

export default function subscribeToEvents( emitter: EventEmitter, windowManager: WindowManager ): void {
	emitter.on( Events.onSaved, () => {
		windowManager.clearWindows().catch( () => { /* do nothing */ } );
	} );
	emitter.on( Events.onCancel, () => {
		windowManager.clearWindows().catch( () => { /* do nothing */ } );
	} );

	windowManager.on( 'closing', ( _win: OOUIWindow, compatClosing: JQuery.Promise<unknown> ) => {
		compatClosing.then( () => {
			windowManager.destroy();
		}, ( _e ) => {
			windowManager.destroy();
		} );
	} );
}
