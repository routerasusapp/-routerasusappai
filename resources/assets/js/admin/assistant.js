'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function assistantView() {
    Alpine.data('assistant', (assistant) => ({
        assistant: {},
        model: {},
        isProcessing: false,

        init() {
            this.assistant = assistant;
            this.model = { ...this.model, ...this.assistant };
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            let data = new FormData();
            for (let key in this.model) {
                data.append(key, this.model[key] || '');
            }

            data.append('status', this.model.status ? 1 : 0);

            if (!this.model.file) {
                data.delete('file');
            }

            this.assistant.id ? this.update(data) : this.create(data);
        },

        update(data) {
            api.post(`/assistants/${this.assistant.id}`, data)
                .then(response => {
                    this.assistant = response.data;
                    this.model = { ...this.assistant };

                    this.isProcessing = false;

                    toast.success('Assistant has been updated successfully!');
                })
                .catch(error => this.isProcessing = false);
        },

        create(data) {
            api.post('/assistants', data)
                .then(response => {
                    toast.defer('Assistant has been created successfully!');
                    window.location = `/admin/assistants/`;
                })
                .catch(error => this.isProcessing = false);
        }
    }))
}