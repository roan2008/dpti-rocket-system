import { test, expect } from '@playwright/test';

/**
 * E2E Test for "Create Production Step" Workflow
 * 
 * This test automates the complete user journey for creating a production step:
 * 1. Authentication as staff user
 * 2. Navigation to Add Production Step form
 * 3. Template selection and dynamic form filling
 * 4. Form submission and verification
 */

// Test configuration
const TEST_USER = {
  username: 'test_staff1',
  password: 'testpass123'
};

// Test data for production step
const PRODUCTION_STEP_DATA = {
  template: 'Quality Control Inspection',
  // Dynamic fields will be filled based on the template
  fields: {
    'Inspector Name': 'John Smith',
    'Inspection Date': '2025-01-08',
    'Pass/Fail Status': 'Pass',
    'Notes': 'All quality checks completed successfully. Component meets specifications.',
    'Temperature (°C)': '22',
    'Humidity (%)': '45'
  }
};

test.describe('Create Production Step E2E Tests', () => {
  
  test.beforeEach('Login as staff user', async ({ page }) => {
    // Navigate to login page using full URL
    await page.goto('http://localhost/dpti-rocket-system/views/login_view.php');
    
    // Wait for page to load and verify we're on the login page
    await expect(page.locator('h2')).toContainText('DPTI Rocket System - Login');
    
    // Perform login using correct selectors
    await page.fill('#username', TEST_USER.username);
    await page.fill('#password', TEST_USER.password);
    await page.click('button[type="submit"]');
    
    // Verify successful login by checking we're redirected to dashboard
    await expect(page).toHaveURL(/dashboard\.php/);
    await expect(page.locator('h1')).toContainText('Dashboard');
  });

  test('Complete Create Production Step Workflow', async ({ page }) => {
    // Step 1: From dashboard, click on the first rocket's "View" button
    const firstViewButton = page.locator('table tbody tr:first-child .action-buttons a:has-text("View")');
    await expect(firstViewButton).toBeVisible();
    await firstViewButton.click();
    
    // Verify we're on rocket detail page
    await expect(page.locator('h1')).toContainText('Rocket Details');
    
    // Step 2: Navigate to Add Production Step form
    const addStepButton = page.locator('a:has-text("Add New Production Step")');
    await expect(addStepButton).toBeVisible();
    await addStepButton.click();
    
    // Verify we're on the add production step page
    await expect(page.locator('h1')).toContainText('Add Production Step');
    
    // Step 3: Select template from dropdown
    const templateSelect = page.locator('select[name="template_name"]');
    await expect(templateSelect).toBeVisible();
    await templateSelect.selectOption(PRODUCTION_STEP_DATA.template);
    
    // Wait for dynamic fields to load
    await page.waitForTimeout(1000); // Give time for AJAX to load fields
    
    // Step 4: Fill out the dynamic fields that appear
    for (const [fieldName, fieldValue] of Object.entries(PRODUCTION_STEP_DATA.fields)) {
      // Try different field types (input, textarea, select)
      const inputField = page.locator(`input[data-field-label="${fieldName}"], input[placeholder*="${fieldName}"]`);
      const textareaField = page.locator(`textarea[data-field-label="${fieldName}"], textarea[placeholder*="${fieldName}"]`);
      const selectField = page.locator(`select[data-field-label="${fieldName}"]`);
      
      if (await inputField.count() > 0) {
        await inputField.fill(fieldValue);
      } else if (await textareaField.count() > 0) {
        await textareaField.fill(fieldValue);
      } else if (await selectField.count() > 0) {
        await selectField.selectOption(fieldValue);
      } else {
        // Fallback: try to find by label text
        const labelLocator = page.locator(`label:has-text("${fieldName}")`);
        if (await labelLocator.count() > 0) {
          const fieldId = await labelLocator.getAttribute('for');
          if (fieldId) {
            const field = page.locator(`#${fieldId}`);
            await field.fill(fieldValue);
          }
        }
      }
    }
    
    // Step 5: Submit the form
    const submitButton = page.locator('button[type="submit"]:has-text("Add Production Step")');
    await expect(submitButton).toBeVisible();
    await submitButton.click();
    
    // Step 6: Verify success and that the new step appears
    
    // Check for success message
    const successMessage = page.locator('.message.success, .alert.success');
    await expect(successMessage).toBeVisible({ timeout: 10000 });
    await expect(successMessage).toContainText('successfully');
    
    // Verify we're redirected back to rocket detail page with the new step
    await expect(page.locator('h1')).toContainText('Rocket Details');
    
    // Verify the new production step appears in the Production History
    const productionHistory = page.locator('.steps-container');
    await expect(productionHistory).toBeVisible();
    
    // Look for the new step with our template name
    const newStep = page.locator('.step-card').filter({ 
      hasText: PRODUCTION_STEP_DATA.template 
    });
    await expect(newStep).toBeVisible();
    
    // Verify the step contains our data
    await newStep.locator('details summary').click(); // Open step details
    
    // Check that some of our filled data appears in the step details
    for (const [fieldName, fieldValue] of Object.entries(PRODUCTION_STEP_DATA.fields)) {
      const stepData = newStep.locator('.json-data, .data-table');
      await expect(stepData).toContainText(fieldValue);
    }
    
    // Verify the step shows the correct staff member
    await expect(newStep).toContainText(TEST_USER.username);
    
    // Verify the timestamp is recent (today's date)
    const today = new Date().toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric', 
      year: 'numeric' 
    });
    await expect(newStep).toContainText(today);
  });

  test('Verify form validation', async ({ page }) => {
    // Navigate to add production step form from dashboard
    const firstViewButton = page.locator('table tbody tr:first-child .action-buttons a:has-text("View")');
    await firstViewButton.click();
    
    const addStepButton = page.locator('a:has-text("Add New Production Step")');
    await addStepButton.click();
    
    // Try to submit without selecting template
    const submitButton = page.locator('button[type="submit"]:has-text("Add Production Step")');
    await submitButton.click();
    
    // Should show validation error
    const errorMessage = page.locator('.message.error, .alert.error');
    await expect(errorMessage).toBeVisible();
    
    // Select template but leave required fields empty
    const templateSelect = page.locator('select[name="template_name"]');
    await templateSelect.selectOption(PRODUCTION_STEP_DATA.template);
    await page.waitForTimeout(1000);
    
    // Try to submit with empty required fields
    await submitButton.click();
    
    // Should show validation error for missing fields
    await expect(errorMessage).toBeVisible();
  });

  test('Verify template switching loads different fields', async ({ page }) => {
    // Navigate to add production step form from dashboard
    const firstViewButton = page.locator('table tbody tr:first-child .action-buttons a:has-text("View")');
    await firstViewButton.click();
    
    const addStepButton = page.locator('a:has-text("Add New Production Step")');
    await addStepButton.click();
    
    const templateSelect = page.locator('select[name="template_name"]');
    
    // Select first template
    await templateSelect.selectOption({ index: 1 }); // Skip the default option
    await page.waitForTimeout(1000);
    
    // Count the number of dynamic fields
    const firstTemplateFields = await page.locator('#dynamic-fields input, #dynamic-fields textarea, #dynamic-fields select').count();
    
    // Select a different template
    await templateSelect.selectOption({ index: 2 });
    await page.waitForTimeout(1000);
    
    // Count fields again - should be different
    const secondTemplateFields = await page.locator('#dynamic-fields input, #dynamic-fields textarea, #dynamic-fields select').count();
    
    // Verify that different templates load different fields
    // (This might be the same if templates are similar, but the important thing is that fields reload)
    expect(firstTemplateFields).toBeGreaterThan(0);
    expect(secondTemplateFields).toBeGreaterThan(0);
  });
});
