/**
 * Test: Theme Set Mode for Post Types
 * 
 * This test validates that theme assignments for post types
 * work correctly for both visitors and admin users.
 */

describe('Theme Set Mode - Post Types', () => {
  const postType = 'post'; // Using standard post type
  const testTheme = 'twentytwentyone'; 
  const defaultTheme = 'twentytwentytwo';
  
  before(() => {
    // Set up post type theme assignment
    cy.loginAsAdmin();
    cy.visit('/wp-admin/options-general.php?page=smart-theme-switcher');
    
    // Configure post type theme setting
    // Note: Actual UI interaction will depend on the settings page structure
    cy.get(`input[name="smart_theme_switcher_settings[post_types][${postType}][enabled]"]`).check();
    cy.get(`select[name="smart_theme_switcher_settings[post_types][${postType}][theme]"]`).select(testTheme);
    cy.get('#submit').click();
    cy.get('.notice-success').should('be.visible');
  });
  
  after(() => {
    // Clean up - disable post type theme assignment
    cy.loginAsAdmin();
    cy.visit('/wp-admin/options-general.php?page=smart-theme-switcher');
    cy.get(`input[name="smart_theme_switcher_settings[post_types][${postType}][enabled]"]`).uncheck();
    cy.get('#submit').click();
  });

  it('should display assigned theme for visitors when set for post type', () => {
    // Create a new post of the configured post type (or use existing)
    cy.loginAsAdmin();
    cy.visit('/wp-admin/post-new.php');
    cy.get('.editor-post-title__input').type('Test Post for Post Type Theme');
    cy.get('.editor-post-publish-button__button').click();
    cy.get('.editor-post-publish-panel__header-publish-button button').click();
    
    // Get the post ID from the URL
    cy.url().then(url => {
      const postId = url.match(/post=(\d+)/)[1];
      
      // Visit as non-logged-in user
      cy.clearCookies();
      cy.visit(`/?p=${postId}`);
      
      // Check that the assigned theme for post type is active
      cy.get('body').should('have.class', `theme-${testTheme}`);
    });
  });

  it('should display assigned theme for admins when set for post type', () => {
    // Visit the post type archive
    cy.loginAsAdmin();
    cy.visit(`/?post_type=${postType}`);
    
    // Check that the assigned theme is active
    cy.get('body').should('have.class', `theme-${testTheme}`);
  });

  it('should revert to default theme when post type assignment is removed', () => {
    // Disable post type theme assignment
    cy.loginAsAdmin();
    cy.visit('/wp-admin/options-general.php?page=smart-theme-switcher');
    cy.get(`input[name="smart_theme_switcher_settings[post_types][${postType}][enabled]"]`).uncheck();
    cy.get('#submit').click();
    
    // Visit the post type archive
    cy.visit(`/?post_type=${postType}`);
    
    // Check that default theme is active
    cy.get('body').should('have.class', `theme-${defaultTheme}`);
  });
});