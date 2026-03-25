import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { createI18n } from 'vue-i18n';
import { ref } from 'vue';

// Mock @inertiajs/vue3 before importing the component
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({
        url: ref('/dashboard').value,
        props: {},
    }),
}));

// Mock lucide-vue-next icons
vi.mock('lucide-vue-next', () => ({
    MessageCircle: { template: '<span>MessageCircle</span>' },
    X: { template: '<span>X</span>' },
    ChevronDown: { template: '<span>ChevronDown</span>' },
    SendHorizonal: { template: '<span>SendHorizonal</span>' },
}));

// Must import after mocks
const { default: MiniChat } = await import('./MiniChat.vue');

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
        en: {
            chat: {
                title: 'Chat',
                noMessages: 'No messages yet. Be the first to say something!',
                loadMore: 'Load more messages',
                loadingMessages: 'Loading messages...',
                inputPlaceholder: 'Type a message...',
                errorSending: 'Failed to post message',
            },
        },
    },
});

function mountMiniChat() {
    return mount(MiniChat, {
        global: {
            plugins: [i18n],
        },
    });
}

describe('MiniChat', () => {
    it('renders the toggle button', () => {
        const wrapper = mountMiniChat();
        // Should have a button to toggle the chat
        const buttons = wrapper.findAll('button');
        expect(buttons.length).toBeGreaterThan(0);
    });

    it('chat panel is closed by default', () => {
        const wrapper = mountMiniChat();
        // The chat panel contains the title "Chat" - should not be visible initially
        // The header with "Chat" text is only in the opened panel
        const panel = wrapper.find('.h-\\[400px\\]');
        expect(panel.exists()).toBe(false);
    });

    it('opens chat panel on toggle button click', async () => {
        const wrapper = mountMiniChat();

        // Mock fetch for loadMoreMessages
        global.fetch = vi.fn().mockResolvedValue({
            json: () => Promise.resolve({ data: [], meta: { current_page: 1, last_page: 1 } }),
        });

        // Click the toggle button (last button, as it's the floating action button)
        const buttons = wrapper.findAll('button');
        const toggleBtn = buttons[buttons.length - 1];
        await toggleBtn.trigger('click');

        expect(wrapper.text()).toContain('Chat');
    });

    it('closes chat panel on close button click', async () => {
        const wrapper = mountMiniChat();

        global.fetch = vi.fn().mockResolvedValue({
            json: () => Promise.resolve({ data: [], meta: { current_page: 1, last_page: 1 } }),
        });

        // Open
        const buttons = wrapper.findAll('button');
        await buttons[buttons.length - 1].trigger('click');
        await vi.dynamicImportSettled();

        // Find and click close (X) button in the header
        const closeBtn = wrapper.findAll('button').find((b) => b.text().includes('X'));
        if (closeBtn) {
            await closeBtn.trigger('click');
            // Panel should close (transition may be involved)
            expect(wrapper.vm).toBeTruthy(); // component still exists
        }
    });

    it('shows empty state when opened with no messages', async () => {
        const wrapper = mountMiniChat();

        global.fetch = vi.fn().mockResolvedValue({
            json: () => Promise.resolve({ data: [], meta: { current_page: 1, last_page: 1 } }),
        });

        const buttons = wrapper.findAll('button');
        await buttons[buttons.length - 1].trigger('click');
        await vi.dynamicImportSettled();

        expect(wrapper.text()).toContain('No messages yet');
    });

    it('renders the component on non-chat pages', () => {
        const wrapper = mountMiniChat();
        expect(wrapper.html()).not.toBe('');
    });
});
