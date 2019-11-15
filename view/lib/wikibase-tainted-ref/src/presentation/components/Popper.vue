<template>
	<div class="wb-tr-popper-wrapper">
		<div class="wb-tr-popper-triangle" />
		<div class="wb-tr-popper-body">
			<div class="wb-tr-title-wrapper">
				<span class="wb-tr-popper-title">Potential Reference/Value Mismatch</span>
				<a class="wb-tr-popper-close" @click="closeClick">x</a>
			</div>
			<h4>
				Tainted Reference Heading text
				<small>
					<a
						class="wb-tr-popper-help"
						title="Help page for this constraint type"
						:href="helpLink"
						target="_blank"
						@click="helpClick"
					>Help</a>
				</small>
			</h4>
			<p class="wb-tr-popper-text">
				The value of "point in time" was changed, but the reference remained the same.
			</p>
		</div>
	</div>
</template>

<script lang="ts">
import { POPPER_HIDE } from '@/store/actionTypes';
import Component from 'vue-class-component';
import Vue from 'vue';
import { Getter } from 'vuex-class';

@Component
export default class Popper extends Vue {
	@Getter( 'helpLink' )
	public helpLink!: string;

	public closeClick( event: MouseEvent ) {
		event.preventDefault();
		this.$store.dispatch( POPPER_HIDE, this.$parent.$data.id );
	}

	public helpClick() {
		this.$track( 'counter.wikibase.view.tainted-ref.helpLinkClick', 1 );
	}
}
</script>

<style lang="scss">
.wb-tr-popper-wrapper {
	z-index: 1;
	position: relative;
}

.wb-tr-popper-triangle {
	width: 27px;
	position: absolute;
	top: -14px;
	left: calc( 50% - 29px / 2 );
	overflow: hidden;
	border-bottom: 0 transparent;
	border-right: 1px solid transparent;
	border-left: 1px solid transparent;
	z-index: 3;
	height: 15px;
}

.wb-tr-popper-triangle:before {
	content: '';
	position: absolute;
	top: -5px;
	left: 0;
	width: 50px;
	height: 18px;
	-webkit-transform-origin: 0 100%;
	-ms-transform-origin: 0 100%;
	transform-origin: 0 100%;
	-webkit-transform: rotate( 45deg );
	-ms-transform: rotate( 45deg );
	transform: rotate( 45deg );
	border-top: 1px solid #a2a9b1;
	border-left: 1px solid #a2a9b1;
	background: #fff;
}

.wb-tr-popper-body {
	padding: 7px 7px 7px 7px;
	width: 415px;
	height: 150px;
	border: #a2a9b1 1px solid;
	overflow: hidden;
	z-index: 2;
	position: relative;
	background-color: #ffff;
}

.wb-tr-popper-title {
	font-family: sans-serif;
	font-size: 16px;
	font-weight: bold;
}

.wb-tr-popper-text {
	font-family: sans-serif;
	font-size: 16px;
}

.wb-tr-title-wrapper {
	display: flex;
	justify-content: space-between;
	border-bottom: 1px #eaecf0 solid;
}

.wb-tr-popper-close {
	margin-top: -4px;
	font-size: 20px;
	width: 5%;
	height: 5%;
}

.wb-tr-popper-help {
	font-weight: normal;
	float: right;
	margin-left: 1.5em;
}
</style>
