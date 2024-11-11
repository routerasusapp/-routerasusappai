'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';
import { getFormData } from '../base/helpers';

export function voiceView() {
    Alpine.data('voice', (voice = {}) => ({
        voice: voice,
        isProcessing: false,

        init() {
            this.voice = voice;
        },

        submit(form) {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;
            this.update(getFormData(form));
        },

        update(data) {
            api.post(`/voices/${this.voice.id}`, data)
                .then(response => {
                    this.voice = response.data;
                    this.isProcessing = false;

                    toast.success('Voice has been updated successfully!');
                })
                .catch(error => this.isProcessing = false);
        }
    }))
}