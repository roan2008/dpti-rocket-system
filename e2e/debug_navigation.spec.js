import { test, expect } from '@playwright/test';

const TEST_USER = {
  username: 'test_staff1',
  password: 'testpass123'
};

test('Debug Navigation Flow', async ({ page }) => {
  // Login
  await page.goto('http://localhost/dpti-rocket-system/views/login_view.php');
  await page.fill('#username', TEST_USER.username);
  await page.fill('#password', TEST_USER.password);
  await page.click('button[type="submit"]');
  
  console.log('✅ Logged in, URL:', page.url());
  
  // Check dashboard
  await expect(page.locator('h1')).toContainText('Dashboard');
  console.log('✅ Dashboard loaded');
  
  // Check if table exists and has data
  const tableExists = await page.locator('table').count();
  console.log('📊 Table count:', tableExists);
  
  if (tableExists > 0) {
    const rowCount = await page.locator('table tbody tr').count();
    console.log('📋 Table rows:', rowCount);
    
    if (rowCount > 0) {
      // Try to click first View button
      const firstViewButton = page.locator('table tbody tr:first-child .action-buttons a:has-text("View")');
      const buttonExists = await firstViewButton.count();
      console.log('🔍 View button exists:', buttonExists > 0);
      
      if (buttonExists > 0) {
        await firstViewButton.click();
        console.log('✅ Clicked View button, new URL:', page.url());
        
        // Check what page we're on
        const h1Text = await page.locator('h1').textContent();
        console.log('📝 H1 text:', h1Text);
        
        // Look for Add Production Step button
        const addStepButton = page.locator('a:has-text("Add New Production Step")');
        const addButtonExists = await addStepButton.count();
        console.log('➕ Add Step button exists:', addButtonExists > 0);
        
        if (addButtonExists > 0) {
          await addStepButton.click();
          console.log('✅ Clicked Add Step button, new URL:', page.url());
          
          // Check what page we're on now
          const newH1Text = await page.locator('h1').textContent();
          console.log('📝 New H1 text:', newH1Text);
          
          // Take screenshot for debugging
          await page.screenshot({ path: 'debug-add-step-page.png' });
          console.log('📸 Screenshot saved as debug-add-step-page.png');
        }
      }
    } else {
      console.log('❌ No rockets in table');
    }
  } else {
    console.log('❌ No table found');
  }
});
