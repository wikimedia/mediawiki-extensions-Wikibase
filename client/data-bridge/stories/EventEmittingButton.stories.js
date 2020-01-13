import { storiesOf } from '@storybook/vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
storiesOf( 'EventEmittingButton', module )
	.addParameters( { component: EventEmittingButton } )
	.add( 'primaryProgressive', () => ( {
		components: { EventEmittingButton },
		template: '<EventEmittingButton type="primaryProgressive" message="primaryProgressive" />',
	} ) )
	.add( 'primaryProgressive as link', () => ( {
		components: { EventEmittingButton },
		template: `<EventEmittingButton
			type="primaryProgressive"
			message="primaryProgressive"
			href="https://www.mediawiki.org/wiki/Wikidata_Bridge"
			:preventDefault="false"
		/>`,
	} ) )
	.add( 'squary primaryProgressive', () => ( {
		components: { EventEmittingButton },
		template: `<EventEmittingButton
			type="primaryProgressive"
			:squary="true"
			message="squary primaryProgressive"
		/>`,
	} ) )
	.add( 'cancel', () => ( {
		components: { EventEmittingButton },
		template: `<EventEmittingButton
			type="cancel"
			message="cancel"
		/>`,
	} ) )
	.add( 'cancel squary', () => ( {
		components: { EventEmittingButton },
		template: `<EventEmittingButton
			type="cancel"
			:squary="true"
			message="cancel"
		/>`,
	} ) )
	.add( 'primaryProgressive disabled', () => ( {
		components: { EventEmittingButton },
		template: `<EventEmittingButton
			type="primaryProgressive"
			message="disabled primaryProgressive"
			:disabled="true"
		/>`,
	} ) );
