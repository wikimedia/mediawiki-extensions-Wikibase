import { EventEmitter } from 'events';
import Vue from 'vue';

export default function repeater(
	app: Vue,
	emitter: EventEmitter,
	eventNames: string[],
): void {
	eventNames.forEach( ( value: string ) => {
		app.$on( value, ( ...payload: unknown[] ) => {
			emitter.emit( value, ...payload );
		} );
	} );
}
