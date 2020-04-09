export enum initEvents {
	saved = 'saved',
	cancel = 'cancel',
}

export enum appEvents {
	relaunch = 'relaunch',
}

export default {
	...initEvents,
	...appEvents,
};
