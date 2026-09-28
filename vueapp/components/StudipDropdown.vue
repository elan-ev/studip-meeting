<script setup>
import {nextTick, onBeforeUnmount, ref, useTemplateRef, watch} from "vue";
import {useGettext} from 'vue3-gettext';
import { createPopper } from '@popperjs/core';
import useDetectOutsideClick from "../composables/useDetectOutsideClick";
import StudipIcon from "@studip/StudipIcon.vue";

const {$gettext} = useGettext();

defineProps({
    title: {
        type: String
    },
    withCloseButton: {
        type: Boolean,
        default: true
    }
});

const isOpen = defineModel({ default: false });
const dropdown = useTemplateRef('dropdown');
const trigger = useTemplateRef('trigger');
const dropdownContent = useTemplateRef('dropdownContent');
const popperInstance = ref(null);

useDetectOutsideClick(dropdown, () => isOpen.value = false);

watch(isOpen, async open => {
    if (open) {
        await nextTick();

        popperInstance.value = createPopper(trigger.value, dropdownContent.value, {
            placement: 'bottom-end',
            modifiers: [
                {
                    name: 'offset',
                    options: {
                        offset: [0, 6]
                    }
                },
                {
                    name: 'preventOverflow',
                    options: {
                        padding: 10
                    }
                }
            ]
        });
    }
});

onBeforeUnmount(() => {
    if (popperInstance.value) {
        popperInstance.value.destroy();
        popperInstance.value = null;
    }
});
</script>

<template>
    <div
        v-bind="$attrs"
        ref="dropdown"
        class="dropdown"
        aria-haspopup="menu"
        :aria-expanded="isOpen"
    >
        <div ref="trigger">
            <slot name="trigger">
            </slot>
        </div>

        <Transition name="fade-down">
            <div
                v-if="isOpen"
                ref="dropdownContent"
                class="dropdown__content"
                :aria-labelledby="title ? 'dropdown-title' : null"
            >
                <button
                    type="button"
                    v-if="withCloseButton"
                    @click="isOpen = false"
                    :title="$gettext('Menü schließen')"
                    class="dropdown__close-button button-base">
                    <StudipIcon shape="decline" :size="20" />
                </button>
                <div v-if="title" class="dropdown__header">
                    <p id="dropdown-title" class="dropdown__title">
                        {{ title }}
                    </p>
                </div>
                <ul class="dropdown__items" role="menu">
                    <slot name="items">
                    </slot>
                </ul>
                <slot name="content">
                </slot>
            </div>
        </Transition>
    </div>
</template>
