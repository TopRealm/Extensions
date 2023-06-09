<template>
	<div class="sd-input" :class="rootClasses">
		<div class="sd-input__wrapper">
			<label
				:id="labelElementId"
				:for="inputElementId"
				class="sd-input__label"
			>
				{{ label }}
			</label>

			<input
				:id="inputElementId"
				ref="input"
				v-model="value"
				dir="auto"
				class="sd-input__input"
				type="text"
				role="combobox"
				aria-autocomplete="list"
				:aria-owns="lookupResultsElementId"
				:aria-expanded="isExpanded"
				:aria-activedescendant="activeLookupItemId || null"
				:placeholder="placeholder"
				@input="onInput"
				@focus="onFocus"
				@blur="onBlur"
				@keyup.enter="onSubmit"
				@keyup.up="onArrowUp"
				@keyup.down="onArrowDown"
			>

			<span class="sd-input__icon" @click="onIconClick">
				<sd-icon :icon="icons.sdIconSearch"></sd-icon>
			</span>

			<span
				v-if="value"
				class="sd-input__indicator"
				role="button"
				@click="onClear"
			>
				<sd-icon :icon="icons.sdIconClear" :title="clearTitle"></sd-icon>
			</span>

			<sd-select-menu
				v-if="hasLookupResults && showLookupResults"
				:items="lookupResults"
				:active-item-index="activeLookupItemIndex"
				:listbox-id="lookupResultsElementId"
				:labelled-by="labelElementId"
				@select="onLookupItemSelect"
				@active-item-change="onActiveItemChange"
			>
			</sd-select-menu>
		</div>

		<sd-button
			v-if="hasButton"
			class="sd-input__button"
			:primary="true"
			:progressive="true"
			@click="onSubmit"
		>
			{{ $i18n( 'searchbutton' ).text() }}
		</sd-button>
	</div>
</template>

<script>
var SdButton = require( './Button.vue' ),
	SdIcon = require( './Icon.vue' ),
	SdSelectMenu = require( './SelectMenu.vue' ),
	icons = require( '../../../lib/icons.js' );

/**
 * @file AutocompleteSearchInput
 *
 * Search input that emits user-provided input to the parent, then receives and
 * displays autocomplete results. This component is fairly specific to the Media
 * Search use case: we know we'll be fetching autocomplete results, we'll always
 * have a search icon and clear indicator button, etc. This could be made more
 * general for wider usage.
 */
// @vue/component
module.exports = exports = {
	name: 'SdAutocompleteSearchInput',

	compatConfig: {
		ATTR_FALSE_VALUE: true
	},

	components: {
		'sd-button': SdButton,
		'sd-icon': SdIcon,
		'sd-select-menu': SdSelectMenu
	},

	props: {
		/**
		 * Name must be provided to ensure unique aria attributes. This probably
		 * isn't the best way to do it in WVUI but serves our purposes here.
		 */
		name: {
			type: String,
			required: true
		},

		/**
		 * Required label for input. Currently, label will always be visually
		 * hidden, but this could be toggled via a prop in a future iteration.
		 */
		label: {
			type: [ String, Object ],
			required: true
		},

		initialValue: {
			type: [ String, Number ],
			default: ''
		},

		placeholder: {
			type: [ String, Object ],
			default: null
		},

		clearTitle: {
			type: [ String, Object ],
			default: null
		},

		buttonLabel: {
			type: [ String, Object ],
			default: null
		},

		lookupResults: {
			type: Array,
			default: function () {
				return [ ];
			}
		},

		/**
		 * This is an optional way to conditonally disable the animation shown
		 * in the input while lookup results are fetched.
		 *
		 * For example, in the parent component, you can check to see if your
		 * lookup URI exists. If not, you can pass true for this prop.
		 */
		lookupDisabled: {
			type: Boolean
		}
	},

	emits: [
		'input',
		'focus',
		'blur',
		'submit',
		'clear',
		'clear-lookup-results'
	],

	data: function () {
		return {
			value: this.initialValue,
			icons: icons,
			pending: false,
			showLookupResults: false,
			activeLookupItemIndex: -1
		};
	},

	computed: {
		/**
		 * @return {Object}
		 */
		rootClasses: function () {
			return {
				'sd-input--button': this.hasButton,
				'sd-input--pending': this.pending
			};
		},

		/**
		 * @return {boolean}
		 */
		hasButton: function () {
			return !!this.buttonLabel;
		},

		/**
		 * @return {boolean}
		 */
		hasLookupResults: function () {
			return this.lookupResults.length > 0;
		},

		/**
		 * ID of the visually-hidden label.
		 *
		 * @return {string}
		 */
		labelElementId: function () {
			return this.name + '__label';
		},

		/**
		 * ID of the input.
		 *
		 * @return {string}
		 */
		inputElementId: function () {
			return this.name + '__input';
		},

		/**
		 * ID of the lookup results container.
		 *
		 * @return {string}
		 */
		lookupResultsElementId: function () {
			return this.name + '__lookup-results';
		},

		/**
		 * The actual string of the active lookup result item.
		 *
		 * @return {string}
		 */
		activeLookupItem: function () {
			if (
				this.lookupResults.length < 1 ||
				!this.showLookupResults ||
				this.activeLookupItemIndex < 0
			) {
				return false;
			}

			return this.lookupResults[ this.activeLookupItemIndex ];
		},

		/**
		 * The ID of the element of the active lookup result item.
		 *
		 * @return {string|boolean}
		 */
		activeLookupItemId: function () {
			return this.activeLookupItemIndex > -1 ?
				this.lookupResultsElementId + '-item-' + this.activeLookupItemIndex :
				false;
		},

		/**
		 * For the aria-expanded attribute of the input, we need to use strings
		 * instead of booleans so that aria-expanded will be set to "false" when
		 * appropriate rather than the attribute being omitted, which is what
		 * would happen if we used a boolean false.
		 *
		 * @return {string}
		 */
		isExpanded: function () {
			return this.hasLookupResults && this.showLookupResults ? 'true' : 'false';
		}
	},

	methods: {
		/**
		 * Emit input and enable pending state.
		 *
		 * @fires input
		 */
		onInput: function () {
			this.pending = !this.lookupDisabled;
			this.$emit( 'input', this.value );
		},

		/**
		 * If there are existing lookup results, show them on focus.
		 *
		 * @param {Event} event
		 * @fires focus
		 */
		onFocus: function ( event ) {
			this.toggleLookupResults( this.lookupResults.length > 0 );
			this.$emit( 'focus', event );
		},

		/**
		 * Hide, but don't delete, lookup results.
		 *
		 * @param {Event} event
		 * @fires blur
		 */
		onBlur: function ( event ) {
			this.$emit( 'blur', event );
			this.toggleLookupResults( false );
		},

		/**
		 * Handle enter keypress or button click.
		 *
		 * @fires enter
		 */
		onSubmit: function () {
			// If the user is highlighting an autocomplete result, emit that
			// result. Otherwise, emit the value of the text input.
			if ( this.hasLookupResults && this.activeLookupItemIndex >= 0 ) {
				// We also want to update the input text.
				this.value = this.activeLookupItem;
			}

			this.$emit( 'submit', this.value );
			this.clearLookupResults();
			this.$refs.input.blur();
		},

		/**
		 * Handle lookup item click.
		 *
		 * @param {number} index
		 * @fires submit
		 */
		onLookupItemSelect: function ( index ) {
			this.value = this.lookupResults[ index ];
			this.$emit( 'submit', this.value );
			this.clearLookupResults();
		},

		/**
		 * Move to the next lookup result. If we're at the end, go back to the
		 * first item.
		 */
		onArrowDown: function () {
			var index = this.activeLookupItemIndex;
			if ( this.hasLookupResults ) {
				this.activeLookupItemIndex =
					this.lookupResults.length > index + 1 ? index + 1 : 0;
			}
		},

		/**
		 * Move to the previous lookup result. If we're at the beginning, go to
		 * the last item.
		 */
		onArrowUp: function () {
			var index = this.activeLookupItemIndex;
			if ( this.hasLookupResults && index > -1 ) {
				this.activeLookupItemIndex =
					index === 0 ? this.lookupResults.length - 1 : index - 1;
			}
		},

		/**
		 * Change the active item index based on mouseover or mouseleave.
		 *
		 * @param {number} index
		 */
		onActiveItemChange: function ( index ) {
			this.activeLookupItemIndex = index;
		},

		/*
		* Set focus to input if icon is clicked.
		*/
		onIconClick: function () {
			var $input;

			this.$nextTick( function () {
				$input = this.$refs.input;
				$input.focus();
			} );
		},

		/**
		 * Handle clear icon click.
		 */
		onClear: function () {
			this.$emit( 'clear' );
			this.value = '';
			this.clearLookupResults();
			this.$refs.input.focus();
		},

		/**
		 * Helper function to reset lookup results to an empty array.
		 */
		clearLookupResults: function () {
			this.$emit( 'clear-lookup-results' );
		},

		/**
		 * Show or hide lookup results.
		 *
		 * @param {boolean} show
		 */
		toggleLookupResults: function ( show ) {
			this.showLookupResults = show;
		}
	},

	watch: {
		/**
		 * When new lookup results are received, remove pending state and reset
		 * the active item index.
		 */
		lookupResults: {
			handler: function () {
				this.pending = false;
				this.activeLookupItemIndex = -1;
				this.toggleLookupResults( this.lookupResults.length > 0 );
			},
			deep: true
		},

		/**
		 * If the search term (passed down here as the "initial value" prop)
		 * changes for a reason besides the user typing into the input here
		 * (say, due to navigating forward/backward through history), make
		 * sure to update the value here to reflect the new term
		 *
		 * @param {string} newValue
		 */
		initialValue: function ( newValue ) {
			this.value = newValue;
		},

		/**
		 * Clear lookup results if the user manually delets all characters
		 *
		 * @param {string} newValue
		 */
		value: function ( newValue ) {
			if ( newValue === '' ) {
				this.clearLookupResults();
			}
		}
	}
};
</script>
