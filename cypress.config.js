const { defineConfig } = require('cypress');
const setupNodeEvents = require('./cypress/plugins/index');

module.exports = defineConfig({
  fixturesFolder: 'cypress/fixtures',
  screenshotsFolder: 'cypress/screenshots',
  videosFolder: 'cypress/videos',
  e2e: {
    setupNodeEvents,
    baseUrl: 'http://localhost:8889',
    specPattern: 'cypress/e2e/**/*.cy.{js,jsx,ts,tsx}',
    supportFile: 'cypress/support/e2e.js',
  },
  env: {
    wpUsername: 'admin',
    wpPassword: 'password',
    pluginSlug: 'smart-theme-switcher',
    queryParam: 'sts_theme',
  },
});