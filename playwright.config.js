import { defineConfig, devices } from '@playwright/test';
import path from 'path';
import { config as loadEnv } from 'dotenv';

// .env.test se credentials load karo (gitignored)
loadEnv({ path: '.env.test' });

const authFile = 'tests/e2e/.auth/session.json';

export default defineConfig({
    testDir: './tests/e2e',
    timeout: 30000,
    retries: 0,
    workers: 1,
    reporter: [['html', { open: 'never' }], ['line']],

    use: {
        baseURL: process.env.TEST_URL || 'http://187.77.190.135',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        headless: true,
    },

    projects: [
        // Pehle sirf ek baar login karo aur session save karo
        {
            name: 'setup',
            testMatch: '**/setup/auth.setup.js',
        },

        // Phir sab tests saved session reuse karenge
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
                storageState: authFile,
            },
            dependencies: ['setup'],
        },
    ],
});
