import { storiesOf } from '@storybook/vue';

import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader';

storiesOf( 'ProcessDialogHeader', module )
	.add( 'without buttons', () => ( {
		components: { ProcessDialogHeader },
		template: '<ProcessDialogHeader title="ProcessDialogHeader" />',
	} ), { info: true } )
	.add( 'with only mock primary button', () => ( {
		components: { ProcessDialogHeader },
		template: `<ProcessDialogHeader title="ProcessDialogHeader">
<button style="background-color: cyan;">primary action</button>
</ProcessDialogHeader>`,
	} ), { info: true } )
	.add( 'with mock buttons', () => ( {
		components: { ProcessDialogHeader },
		template: `<ProcessDialogHeader title="ProcessDialogHeader">
<button style="background-color: cyan;">primary action</button>
<template slot="safeAction"><button>safe action</button></template>
</ProcessDialogHeader>`,
	} ), { info: true } )
	.add( 'rtl with mock buttons', () => ( {
		components: { ProcessDialogHeader },
		template: `<div dir="rtl"><ProcessDialogHeader title="גשר נתונים">
<template><button style="background-color: cyan;">פעולה ראשונית</button></template>
<template slot="safeAction"><button>פעולה בטוחה</button></template>
</ProcessDialogHeader>
</div>`,
	} ), { info: true } );
