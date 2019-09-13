import { storiesOf } from '@storybook/vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
storiesOf( 'EventEmittingButton', module )
	.add( 'primaryProgressive', () => ( {
		components: { EventEmittingButton },
		template: '<EventEmittingButton type="primaryProgressive" message="primaryProgressive" />',
	} ), { info: true } )
	.add( 'squary primaryProgressive', () => ( {
		components: { EventEmittingButton },
		template: `<EventEmittingButton
			type="primaryProgressive"
			:squary="true"
			message="squary primaryProgressive"
		/>`,
	} ), { info: true } );
