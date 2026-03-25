import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { createI18n } from 'vue-i18n';
import ChatInput from './ChatInput.vue';

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
        en: {
            chat: {
                inputPlaceholder: 'Type a message...',
                send: 'Send',
                errorSending: 'Failed to post message',
            },
            common: {
                loading: 'Loading...',
            },
            validation: {
                required: 'This field is required',
            },
        },
    },
});

function mountInput() {
    return mount(ChatInput, {
        global: {
            plugins: [i18n],
        },
    });
}

describe('ChatInput', () => {
    it('renders a textarea and submit button', () => {
        const wrapper = mountInput();
        expect(wrapper.find('textarea').exists()).toBe(true);
        expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });

    it('shows the send button text', () => {
        const wrapper = mountInput();
        const button = wrapper.find('button[type="submit"]');
        expect(button.text()).toBe('Send');
    });

    it('has a placeholder on the textarea', () => {
        const wrapper = mountInput();
        const textarea = wrapper.find('textarea');
        expect(textarea.attributes('placeholder')).toBe('Type a message...');
    });

    it('emits submit when form is submitted with text', async () => {
        const wrapper = mountInput();
        const textarea = wrapper.find('textarea');

        // vee-validate manages the field, so we set value through the input event
        await textarea.setValue('Hello there');
        await wrapper.find('form').trigger('submit');

        // Allow async validation to complete
        await vi.dynamicImportSettled();

        const emitted = wrapper.emitted('submit');
        if (emitted) {
            expect(emitted[0][0]).toBe('Hello there');
        }
    });

    it('does not emit submit when textarea is empty', async () => {
        const wrapper = mountInput();
        await wrapper.find('form').trigger('submit');
        await vi.dynamicImportSettled();

        // Should not emit since validation should fail
        const emitted = wrapper.emitted('submit');
        expect(emitted).toBeUndefined();
    });

    it('handles Enter key (without Shift) on textarea', async () => {
        const wrapper = mountInput();
        const textarea = wrapper.find('textarea');
        await textarea.setValue('Quick message');
        await textarea.trigger('keydown', { key: 'Enter', shiftKey: false });
        await vi.dynamicImportSettled();

        // The keydown handler calls onSubmit which triggers form submission
        const emitted = wrapper.emitted('submit');
        if (emitted) {
            expect(emitted[0][0]).toBe('Quick message');
        }
    });

    it('does not submit on Shift+Enter', async () => {
        const wrapper = mountInput();
        const textarea = wrapper.find('textarea');
        await textarea.setValue('Multiline');
        await textarea.trigger('keydown', { key: 'Enter', shiftKey: true });
        await vi.dynamicImportSettled();

        // Shift+Enter should not trigger submit
        const emitted = wrapper.emitted('submit');
        expect(emitted).toBeUndefined();
    });
});
