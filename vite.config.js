import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({

    plugins: [

        laravel({

            input: [

                'resources/css/app.css',
                'resources/js/app.js',

                'resources/css/customers.css',
                'resources/js/customers.js',

                'resources/css/create.css',
                'resources/js/create.js',

                'resources/css/login.css',

                'resources/css/password.css',

                'resources/css/dashboard.css',

                'resources/css/groups.css',
                'resources/js/groups-form.js',
                'resources/js/group-show.js',
                'resources/js/group-members.js',

                'resources/css/payments.css',
                'resources/js/payments.js',

                'resources/css/draws.css',
                'resources/js/draws.js',
                'resources/js/password-toggle.js',

            ],

            refresh: true,

        }),

    ],

});