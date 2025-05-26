/**
 * Test: Edge Cases for Smart Theme Switcher
 * 
 * Tests various edge cases to ensure the plugin gracefully handles
 * unusual scenarios.
 */

describe('Edge Cases', () => {
  const postId = 1; // Assuming post ID 1 exists
  const validTheme = 'twentytwentyone';
  const invalidTheme = 'non-existent-theme';
  const defaultTheme = 'twentytwentytwo';
  const queryParam = Cypress.env('queryParam') || 'sts_theme';

  before(() => {
    // Enable preview mode
    cy.loginAsAdmin();
    cy.setPreviewMode(true);
  });
  
  it('should fall back to default theme when invalid theme slug is provided', () => {
    // As admin, try to preview with invalid theme
    cy.loginAsAdmin();
    cy.visit(`/?p=${postId}&${queryParam}=${invalidTheme}`);
    
    // Should not have the invalid theme class
    cy.get('body').should('not.have.class', `theme-${invalidTheme}`);
    
    // Should have the default theme
    cy.get('body').should('have.class', `theme-${defaultTheme}`);
  });

  it('should not show preview elements in admin pages', () => {
    // Visit admin dashboard with preview parameter
    cy.loginAsAdmin();
    cy.visit(`/wp-admin/index.php?${queryParam}=${validTheme}`);
    
    // Preview banner should not be visible in admin
    cy.get('#sts-preview-banner').should('not.exist');
  });

  it('should work with classic themes', () => {
    // This test assumes twentytwenty is a classic theme
    const classicTheme = 'twentytwenty';
    
    cy.loginAsAdmin();
    cy.visit(`/?p=${postId}&${queryParam}=${classicTheme}`);
    
    // Check that the preview theme is active
    cy.get('body').should('have.class', `theme-${classicTheme}`);
  });

  it('should work with block themes', () => {
    // This test assumes twentytwentytwo is a block theme
    const blockTheme = 'twentytwentytwo';
    
    cy.loginAsAdmin();
    cy.visit(`/?p=${postId}&${queryParam}=${blockTheme}`);
    
    // Check that the preview theme is active
    cy.get('body').should('have.class', `theme-${blockTheme}`);
  });

  it('should not show preview for search bots', () => {
    // Set user agent to a search bot
    cy.visit(`/?p=${postId}&${queryParam}=${validTheme}`, {
      headers: {
        'User-Agent': 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
      }
    });
    
    // Preview banner should not be visible
    cy.get('#sts-preview-banner').should('not.exist');
    
    // Default theme should be active
    cy.get('body').should('have.class', `theme-${defaultTheme}`);
  });
});