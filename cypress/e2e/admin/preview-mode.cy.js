/**
 * Test: Preview Mode functionality
 * 
 * This test validates that theme preview mode works correctly for admins
 * but doesn't affect regular visitors.
 */

describe('Preview Mode', () => {
  const postId = 1; // Assuming post ID 1 exists
  const testTheme = 'twentytwentyone';
  const defaultTheme = 'twentytwentytwo';
  const queryParam = Cypress.env('queryParam') || 'sts_theme';
  
  before(() => {
    // Enable preview mode in settings
    cy.loginAsAdmin();
    cy.setPreviewMode(true);
  });
  
  after(() => {
    // Disable preview mode after tests
    cy.loginAsAdmin();
    cy.setPreviewMode(false);
  });

  it('should display preview theme for admin using query parameter', () => {
    // Login as admin
    cy.loginAsAdmin();
    
    // Visit post with preview parameter
    cy.visit(`/?p=${postId}&${queryParam}=${testTheme}`);
    
    // Check that preview banner is visible
    cy.get('#sts-preview-banner').should('be.visible');
    
    // Verify theme dropdown shows selected theme
    cy.get('#sts-theme-select').should('have.value', testTheme);
    
    // Check that the preview theme is active
    cy.get('body').should('have.class', `theme-${testTheme}`);
  });

  it('should allow switching themes via preview banner', () => {
    // Login as admin
    cy.loginAsAdmin();
    
    // Visit post with preview parameter
    cy.visit(`/?p=${postId}&${queryParam}=${testTheme}`);
    
    // Switch to a different theme using the banner dropdown
    const alternativeTheme = 'twentytwenty';
    cy.get('#sts-theme-select').select(alternativeTheme);
    
    // Verify URL changes
    cy.url().should('include', `${queryParam}=${alternativeTheme}`);
    
    // Check that the new preview theme is active
    cy.get('body').should('have.class', `theme-${alternativeTheme}`);
  });

  it('should exit preview mode when using Exit Preview button', () => {
    // Login as admin
    cy.loginAsAdmin();
    
    // Visit post with preview parameter
    cy.visit(`/?p=${postId}&${queryParam}=${testTheme}`);
    
    // Click Exit Preview button
    cy.get('.sts-exit-preview-button').click();
    
    // Verify preview parameter is removed from URL
    cy.url().should('not.include', queryParam);
    
    // Check that preview banner is gone
    cy.get('#sts-preview-banner').should('not.exist');
    
    // Check that default theme is active
    cy.get('body').should('have.class', `theme-${defaultTheme}`);
  });

  it('should not display preview theme for visitors', () => {
    // Set up preview as admin
    cy.loginAsAdmin();
    cy.visit(`/?p=${postId}&${queryParam}=${testTheme}`);
    cy.get('#sts-preview-banner').should('be.visible');
    
    // Now log out and visit as visitor
    cy.clearCookies();
    cy.visit(`/?p=${postId}&${queryParam}=${testTheme}`);
    
    // Preview banner should not be visible
    cy.get('#sts-preview-banner').should('not.exist');
    
    // Check that default theme is active, not preview theme
    cy.get('body').should('have.class', `theme-${defaultTheme}`);
    cy.get('body').should('not.have.class', `theme-${testTheme}`);
  });

  it('should not show preview banner when preview mode is disabled', () => {
    // Disable preview mode
    cy.loginAsAdmin();
    cy.setPreviewMode(false);
    
    // Visit post with preview parameter
    cy.visit(`/?p=${postId}&${queryParam}=${testTheme}`);
    
    // Preview banner should not be visible
    cy.get('#sts-preview-banner').should('not.exist');
    
    // Re-enable preview mode for other tests
    cy.setPreviewMode(true);
  });
});