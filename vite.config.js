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

            ],

            refresh: true,

        }),

    ],

});