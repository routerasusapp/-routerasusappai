'use strict';

import Alpine from 'alpinejs';
import api from './api';
import { toast } from '../base/toast';

export function userView() {
    Alpine.data('user', (user) => ({
        user: {},
        model: {
            role: 0,
        },
        isProcessing: false,

        init() {
            this.user = user;
            this.model = { ...this.model, ...this.user };
        },

        submit() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;
            let data = this.model;
            data.status = data.status ? 1 : 0;

            this.user.id ? this.update(data) : this.create(data);
        },

        update(data) {
            api.post(`/users/${this.user.id}`, data)
                .then(response => {
                    this.user = response.data;
                    this.model = { ...this.user };

                    this.isProcessing = false;

                    toast.success('User has been updated successfully!');
                })
                .catch(error => this.isProcessing = false);
        },

        create(data) {
            api.post('/users', data)
                .then(response => {
                    toast.defer('User has been created successfully!');
                    window.location = `/admin/users/`;
                })
                .catch(error => this.isProcessing = false);
        }
    }))
}