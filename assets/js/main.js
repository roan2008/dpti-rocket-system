// DPTI Rocket System - Main JavaScript File

// DOM Content Loaded Event
document.addEventListener('DOMContentLoaded', function() {
    // Initialize application
    console.log('DPTI Rocket System loaded');
    
    // Initialize any interactive elements
    initializeInteractiveElements();
});

// Initialize interactive elements
function initializeInteractiveElements() {
    // Add click handlers for buttons if needed
    const buttons = document.querySelectorAll('.btn-primary, .btn-small');
    buttons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            // Add any button-specific logic here if needed
            // For now, just let the default action proceed
        });
    });
    
    // Add form validation if needed
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // Add form validation here if needed
            // For now, just let the default action proceed
        });
    });
}

// Utility functions (safe, no eval)
function showMessage(message, type) {
    // Safe way to show messages without using eval
    console.log(type + ': ' + message);
}

function formatDate(dateString) {
    // Safe date formatting
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    } catch (error) {
        return dateString;
    }
}
