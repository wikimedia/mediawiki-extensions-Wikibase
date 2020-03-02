import { storiesOf } from '@storybook/vue';

import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader';

storiesOf( 'ProcessDialogHeader', module )
	.addParameters( { component: ProcessDialogHeader } )
	.add( 'without buttons or title', () => ( {
		components: { ProcessDialogHeader },
		template: '<ProcessDialogHeader />',
	} ) )
	.add( 'with only title', () => ( {
		components: { ProcessDialogHeader },
		template:
			`<ProcessDialogHeader>
				<template slot="title">hello there</template>
			</ProcessDialogHeader>`,
	} ) )
	.add( 'with only mock primary button', () => ( {
		components: { ProcessDialogHeader },
		template:
			`<ProcessDialogHeader>
				<template slot="primaryAction"><button style="background-color: cyan;">primary action</button></template>
			</ProcessDialogHeader>`,
	} ) )
	.add( 'with mock buttons', () => ( {
		components: { ProcessDialogHeader },
		template:
			`<ProcessDialogHeader>
				<template slot="primaryAction"><button style="background-color: cyan;">primary action</button></template>
				<template slot="safeAction"><button>safe action</button></template>
			</ProcessDialogHeader>`,
	} ) )
	.add( 'with long labels', () => ( {
		components: { ProcessDialogHeader },
		template:
			`<div style="max-width: 500px;">
				<ProcessDialogHeader>
					<template slot="title">Edit located in the administrative territorial entity</template>
					<template slot="primaryAction"><button style="background-color: cyan;">Publish changes</button></template>
					<template slot="safeAction"><button>X</button></template>
				</ProcessDialogHeader>
			</div>`,
	} ) )
	.add( 'rtl with mock buttons', () => ( {
		components: { ProcessDialogHeader },
		template:
			`<div dir="rtl">
				<ProcessDialogHeader>
					<template slot="title">גשר נתונים</template>
					<template slot="primaryAction"><button style="background-color: cyan;">פעולה ראשונית</button></template>
					<template slot="safeAction"><button>פעולה בטוחה</button></template>
				</ProcessDialogHeader>
			</div>`,
	} ) );
