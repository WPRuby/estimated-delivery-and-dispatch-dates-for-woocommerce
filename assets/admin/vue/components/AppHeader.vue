<template>
  <header class="dpl-header wpruby-dp-header">
    <div class="dpl-header__brand wpruby-dp-header__brand">
      <div class="dpl-header__titlebar wpruby-dp-header__titlebar">
        <span class="dpl-icon-tile wpruby-dp-header__logo" aria-hidden="true">
          <Icon name="truck" />
        </span>
        <h1 class="wpruby-dp-header__title">{{ title }}</h1>
        <span v-if="version" class="wpruby-dp-header__version">v{{ version }}</span>
      </div>
      <p class="wpruby-dp-header__desc">{{ description }}</p>

      <div v-if="ready" class="wpruby-dp-header__chips">
        <span class="wpruby-dp-chip dpl-chip" :class="enabled ? 'wpruby-dp-chip--on' : 'wpruby-dp-chip--off'">
          <span class="wpruby-dp-chip__dot" aria-hidden="true"></span>
          {{ enabled ? enabledLabel : disabledLabel }}
        </span>
        <span class="wpruby-dp-chip dpl-chip" :class="productPage ? 'wpruby-dp-chip--on' : 'wpruby-dp-chip--off'">
          <Icon name="eye" />
          {{ productPageLabel }}
        </span>
        <span class="wpruby-dp-chip dpl-chip">
          <Icon name="calendar" />
          {{ workingDaysLabel }}
        </span>
      </div>
    </div>

    <div class="wpruby-dp-header__actions">
      <span
        class="wpruby-dp-status"
        :class="saving ? 'wpruby-dp-status--dirty' : dirty ? 'wpruby-dp-status--dirty' : 'wpruby-dp-status--saved'"
      >
        <span class="wpruby-dp-status__dot" aria-hidden="true"></span>
        {{ saving ? savingLabel : dirty ? unsavedLabel : savedLabel }}
      </span>
      <button
        type="button"
        class="dpl-button wpruby-dp-btn wpruby-dp-header__save"
        :class="dirty && !saving ? 'dpl-button--primary wpruby-dp-btn--primary' : 'dpl-button--disabled'"
        :disabled="saving || !dirty"
        @click="$emit('save')"
      >
        <span v-if="saving" class="dpl-button__spinner" aria-hidden="true"></span>
        {{ saving ? savingLabel : saveLabel }}
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import Icon from './Icon.vue';
import { state } from '../store.js';
import { __ } from '../api/client.js';

defineProps({
  dirty: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
});
defineEmits(['save']);

const boot = window.edddAdmin || {};
const version = boot.version || '';

const title = __('WPRuby Delivery Estimates for WooCommerce');
const description = __('Show simple delivery and dispatch estimates on WooCommerce product pages.');
const saveLabel = __('Save changes');
const savingLabel = __('Saving…');
const unsavedLabel = __('Unsaved changes');
const savedLabel = __('All changes saved');
const enabledLabel = __('Enabled');
const disabledLabel = __('Disabled');
const productPageLabel = __('Product page estimate');

const ready = computed(() => state.ready && !!state.settings);
const enabled = computed(() => !!(state.settings && state.settings.enabled));
const productPage = computed(() => !!(state.settings && state.settings.display_product));

const workingDaysLabel = computed(() => {
  const days = (state.settings && state.settings.working_days) || [];
  const weekdays = (state.data.weekdays || []).reduce((acc, d) => {
    acc[d.value] = d.label;
    return acc;
  }, {});

  if (!days.length) {
    return __('Business days: Mon–Fri');
  }

  const labels = days.map((d) => weekdays[d] || d).filter(Boolean);
  if (labels.length === 5 && days.join(',') === '1,2,3,4,5') {
    return __('Business days: Mon–Fri');
  }

  return `${__('Business days')}: ${labels.join(', ')}`;
});
</script>
