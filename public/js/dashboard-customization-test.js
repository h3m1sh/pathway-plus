/**
 * Dashboard Customization Test Suite
 * 
 * This file contains tests to verify the dashboard customization functionality
 * Run these tests in the browser console on the dashboard page
 */

class DashboardCustomizationTest {
    constructor() {
        this.testResults = [];
    }

    // Test 1: Check if customization mode can be toggled
    testCustomizationModeToggle() {
        console.log('🧪 Testing customization mode toggle...');
        
        const customizeBtn = document.getElementById('customizationControls');
        const initialDisplay = customizeBtn.style.display;
        
        // Simulate clicking the customize button
        document.getElementById('customizeLayout').click();
        
        setTimeout(() => {
            const newDisplay = customizeBtn.style.display;
            const success = newDisplay !== initialDisplay;
            
            this.testResults.push({
                test: 'Customization Mode Toggle',
                success: success,
                message: success ? '✅ Customization mode toggle works' : '❌ Customization mode toggle failed'
            });
            
            console.log(this.testResults[this.testResults.length - 1].message);
        }, 100);
    }

    // Test 2: Check if widgets are draggable
    testWidgetDraggability() {
        console.log('🧪 Testing widget draggability...');
        
        const widgets = document.querySelectorAll('.widget-item');
        let draggableCount = 0;
        
        widgets.forEach(widget => {
            if (widget.hasAttribute('draggable') && widget.getAttribute('draggable') === 'true') {
                draggableCount++;
            }
        });
        
        const success = draggableCount === widgets.length;
        
        this.testResults.push({
            test: 'Widget Draggability',
            success: success,
            message: success ? `✅ All ${widgets.length} widgets are draggable` : `❌ Only ${draggableCount}/${widgets.length} widgets are draggable`
        });
        
        console.log(this.testResults[this.testResults.length - 1].message);
    }

    // Test 3: Check if widget controls are present
    testWidgetControls() {
        console.log('🧪 Testing widget controls...');
        
        const widgets = document.querySelectorAll('.widget-item');
        let controlsCount = 0;
        
        widgets.forEach(widget => {
            const controls = widget.querySelector('.widget-controls');
            if (controls) {
                controlsCount++;
            }
        });
        
        const success = controlsCount === widgets.length;
        
        this.testResults.push({
            test: 'Widget Controls',
            success: success,
            message: success ? `✅ All ${widgets.length} widgets have controls` : `❌ Only ${controlsCount}/${widgets.length} widgets have controls`
        });
        
        console.log(this.testResults[this.testResults.length - 1].message);
    }

    // Test 4: Check if localStorage functions work
    testLocalStorage() {
        console.log('🧪 Testing localStorage functionality...');
        
        const testData = {
            layout: ['test1', 'test2'],
            visibility: { test1: true, test2: false },
            timestamp: new Date().toISOString()
        };
        
        try {
            localStorage.setItem('dashboardPreferences', JSON.stringify(testData));
            const retrieved = JSON.parse(localStorage.getItem('dashboardPreferences'));
            const success = JSON.stringify(retrieved) === JSON.stringify(testData);
            
            this.testResults.push({
                test: 'Local Storage',
                success: success,
                message: success ? '✅ LocalStorage functionality works' : '❌ LocalStorage functionality failed'
            });
            
            console.log(this.testResults[this.testResults.length - 1].message);
        } catch (error) {
            this.testResults.push({
                test: 'Local Storage',
                success: false,
                message: '❌ LocalStorage test failed with error: ' + error.message
            });
            
            console.log(this.testResults[this.testResults.length - 1].message);
        }
    }

    // Test 5: Check if widget configuration is defined
    testWidgetConfiguration() {
        console.log('🧪 Testing widget configuration...');
        
        const success = typeof widgetConfig !== 'undefined' && 
                       Object.keys(widgetConfig).length > 0 &&
                       widgetConfig.hasOwnProperty('profile') &&
                       widgetConfig.hasOwnProperty('skillPassport');
        
        this.testResults.push({
            test: 'Widget Configuration',
            success: success,
            message: success ? `✅ Widget configuration defined with ${Object.keys(widgetConfig).length} widgets` : '❌ Widget configuration missing or incomplete'
        });
        
        console.log(this.testResults[this.testResults.length - 1].message);
    }

    // Test 6: Check if API endpoints are accessible
    async testAPIEndpoints() {
        console.log('🧪 Testing API endpoints...');
        
        try {
            const response = await fetch('/dashboard/preferences/load', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const success = response.ok;
            
            this.testResults.push({
                test: 'API Endpoints',
                success: success,
                message: success ? '✅ API endpoints are accessible' : '❌ API endpoints are not accessible'
            });
            
            console.log(this.testResults[this.testResults.length - 1].message);
        } catch (error) {
            this.testResults.push({
                test: 'API Endpoints',
                success: false,
                message: '❌ API endpoints test failed: ' + error.message
            });
            
            console.log(this.testResults[this.testResults.length - 1].message);
        }
    }

    // Run all tests
    async runAllTests() {
        console.log('🚀 Starting Dashboard Customization Tests...\n');
        
        this.testCustomizationModeToggle();
        this.testWidgetDraggability();
        this.testWidgetControls();
        this.testLocalStorage();
        this.testWidgetConfiguration();
        await this.testAPIEndpoints();
        
        // Wait a bit for async tests to complete
        setTimeout(() => {
            this.printResults();
        }, 500);
    }

    // Print test results
    printResults() {
        console.log('\n📊 Test Results Summary:');
        console.log('========================');
        
        let passedTests = 0;
        let totalTests = this.testResults.length;
        
        this.testResults.forEach(result => {
            if (result.success) {
                passedTests++;
            }
            console.log(`${result.success ? '✅' : '❌'} ${result.test}: ${result.message}`);
        });
        
        console.log('\n📈 Summary:');
        console.log(`Passed: ${passedTests}/${totalTests} tests`);
        console.log(`Success Rate: ${Math.round((passedTests / totalTests) * 100)}%`);
        
        if (passedTests === totalTests) {
            console.log('🎉 All tests passed! Dashboard customization is working correctly.');
        } else {
            console.log('⚠️  Some tests failed. Please check the implementation.');
        }
    }
}

// Auto-run tests when loaded
if (typeof window !== 'undefined') {
    window.DashboardCustomizationTest = DashboardCustomizationTest;
    
    // Run tests when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            const testSuite = new DashboardCustomizationTest();
            testSuite.runAllTests();
        });
    } else {
        const testSuite = new DashboardCustomizationTest();
        testSuite.runAllTests();
    }
}

// Manual test runner
function runDashboardTests() {
    const testSuite = new DashboardCustomizationTest();
    testSuite.runAllTests();
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DashboardCustomizationTest;
} 