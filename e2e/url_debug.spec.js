// @ts-check
import { test, expect } from '@playwright/test';

test.describe('URL Debug Test', () => {
    test('Check URL resolution', async ({ page }) => {
        console.log('Base URL from config:', test.info().project.use.baseURL);
        
        // Test different navigation patterns
        console.log('\n=== Testing Root URL ===');
        await page.goto('/');
        console.log('Final URL after goto("/"):', await page.url());
        
        console.log('\n=== Testing Login URL (relative) ===');
        await page.goto('views/login_view.php');
        console.log('Final URL after goto("views/login_view.php"):', await page.url());
        
        console.log('\n=== Testing Full URL ===');
        await page.goto('http://localhost/dpti-rocket-system/views/login_view.php');
        console.log('Final URL after full URL:', await page.url());
        
        // Check page content
        const content = await page.content();
        console.log('Page content length:', content.length);
        console.log('Page title:', await page.title());
        
        if (content.includes('404')) {
            console.log('ERROR: Getting 404 page');
        } else {
            console.log('SUCCESS: Page loaded correctly');
        }
    });
});
