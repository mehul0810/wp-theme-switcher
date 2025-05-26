/**
 * Test: Theme Set Mode for Individual Posts
 * 
 * This test validates that theme assignments for individual posts
 * work correctly for both visitors and admin users.
 */

describe('Theme Set Mode - Individual Posts', () => {
  const postId = 1; // Assuming post ID 1 exists, adjust as needed
  const testTheme = 'twentytwentyone'; // Use an available theme slug
  const defaultTheme = 'twentytwentytwo'; // Default theme

  beforeEach(() => {
    // Reset to default state before each test
    cy.loginAsAdmin();
    cy.setPostTheme(postId, ''); // Clear any theme assignment
  });

  it('should display assigned theme for visitors when set for individual post', () => {
    // As admin, set a theme for the post
    cy.loginAsAdmin();
    cy.setPostTheme(postId, testTheme);
    
    // Visit as non-logged-in user (visitor)
    cy.clearCookies();
    cy.visit(`/?p=${postId}`);
    
    // Check that the assigned theme is active
    // This verification will depend on how you can detect the active theme
    cy.get('body').should('have.class', `theme-${testTheme}`);
  });

  it('should display assigned theme for admins when set for individual post', () => {
    // As admin, set a theme for the post
    cy.loginAsAdmin();
    cy.setPostTheme(postId, testTheme);
    
    // Visit as admin
    cy.visit(`/?p=${postId}`);
    
    // Check that the assigned theme is active
    cy.get('body').should('have.class', `theme-${testTheme}`);
  });

  it('should revert to default theme when theme assignment is removed', () => {
    // First, set a theme
    cy.loginAsAdmin();
    cy.setPostTheme(postId, testTheme);
    
    // Then clear the theme assignment
    cy.setPostTheme(postId, '');
    
    // Visit as non-logged-in user
    cy.clearCookies();
    cy.visit(`/?p=${postId}`);
    
    // Check that default theme is active
    cy.get('body').should('have.class', `theme-${defaultTheme}`);
  });
});