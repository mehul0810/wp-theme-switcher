// ***********************************************
// Custom commands for Smart Theme Switcher tests
// ***********************************************

/**
 * Login to WordPress admin
 */
Cypress.Commands.add('loginAsAdmin', () => {
  cy.visit('/wp-login.php');
  cy.get('#user_login').type(Cypress.env('wpUsername'));
  cy.get('#user_pass').type(Cypress.env('wpPassword'));
  cy.get('#wp-submit').click();
  cy.get('body.wp-admin').should('exist');
});

/**
 * Set theme for a post through admin
 */
Cypress.Commands.add('setPostTheme', (postId, themeName) => {
  cy.visit(`/wp-admin/post.php?post=${postId}&action=edit`);
  
  // Wait for editor to load
  cy.get('.edit-post-header').should('be.visible');
  
  // Open sidebar if not already open
  cy.get('.edit-post-header__settings button[aria-label="Settings"]').then($settingsButton => {
    if (!Cypress.$('.interface-interface-skeleton__sidebar').is(':visible')) {
      cy.wrap($settingsButton).click();
    }
  });
  
  // Find the Theme Preview panel and expand if collapsed
  cy.get('.components-panel__body')
    .contains('Theme Preview')
    .parent()
    .then($panel => {
      if (!$panel.hasClass('is-opened')) {
        cy.wrap($panel).click();
      }
    });
  
  // Select the theme from dropdown
  cy.get('.sts-theme-select select').select(themeName);
  
  // Save the post
  cy.get('.editor-post-publish-button__button').click();
  cy.get('.components-snackbar__content').contains('Post updated').should('be.visible');
});

/**
 * Enable/disable preview mode in plugin settings
 */
Cypress.Commands.add('setPreviewMode', (enabled) => {
  cy.visit('/wp-admin/options-general.php?page=smart-theme-switcher');
  
  // Toggle preview mode checkbox
  cy.get('input[name="smart_theme_switcher_settings[enable_preview]"]').then($checkbox => {
    if (($checkbox.is(':checked') && !enabled) || (!$checkbox.is(':checked') && enabled)) {
      cy.wrap($checkbox).click();
    }
  });
  
  // Save settings
  cy.get('#submit').click();
  cy.get('.notice-success').should('be.visible');
});

/**
 * Preview a post with a specific theme
 */
Cypress.Commands.add('previewPostWithTheme', (postUrl, themeName) => {
  const queryParam = Cypress.env('queryParam') || 'sts_theme';
  cy.visit(`${postUrl}?${queryParam}=${themeName}`);
  
  // Check for preview banner
  cy.get('#sts-preview-banner').should('be.visible');
});

/**
 * Check if current theme matches expected theme
 * This is a simplified approach - in real tests you'd need a reliable way to detect the active theme
 */
Cypress.Commands.add('checkActiveTheme', (expectedTheme) => {
  // Look for theme-specific classes or elements
  // This implementation is simplified and would need to be customized based on how themes can be identified
  cy.get('body').should('have.attr', 'data-theme', expectedTheme);
  
  // Alternative: check for theme-specific CSS or structures
  // cy.get('body').should('have.class', `theme-${expectedTheme}`);
});