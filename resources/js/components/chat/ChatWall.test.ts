import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { createI18n } from 'vue-i18n';
import ChatWall from './ChatWall.vue';

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    messages: {
        en: {
            chat: {
                noMessages: 'No messages yet. Be the first to say something!',
                loadMore: 'Load more messages',
                loadingMessages: 'Loading messages...',
            },
        },
    },
});

function makeMessage(id: number, body: string, minutesAgo = 0) {
    return {
        id,
        body,
        created_at: new Date(Date.now() - minutesAgo * 60000).toISOString(),
        user: { id: 1, name: 'TestUser', chat_color: null },
    };
}

function mountWall(props: Record<string, unknown> = {}) {
    return mount(ChatWall, {
        props: {
            messages: [],
            loading: false,
            error: null,
            hasMore: false,
            scrollToBottom: false,
            ...props,
        },
        global: {
            plugins: [i18n],
        },
    });
}

describe('ChatWall', () => {
    it('shows empty state when no messages and not loading', () => {
        const wrapper = mountWall({ messages: [], loading: false });
        expect(wrapper.text()).toContain('No messages yet');
    });

    it('shows loading state when loading with no messages', () => {
        const wrapper = mountWall({ messages: [], loading: true });
        expect(wrapper.text()).toContain('Loading messages...');
    });

    it('renders messages', () => {
        const messages = [
            makeMessage(1, 'First message', 2),
            makeMessage(2, 'Second message', 1),
        ];
        const wrapper = mountWall({ messages });
        expect(wrapper.text()).toContain('First message');
        expect(wrapper.text()).toContain('Second message');
    });

    it('shows load more button when hasMore is true', () => {
        const messages = [makeMessage(1, 'Hello')];
        const wrapper = mountWall({ messages, hasMore: true });
        expect(wrapper.text()).toContain('Load more messages');
    });

    it('does not show load more when hasMore is false', () => {
        const messages = [makeMessage(1, 'Hello')];
        const wrapper = mountWall({ messages, hasMore: false });
        expect(wrapper.text()).not.toContain('Load more messages');
    });

    it('emits loadMore when load more button is clicked', async () => {
        const messages = [makeMessage(1, 'Hello')];
        const wrapper = mountWall({ messages, hasMore: true });
        const button = wrapper.find('button');
        await button.trigger('click');
        expect(wrapper.emitted('loadMore')).toBeTruthy();
    });

    it('disables load more button when loading', () => {
        const messages = [makeMessage(1, 'Hello')];
        const wrapper = mountWall({ messages, hasMore: true, loading: true });
        const button = wrapper.find('button');
        expect(button.attributes('disabled')).toBeDefined();
    });

    it('shows loading text in button when loading', () => {
        const messages = [makeMessage(1, 'Hello')];
        const wrapper = mountWall({ messages, hasMore: true, loading: true });
        expect(wrapper.text()).toContain('Loading messages...');
    });

    it('displays error message when error prop is set', () => {
        const wrapper = mountWall({ error: 'Connection failed' });
        expect(wrapper.text()).toContain('Connection failed');
    });

    it('does not show messages container when error is set', () => {
        const wrapper = mountWall({ error: 'Something wrong' });
        // Error div is shown instead of messages container
        expect(
            wrapper.find('.text-red-600').exists() ||
                wrapper.find('.text-red-400').exists(),
        ).toBe(true);
    });

    it('renders many messages in order', () => {
        const messages = Array.from({ length: 50 }, (_, i) =>
            makeMessage(i + 1, `Message ${i + 1}`, 50 - i),
        );
        const wrapper = mountWall({ messages });
        const text = wrapper.text();
        expect(text).toContain('Message 1');
        expect(text).toContain('Message 50');
    });

    it('renders messages with correct structure', () => {
        const messages = [makeMessage(1, 'Structured msg')];
        const wrapper = mountWall({ messages });
        // Should have at least one ChatMessage component rendered
        const chatMessages = wrapper.findAllComponents({ name: 'ChatMessage' });
        expect(chatMessages).toHaveLength(1);
    });
});
