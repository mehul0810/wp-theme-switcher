/**
 * Test: Performance and UX for Smart Theme Switcher
 * 
 * Tests accessibility, performance, and UX aspects of the plugin.
 */

describe('Performance & UX', () => {
  const postId = 1;
  const testTheme = 'twentytwentyone';
  const queryParam = Cypress.env('queryParam') || 'sts_theme';

  before(() => {
    // Enable preview mode
    cy.loginAsAdmin();
    cy.setPreviewMode(true);
  });
  
  it('should have accessible banner elements', () => {
    // Visit with preview mode
    cy.loginAsAdmin();
    cy.visit(`/?p=${postId}&${queryParam}=${testTheme}`);
    
    // Test keyboard navigation
    cy.get('#sts-preview-banner').should('be.visible');
    
    // Check theme select dropdown is keyboard accessible
    cy.get('#sts-theme-select')
      .should('have.attr', 'aria-label')
      .should('be.focusable');
    
    // Check exit button is keyboard accessible
    cy.get('.sts-exit-preview-button')
      .should('have.attr', 'role', 'button')
      .should('be.focusable');
      
    // Verify focus moves properly with tab
    cy.get('#sts-theme-select').focus();
    cy.tab().focused().should('have.class', 'sts-exit-preview-button');
  });

  it('should not have visible delays when switching themes', () => {
    // Visit with preview mode
    cy.loginAsAdmin();
    cy.visit(`/?p=${postId}&${queryParam}=${testTheme}`);
    
    // Time how long it takes to switch themes
    const alternativeTheme = 'twentytwenty';
    const startTime = Date.now();
    
    cy.get('#sts-theme-select').select(alternativeTheme);
    
    // Check that the page loads within a reasonable time (e.g., 3 seconds)
    cy.get('body', { timeout: 3000 }).should('be.visible').then(() => {
      const loadTime = Date.now() - startTime;
      expect(loadTime).to.be.lessThan(3000);
    });
    
    // No visible errors during transition
    cy.get('.notice-error').should('not.exist');
    cy.get('.wp-die-message').should('not.exist');
  });

  it('should not have residual query parameters after exiting preview', () => {
    // Visit with preview mode
    cy.loginAsAdmin();
    cy.visit(`/?p=${postId}&${queryParam}=${testTheme}`);
    
    // Exit preview
    cy.get('.sts-exit-preview-button').click();
    
    // Check URL doesn't contain the preview parameter
    cy.url().should('not.include', queryParam);
  });
});