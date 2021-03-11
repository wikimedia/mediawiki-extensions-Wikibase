import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader';

export default {
	title: 'ProcessDialogHeader',
	component: ProcessDialogHeader,
};

export function withoutButtonsOrTitle() {
	return {
		components: { ProcessDialogHeader },
		template: '<ProcessDialogHeader />',
	};
}

export function withOnlyTitle() {
	return {
		components: { ProcessDialogHeader },
		template:
			`<ProcessDialogHeader>
				<template slot="title">hello there</template>
			</ProcessDialogHeader>`,
	};
}

export function withOnlyMockPrimaryButton() {
	return {
		components: { ProcessDialogHeader },
		template:
			`<ProcessDialogHeader>
				<template slot="primaryAction"><button style="background-color: cyan;">primary action</button></template>
			</ProcessDialogHeader>`,
	};
}

export function withMockButtons() {
	return {
		components: { ProcessDialogHeader },
		template:
			`<ProcessDialogHeader>
				<template slot="primaryAction"><button style="background-color: cyan;">primary action</button></template>
				<template slot="safeAction"><button>safe action</button></template>
			</ProcessDialogHeader>`,
	};
}

export function withLongLabels() {
	return {
		components: { ProcessDialogHeader },
		template:
			`<div style="max-width: 500px;">
				<ProcessDialogHeader>
					<template slot="title">Edit located in the administrative territorial entity</template>
					<template slot="primaryAction"><button style="background-color: cyan;">Publish changes</button></template>
					<template slot="safeAction"><button>X</button></template>
				</ProcessDialogHeader>
			</div>`,
	};
}

export function rtlWithMockButtons() {
	return {
		components: { ProcessDialogHeader },
		template:
			`<div dir="rtl">
				<ProcessDialogHeader>
					<template slot="title">גשר נתונים</template>
					<template slot="primaryAction"><button style="background-color: cyan;">פעולה ראשונית</button></template>
					<template slot="safeAction"><button>פעולה בטוחה</button></template>
				</ProcessDialogHeader>
			</div>`,
	};
}
