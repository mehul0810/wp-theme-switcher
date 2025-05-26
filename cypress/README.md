# Cypress Tests for Smart Theme Switcher

This directory contains end-to-end (E2E) tests for the Smart Theme Switcher WordPress plugin using Cypress.

## Test Categories

1. **Theme Set Mode (All Users)**
   - Individual post theme assignment
   - Post type theme assignment
   - Verification for both visitors and admins

2. **Preview Mode (Authorized Users Only)**
   - Theme preview functionality
   - Preview banner interaction
   - Theme switching via UI
   - Preview isolation (admins only)

3. **Edge Cases**
   - Invalid theme handling
   - Admin area preview prevention
   - Block vs. Classic theme compatibility
   - Search bot handling

4. **Performance & UX**
   - Accessibility testing
   - Transition performance
   - UI cleanliness

## Setup and Installation

1. Install dependencies:
   ```bash
   npm install --save-dev cypress
   ```

2. Set up a local WordPress environment with:
   - WordPress installed
   - Smart Theme Switcher plugin activated
   - At least two themes installed (e.g., Twenty Twenty, Twenty Twenty-One)
   - A sample post and page created

3. Configure Cypress:
   - Update `cypress.config.js` with your local WordPress URL
   - Adjust the admin credentials as needed
   - Modify theme slugs in tests if necessary

## Running Tests

To open Cypress test runner:
```bash
npx cypress open
```

To run all tests headlessly:
```bash
npx cypress run
```

To run a specific test file:
```bash
npx cypress run --spec "cypress/e2e/admin/preview-mode.cy.js"
```

## Test Architecture

- `cypress/e2e/admin/` - Tests requiring admin access
- `cypress/e2e/public/` - Tests for visitor-facing functionality
- `cypress/support/commands.js` - Custom commands for WordPress and plugin interactions
- `cypress/fixtures/` - Test data (if needed)

## Notes for Test Maintenance

- The tests assume specific theme slugs. Update these if your test environment uses different themes.
- The method for detecting active themes (`body` class) is a placeholder. You may need to implement a more reliable detection method.
- Admin UI selectors may need adjustment if the plugin's admin interface changes.

## Debugging

If tests fail:
1. Check that your WordPress environment matches the expected configuration
2. Verify that the selectors in the tests match your current plugin version
3. Review the Cypress screenshots and videos generated in the `cypress/screenshots` and `cypress/videos` directories