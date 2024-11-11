'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { markdownToHtml } from './markdown';
import { EventSourceParserStream } from 'eventsource-parser/stream';

/**
 * @copypright https://bennadel.com/4602
 */
Alpine.directive("template-outlet", (element, metadata, framework) => {
    Alpine.nextTick(() => {
        // Get the template reference that we want to clone and render.
        var templateRef = framework.evaluate(metadata.expression);

        if (!templateRef) {
            return;
        }

        // Clone the template and get the root node - this is the node that we will
        // inject into the DOM.
        var clone = templateRef.content
            .cloneNode(true)
            .firstElementChild;

        // CAUTION: The following logic ASSUMES that the template-outlet directive has
        // an "x-data" scope binding on it. If it didn't we would have to change the
        // logic. But, I don't think Alpine.js has mechanics to solve this use-case
        // quite yet.
        Alpine.addScopeToNode(
            clone,
            // Use the "x-data" scope from the template-outlet element as a means to
            // supply initializing data to the clone (for constructor injection).
            Alpine.closestDataStack(element)[0],
            // use the template-outlet element's parent to define the rest of the
            // scope chain.
            element.parentElement
        );

        // Swap the template-outlet element with the hook and clone.
        // --
        // NOTE: Doing this inside the mutateDom() method will pause Alpine's internal
        // MutationObserver, which allows us to perform DOM manipulation without
        // triggering actions in the framework. Then, we can call initTree() and
        // destroyTree() to have explicitly setup and teardowm DOM node bindings.
        Alpine.mutateDom(
            function pauseMutationObserver() {
                element.after(clone);
                Alpine.initTree(clone);
                element.remove();
                Alpine.destroyTree(element);
            }
        );
    });
});

export function chat() {
    Alpine.data('chat', (
        model,
        adapters = [],
        assistant = null,
        conversation = null
    ) => ({
        adapters: [],
        adapter: null,

        conversation: null,
        assistant: assistant,
        history: null,
        assistants: null,
        file: null,
        prompt: null,
        isProcessing: false,
        parent: null,
        tree: null,
        quote: null,
        autoScroll: false,
        isDeleting: false,
        query: '',
        promptUpdated: false,

        init() {
            adapters.forEach(adapter => {
                if (adapter.is_available) {
                    adapter.models.forEach(model => {
                        if (model.is_available) {
                            this.adapters.push(model);
                        }
                    });
                }
            });

            this.adapter = this.adapters.find(adapter => adapter.model == model);

            if (!this.adapter && this.adapters.length > 0) {
                this.adapter = this.adapters[0];
            }

            if (conversation) {
                this.select(conversation);

                setTimeout(() => window.scroll({
                    behavior: 'smooth',
                    top: document.body.scrollHeight
                }), 500);
            }

            this.fetchHistory();
            this.getAssistants();

            window.addEventListener('scroll', () => {
                this.autoScroll = window.scrollY + window.innerHeight + 500 >= document.documentElement.scrollHeight;
            });

            window.addEventListener('mouseup', (e) => {
                this.$refs.quote.classList.add('hidden');
                this.$refs.quote.classList.remove('flex');
            });

            this.$watch('prompt', () => this.promptUpdated = true);
        },

        format(message) {
            return message ? markdownToHtml(message.content) : '';
        },

        generateMap(msgId = null) {
            const map = new Map();

            this.conversation.messages.forEach(message => {
                map.set(message.id, message);
            });

            let tree = {
                index: 0,
                children: this.buildTree(null, map)
            };

            if (msgId) {
                this.updateIndicesToMessage(tree, msgId);
            }

            this.tree = tree;
        },

        updateIndicesToMessage(node, messageId) {
            if (node.id === messageId) {
                return true; // Found the node, no need to update further
            }

            if (node.children && node.children.length > 0) {
                for (let i = 0; i < node.children.length; i++) {
                    if (this.updateIndicesToMessage(node.children[i], messageId)) {
                        node.index = i; // Update the parent's index to point to this child
                        return true; // Propagate the update up the tree
                    }
                }
            }
            return false; // Node not found in this subtree
        },

        buildTree(pid, map) {
            let tree = [];

            for (let [id, message] of map) {
                if (message.parent_id === pid) {
                    tree.push(message);

                    message.index = 0;
                    message.children = this.buildTree(id, map);
                }
            }

            return tree;
        },

        fetchHistory() {
            api.get('/library/conversations', { limit: 5 })
                .then(response => response.json())
                .then(list => {
                    let data = list.data;
                    this.history = data.reverse();
                });
        },

        getAssistants(cursor = null) {
            let params = {
                limit: 250
            };

            if (cursor) {
                params.starting_after = cursor;
            }

            api.get('/assistants', params)
                .then(response => response.json())
                .then(list => {
                    if (!this.assistants) {
                        this.assistants = [];
                    }

                    this.assistants.push(...list.data);

                    if (list.data.length > 0 && list.data.length == params.limit) {
                        this.getAssistants(this.assistants[this.assistants.length - 1].id);
                    }
                });
        },

        async submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            if (!this.conversation) {
                try {
                    await this.createConversation();
                } catch (error) {
                    this.isProcessing = false;
                    return;
                }
            }

            let data = new FormData();
            data.append('content', this.prompt);
            data.append('model', this.adapter.model);

            if (this.assistant?.id) {
                data.append('assistant_id', this.assistant.id);
            }

            if (this.quote) {
                data.append('quote', this.quote);
            }

            let msgs = document.getElementsByClassName('message');
            if (msgs.length > 0) {
                let pid = msgs[msgs.length - 1].dataset.id;

                if (pid) {
                    data.append('parent_id', pid);
                }
            }

            if (this.adapter?.supports_image && this.file) {
                data.append('file', this.file);
            }

            this.ask(data, this.assistant);
        },

        async ask(data, assistant) {
            try {
                let response = await api.post('/ai/conversations/' + this.conversation.id + '/messages', data);

                // Get the readable stream from the response body
                const stream = response.body
                    .pipeThrough(new TextDecoderStream())
                    .pipeThrough(new EventSourceParserStream());

                // Get the reader from the stream
                const reader = stream.getReader();

                // Temporary message
                let message = {
                    object: 'message',
                    id: 'temp',
                    model: null,
                    role: 'assistant',
                    content: '',
                    quote: null,
                    assistant: assistant,
                    parent_id: data.get('parent_id'),
                    children: []
                };
                let pushed = false;
                let lastMessage = null;

                window.scrollTo(0, document.body.scrollHeight);
                this.autoScroll = true;
                this.promptUpdated = false;

                this.file = null;

                while (true) {
                    if (this.autoScroll) {
                        window.scrollTo(0, document.body.scrollHeight);
                    }

                    const { value, done } = await reader.read();
                    if (done) {
                        this.isProcessing = false;

                        // Remove messages with null id from the conversation
                        this.conversation.messages = this.conversation.messages
                            .filter(msg => msg.id !== 'temp');

                        this.generateMap(lastMessage.id);
                        break;
                    }

                    if (value.event == 'token') {
                        message.content += JSON.parse(value.data);

                        if (!pushed) {
                            this.conversation.messages.push(message);
                            pushed = true;
                        }

                        this.generateMap(message.id);
                        continue;
                    }

                    if (value.event == 'message') {
                        let msg = JSON.parse(value.data);

                        if (!this.promptUpdated) {
                            this.quote = null;
                            this.prompt = null;
                        }

                        this.conversation.messages.push(msg);

                        if (msg.role === 'user') {
                            message.parent_id = msg.id;
                        }

                        this.generateMap();
                        lastMessage = msg;
                        continue;
                    }

                    if (value.event == 'error') {
                        this.error(value.data);
                        break;
                    }
                }
            } catch (error) {
                this.error(error);
            }
        },

        error(msg) {
            this.isProcessing = false;
            toast.error(msg);
            console.error(msg);

            // Remove messages with null id from the conversation
            this.conversation.messages = this.conversation.messages
                .filter(msg => msg.id !== 'temp');

            this.generateMap();
        },

        async createConversation() {
            let resp = await api.post('/ai/conversations');
            let conversation = resp.data;

            if (this.history === null) {
                this.history = [];
            }

            this.history.push(conversation);
            this.select(conversation);
        },

        select(conversation) {
            this.conversation = conversation;
            this.generateMap();

            let url = new URL(window.location.href);
            url.pathname = '/app/chat/' + conversation.id;
            window.history.pushState({}, '', url);
        },

        save(conversation) {
            api.post(`/library/conversations/${conversation.id}`, {
                title: conversation.title,
            }).then((resp) => {
                // Update the item in the history list
                if (this.history) {
                    let index = this.history.findIndex(item => item.id === resp.data.id);

                    if (index >= 0) {
                        this.history[index] = resp.data;
                    }
                }
            });
        },

        enter(e) {
            if (e.key === 'Enter' && !e.shiftKey && !this.isProcessing && this.prompt && this.prompt.trim() !== '') {
                e.preventDefault();
                this.submit();
            }
        },

        copy(message) {
            navigator.clipboard.writeText(message.content)
                .then(() => {
                    toast.success('Copied to clipboard!');
                });
        },

        textSelect(e) {
            this.$refs.quote.classList.add('hidden');
            this.$refs.quote.classList.remove('flex');

            let selection = window.getSelection();

            if (selection.rangeCount <= 0) {
                return;
            }

            let range = selection.getRangeAt(0);
            let text = range.toString();

            if (text.trim() == '') {
                return;
            }

            e.stopPropagation();

            let startNode = range.startContainer;
            let startOffset = range.startOffset;

            let rect;
            if (startNode.nodeType === Node.TEXT_NODE) {
                // Create a temporary range to get the exact position of the start
                let tempRange = document.createRange();
                tempRange.setStart(startNode, startOffset);
                tempRange.setEnd(startNode, startOffset + 1); // Add one character to make the range visible
                rect = tempRange.getBoundingClientRect();
            } else if (startNode.nodeType === Node.ELEMENT_NODE) {
                // For element nodes, get the bounding rect directly
                rect = startNode.getBoundingClientRect();
            }

            // Adjust coordinates relative to the container (parent)
            let container = this.$refs.quote.parentElement;
            let containerRect = container.getBoundingClientRect();
            let x = rect.left - containerRect.left + container.scrollLeft;
            let y = rect.top - containerRect.top + container.scrollTop;

            this.$refs.quote.style.top = y + 'px';
            this.$refs.quote.style.left = x + 'px';

            this.$refs.quote.classList.add('flex');
            this.$refs.quote.classList.remove('hidden');

            this.$refs.quote.dataset.value = range.toString();

            return;

        },

        selectQuote() {
            this.quote = this.$refs.quote.dataset.value;
            this.$refs.quote.dataset.value = null;

            this.$refs.quote.classList.add('hidden');
            this.$refs.quote.classList.remove('flex');

            // Clear selection
            window.getSelection().removeAllRanges();
        },

        regenerate(message, model = null) {
            if (!message.parent_id) {
                return;
            }

            let parentMessage = this.conversation.messages.find(
                msg => msg.id === message.parent_id
            );

            if (!parentMessage) {
                return;
            }

            let data = new FormData();
            data.append('parent_id', parentMessage.id);
            data.append('model', model || message.model);

            this.ask(data, message.assistant);
        },

        edit(message, content) {
            let data = new FormData();

            data.append('model', message.model);
            data.append('content', content);

            if (message.parent_id) {
                data.append('parent_id', message.parent_id);
            }

            if (message.assistant?.id) {
                data.append('assistant_id', message.assistant.id);
            }

            if (message.quote) {
                data.append('quote', message.quote);
            }

            this.ask(data, message.assistant);
        },

        remove(conversation) {
            this.isDeleting = true;

            api.delete(`/library/conversations/${conversation.id}`)
                .then(() => {
                    this.conversation = null;
                    window.modal.close();

                    toast.show("Conversation has been deleted successfully.", 'ti ti-trash');
                    this.isDeleting = false;

                    let url = new URL(window.location.href);
                    url.pathname = '/app/chat/';
                    window.history.pushState({}, '', url);

                    this.history.splice(this.history.indexOf(conversation), 1);
                })
                .catch(error => this.isDeleting = false);
        },

        doesAssistantMatch(assistant, query) {
            query = query.trim().toLowerCase();

            if (!query) {
                return true;
            }

            if (assistant.name.toLowerCase().includes(query)) {
                return true;
            }

            if (assistant.expertise && assistant.expertise.toLowerCase().includes(query)) {
                return true;
            }

            if (assistant.description && assistant.description.toLowerCase().includes(query)) {
                return true;
            }

            return false;
        },

        selectAssistant(assistant) {
            this.assistant = assistant;
            window.modal.close();

            if (!this.conversation) {
                let url = new URL(window.location.href);
                url.pathname = '/app/chat/' + assistant.id;
                window.history.pushState({}, '', url);
            }
        },
    }));
}