'use strict';

import Alpine from 'alpinejs';
import api from './api';

export function planSnapshowView() {
    Alpine.data('plansnapshot', (snapshot) => ({
        snapshot: snapshot,
        isProcessing: false,
        orders: [],

        init() {
            this.getOrders();
        },
        getOrders() {
            api.get(`/orders?plan_snapshot=${this.snapshot.id}&sort=created_at:desc&limit=3`)
                .then(response => {
                    this.orders = response.data.data;
                });
        },

        resync() {
            if (this.isProcessing) {
                return;
            }

            this.isProcessing = true;

            api.post(`/plan-snapshots/${this.snapshot.id}/resync`)
                .then(response => {
                    this.isProcessing = false;
                    this.snapshot = response.data;
                    window.modal.close();
                })
                .catch(error => this.isProcessing = false);
        }
    }))
}