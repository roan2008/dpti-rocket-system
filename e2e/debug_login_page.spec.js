// @ts-check
import { test, expect } from '@playwright/test';

test.describe('Debug Login Page', () => {
    test('Check login page elements and structure', async ({ page }) => {
        // Navigate to login page directly
        await page.goto('views/login_view.php');
        
        // Wait for page load
        await page.waitForLoadState('networkidle');
        
        // Take screenshot for debugging
        await page.screenshot({ path: 'login-page-debug.png', fullPage: true });
        
        // Get page content for debugging
        const pageContent = await page.content();
        console.log('Page Title:', await page.title());
        console.log('Page URL:', await page.url());
        console.log('Page content length:', pageContent.length);
        
        // Check if page has any content
        expect(pageContent.length).toBeGreaterThan(100);
        
        // Look for any h1, h2, h3 elements
        const headings = await page.locator('h1, h2, h3, h4, h5, h6').allTextContents();
        console.log('All headings found:', headings);
        
        // Look for login form elements
        const forms = await page.locator('form').count();
        console.log('Number of forms found:', forms);
        
        // Look for input fields
        const inputs = await page.locator('input').count();
        console.log('Number of input fields found:', inputs);
        
        // Check specific elements
        const hasUsernameField = await page.locator('#username').isVisible();
        const hasPasswordField = await page.locator('#password').isVisible();
        console.log('Username field visible:', hasUsernameField);
        console.log('Password field visible:', hasPasswordField);
        
        // Print first 500 characters of page content for debugging
        console.log('Page content preview:', pageContent.substring(0, 500));
    });
});
