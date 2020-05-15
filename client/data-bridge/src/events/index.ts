export enum initEvents {
	saved = 'saved',
	cancel = 'cancel',
	reload = 'reload',
}

export enum appEvents {
	relaunch = 'relaunch',
}

export default {
	...initEvents,
	...appEvents,
};
