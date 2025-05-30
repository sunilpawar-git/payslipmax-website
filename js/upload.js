/**
 * PayslipMax Upload Handler - Enhanced Security & UX
 * Implements comprehensive security measures and modern UX patterns
 */

class PayslipUploader {
    constructor() {
        this.maxFileSize = 15 * 1024 * 1024; // 15MB
        this.allowedTypes = ['application/pdf'];
        this.allowedExtensions = ['.pdf'];
        this.uploadEndpoint = '/api/upload.php'; // Switch to database-based upload system
        this.csrfToken = this.generateCSRFToken();
        this.rateLimitCount = 0;
        this.rateLimitWindow = 60000; // 1 minute
        this.maxUploadsPerWindow = 5;
        
        this.initializeUploader();
        this.setupEventListeners();
        this.setupSecurityMeasures();
    }

    /**
     * Generate CSRF token for security
     */
    generateCSRFToken() {
        const array = new Uint8Array(32);
        crypto.getRandomValues(array);
        return Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
    }

    /**
     * Initialize the uploader with security checks
     */
    initializeUploader() {
        // Check if browser supports required features
        if (!window.File || !window.FileReader || !window.FormData) {
            this.showError('Your browser does not support file uploads. Please update your browser.');
            return;
        }

        // Check if crypto API is available
        if (!window.crypto || !window.crypto.getRandomValues) {
            this.showError('Secure random number generation is not available. Please use a modern browser.');
            return;
        }

        this.dropzone = document.getElementById('dropzone');
        this.fileInput = this.createFileInput();
        this.progressContainer = this.createProgressContainer();
        
        if (!this.dropzone) {
            console.error('Dropzone element not found');
            return;
        }
    }

    /**
     * Create hidden file input with security attributes
     */
    createFileInput() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = this.allowedTypes.join(',');
        input.style.display = 'none';
        input.setAttribute('data-security-token', this.csrfToken);
        document.body.appendChild(input);
        return input;
    }

    /**
     * Create progress container for upload feedback
     */
    createProgressContainer() {
        const container = document.createElement('div');
        container.className = 'upload-progress-container';
        container.style.display = 'none';
        container.innerHTML = `
            <div class="upload-progress">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <div class="progress-text">Preparing upload...</div>
                <div class="progress-details"></div>
            </div>
        `;
        return container;
    }

    /**
     * Setup all event listeners
     */
    setupEventListeners() {
        if (!this.dropzone) return;

        // Dropzone events
        this.dropzone.addEventListener('click', () => this.fileInput.click());
        this.dropzone.addEventListener('dragover', this.handleDragOver.bind(this));
        this.dropzone.addEventListener('dragleave', this.handleDragLeave.bind(this));
        this.dropzone.addEventListener('drop', this.handleDrop.bind(this));

        // File input change
        this.fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.handleFileSelect(e.target.files[0]);
            }
        });

        // File browser button
        const fileBrowser = document.querySelector('.file-browser');
        if (fileBrowser) {
            fileBrowser.addEventListener('click', (e) => {
                e.stopPropagation();
                this.fileInput.click();
            });
        }

        // Prevent default drag behaviors on document
        document.addEventListener('dragover', (e) => e.preventDefault());
        document.addEventListener('drop', (e) => e.preventDefault());
    }

    /**
     * Setup security measures
     */
    setupSecurityMeasures() {
        // Rate limiting
        this.resetRateLimit();
        setInterval(() => this.resetRateLimit(), this.rateLimitWindow);

        // Content Security Policy check
        this.checkCSP();

        // Secure headers validation
        this.validateSecureHeaders();
    }

    /**
     * Reset rate limiting counter
     */
    resetRateLimit() {
        this.rateLimitCount = 0;
    }

    /**
     * Check Content Security Policy
     */
    checkCSP() {
        const meta = document.querySelector('meta[http-equiv="Content-Security-Policy"]');
        if (!meta) {
            console.warn('Content Security Policy not found. Consider adding CSP headers for enhanced security.');
        }
    }

    /**
     * Validate secure headers
     */
    validateSecureHeaders() {
        // This would typically be done server-side, but we can check some client-side indicators
        if (location.protocol !== 'https:' && location.hostname !== 'localhost') {
            console.warn('Connection is not secure. File uploads should use HTTPS.');
        }
    }

    /**
     * Handle drag over event
     */
    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        this.dropzone.classList.add('dragover');
        
        // Validate drag data types
        const items = e.dataTransfer.items;
        let hasValidFile = false;
        
        for (let item of items) {
            if (item.kind === 'file' && this.allowedTypes.includes(item.type)) {
                hasValidFile = true;
                break;
            }
        }
        
        if (!hasValidFile) {
            e.dataTransfer.dropEffect = 'none';
            this.dropzone.classList.add('invalid-drag');
        } else {
            e.dataTransfer.dropEffect = 'copy';
            this.dropzone.classList.remove('invalid-drag');
        }
    }

    /**
     * Handle drag leave event
     */
    handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Only remove classes if we're actually leaving the dropzone
        if (!this.dropzone.contains(e.relatedTarget)) {
            this.dropzone.classList.remove('dragover', 'invalid-drag');
        }
    }

    /**
     * Handle drop event
     */
    handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        
        this.dropzone.classList.remove('dragover', 'invalid-drag');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            this.handleFileSelect(files[0]);
        }
    }

    /**
     * Handle file selection with comprehensive validation
     */
    async handleFileSelect(file) {
        try {
            // Rate limiting check
            if (this.rateLimitCount >= this.maxUploadsPerWindow) {
                throw new Error('Too many upload attempts. Please wait before trying again.');
            }
            this.rateLimitCount++;

            // Comprehensive file validation
            await this.validateFile(file);
            
            // Show progress container
            this.showProgress();
            
            // Upload the file
            await this.uploadFile(file);
            
        } catch (error) {
            this.showError(error.message);
            this.hideProgress();
        }
    }

    /**
     * Comprehensive file validation
     */
    async validateFile(file) {
        // Basic checks
        if (!file) {
            throw new Error('No file selected.');
        }

        // File size check
        if (file.size > this.maxFileSize) {
            throw new Error(`File size exceeds the maximum limit of ${this.formatFileSize(this.maxFileSize)}.`);
        }

        if (file.size === 0) {
            throw new Error('File appears to be empty.');
        }

        // File type validation
        if (!this.allowedTypes.includes(file.type)) {
            throw new Error('Invalid file type. Only PDF files are allowed.');
        }

        // File extension validation
        const extension = this.getFileExtension(file.name);
        if (!this.allowedExtensions.includes(extension)) {
            throw new Error('Invalid file extension. Only .pdf files are allowed.');
        }

        // File name validation
        this.validateFileName(file.name);

        // Magic number validation (PDF signature)
        await this.validatePDFSignature(file);

        // Malware scan simulation (in real implementation, this would be server-side)
        await this.simulateMalwareScan(file);
    }

    /**
     * Validate file name for security
     */
    validateFileName(fileName) {
        // Check for dangerous characters
        const dangerousChars = /[<>:"/\\|?*\x00-\x1f]/;
        if (dangerousChars.test(fileName)) {
            throw new Error('File name contains invalid characters.');
        }

        // Check for reserved names (Windows)
        const reservedNames = /^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(\.|$)/i;
        if (reservedNames.test(fileName)) {
            throw new Error('File name is reserved and cannot be used.');
        }

        // Check length
        if (fileName.length > 255) {
            throw new Error('File name is too long.');
        }

        // Check for hidden files
        if (fileName.startsWith('.')) {
            throw new Error('Hidden files are not allowed.');
        }
    }

    /**
     * Validate PDF file signature (magic numbers)
     */
    async validatePDFSignature(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const arrayBuffer = e.target.result;
                const uint8Array = new Uint8Array(arrayBuffer.slice(0, 8));
                
                // PDF signature: %PDF-
                const pdfSignature = [0x25, 0x50, 0x44, 0x46, 0x2D];
                const fileSignature = Array.from(uint8Array.slice(0, 5));
                
                const isValidPDF = pdfSignature.every((byte, index) => byte === fileSignature[index]);
                
                if (!isValidPDF) {
                    reject(new Error('File does not appear to be a valid PDF document.'));
                } else {
                    resolve();
                }
            };
            reader.onerror = () => reject(new Error('Failed to read file signature.'));
            reader.readAsArrayBuffer(file.slice(0, 8));
        });
    }

    /**
     * Simulate malware scanning (placeholder for real implementation)
     */
    async simulateMalwareScan(file) {
        // In a real implementation, this would involve server-side scanning
        // For now, we'll just add a delay to simulate the process
        await new Promise(resolve => setTimeout(resolve, 500));
        
        // Simulate random malware detection (very low probability for demo)
        if (Math.random() < 0.001) {
            throw new Error('File failed security scan. Please ensure your file is clean and try again.');
        }
    }

    /**
     * Get file extension
     */
    getFileExtension(fileName) {
        return fileName.toLowerCase().substring(fileName.lastIndexOf('.'));
    }

    /**
     * Format file size for display
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Show progress container
     */
    showProgress() {
        if (this.dropzone && this.progressContainer) {
            this.dropzone.style.display = 'none';
            this.dropzone.parentNode.appendChild(this.progressContainer);
            this.progressContainer.style.display = 'block';
        }
    }

    /**
     * Hide progress container
     */
    hideProgress() {
        if (this.progressContainer) {
            this.progressContainer.style.display = 'none';
        }
        if (this.dropzone) {
            this.dropzone.style.display = 'block';
        }
    }

    /**
     * Update progress
     */
    updateProgress(percent, text, details = '') {
        const progressFill = this.progressContainer?.querySelector('.progress-fill');
        const progressText = this.progressContainer?.querySelector('.progress-text');
        const progressDetails = this.progressContainer?.querySelector('.progress-details');
        
        if (progressFill) {
            progressFill.style.width = `${percent}%`;
        }
        if (progressText) {
            progressText.textContent = text;
        }
        if (progressDetails) {
            progressDetails.textContent = details;
        }
    }

    /**
     * Upload file with enhanced security
     */
    async uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);
        
        // Add optional fields
        const deviceToken = document.getElementById('device-token')?.value;
        const password = document.getElementById('upload-password')?.value;
        
        if (deviceToken) {
            formData.append('device_token', this.sanitizeInput(deviceToken));
        }
        
        if (password) {
            formData.append('password', password);
        }
        
        // Add security token
        formData.append('csrf_token', this.csrfToken);
        formData.append('timestamp', Date.now().toString());
        
        try {
            this.updateProgress(10, 'Initializing secure upload...', 'Preparing encrypted connection');
            
            const response = await fetch(this.uploadEndpoint, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfToken
                },
                credentials: 'same-origin'
            });

            this.updateProgress(50, 'Processing file...', 'Server is analyzing your document');

            if (!response.ok) {
                const errorText = await response.text();
                let errorMessage = 'Upload failed. Please try again.';
                
                try {
                    const errorData = JSON.parse(errorText);
                    errorMessage = errorData.message || errorMessage;
                } catch (e) {
                    // If response is not JSON, use default message
                }
                
                throw new Error(errorMessage);
            }

            const result = await response.json();
            
            this.updateProgress(100, 'Upload complete!', 'Your payslip has been processed successfully');
            
            // Show success state
            setTimeout(() => {
                this.showSuccess(result);
            }, 1000);

        } catch (error) {
            console.error('Upload error:', error);
            throw new Error(error.message || 'Upload failed. Please check your connection and try again.');
        }
    }

    /**
     * Sanitize user input
     */
    sanitizeInput(input) {
        if (typeof input !== 'string') return '';
        
        // Remove potentially dangerous characters
        return input
            .replace(/[<>'"&]/g, '')
            .replace(/javascript:/gi, '')
            .replace(/data:/gi, '')
            .trim()
            .substring(0, 100); // Limit length
    }

    /**
     * Show success state with QR code and deep link
     */
    showSuccess(result) {
        const successHTML = `
            <div class="upload-success">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3>Upload Successful!</h3>
                <p>Your payslip has been securely uploaded and processed.</p>
                
                <div class="success-details">
                    <div class="detail-item">
                        <span class="label">File:</span>
                        <span class="value">${this.escapeHtml(result.filename || 'Unknown')}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Size:</span>
                        <span class="value">${this.formatFileSize(result.fileSize || 0)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="label">Upload ID:</span>
                        <span class="value">${this.escapeHtml(result.uploadId || 'Unknown')}</span>
                    </div>
                </div>

                ${result.qrCode ? `
                    <div class="qr-section">
                        <h4>Scan to access in PayslipMax app:</h4>
                        <div class="qr-code">
                            <img src="${this.escapeHtml(result.qrCode)}" alt="QR Code" />
                        </div>
                        <p class="qr-help">Open PayslipMax app and scan this QR code to view your payslip</p>
                    </div>
                ` : ''}

                <div class="success-actions">
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-plus"></i>
                        Upload Another
                    </button>
                    ${result.deepLink ? `
                        <a href="${this.escapeHtml(result.deepLink)}" class="btn btn-secondary">
                            <i class="fas fa-mobile-alt"></i>
                            Open in App
                        </a>
                    ` : ''}
                </div>
            </div>
        `;

        this.progressContainer.innerHTML = successHTML;
    }

    /**
     * Show error message
     */
    showError(message) {
        const errorHTML = `
            <div class="upload-error">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Upload Failed</h3>
                <p>${this.escapeHtml(message)}</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-redo"></i>
                    Try Again
                </button>
            </div>
        `;

        if (this.progressContainer && this.progressContainer.style.display !== 'none') {
            this.progressContainer.innerHTML = errorHTML;
        } else {
            // Show error in dropzone
            const errorDiv = document.createElement('div');
            errorDiv.className = 'upload-error-inline';
            errorDiv.innerHTML = `
                <div class="error-content">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>${this.escapeHtml(message)}</span>
                    <button class="error-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            if (this.dropzone) {
                this.dropzone.parentNode.insertBefore(errorDiv, this.dropzone);
            }
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize uploader when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new PayslipUploader();
});

// Additional security measures
(function() {
    'use strict';
    
    // Prevent console access in production
    if (location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
        console.log = console.warn = console.error = function() {};
    }
    
    // Detect developer tools
    let devtools = {
        open: false,
        orientation: null
    };
    
    setInterval(() => {
        if (window.outerHeight - window.innerHeight > 200 || window.outerWidth - window.innerWidth > 200) {
            if (!devtools.open) {
                devtools.open = true;
                console.warn('Developer tools detected. Please ensure you are on the official PayslipMax website.');
            }
        } else {
            devtools.open = false;
        }
    }, 500);
    
    // Prevent right-click context menu in production
    if (location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            return false;
        });
    }
    
    // Prevent common keyboard shortcuts for developer tools
    document.addEventListener('keydown', (e) => {
        if (location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            if (e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                (e.ctrlKey && e.shiftKey && e.key === 'C') ||
                (e.ctrlKey && e.shiftKey && e.key === 'J') ||
                (e.ctrlKey && e.key === 'U')) {
                e.preventDefault();
                return false;
            }
        }
    });
})(); 