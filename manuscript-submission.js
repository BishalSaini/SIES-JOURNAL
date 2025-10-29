document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.getElementById('manuscript-upload-form');
    const fileNameDisplay = document.getElementById('file-name-display');
    const fileInput = document.getElementById('manuscript-file');

    // File selection update
    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                fileNameDisplay.textContent = this.files[0].name;
                fileNameDisplay.classList.add('text-green-600');
            } else {
                fileNameDisplay.textContent = 'No file selected.';
                fileNameDisplay.classList.remove('text-green-600');
            }
        });
    }

    // Form submission handler
    if (uploadForm) {
        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Get submit button and message element
            const submitBtn = document.getElementById('manuscript-submit-btn');
            const messageEl = document.getElementById('upload-form-message');
            
            // Disable submit button and show loading
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Submitting...';
            }
            
            // Clear previous messages
            if (messageEl) {
                messageEl.classList.add('hidden');
                messageEl.classList.remove('text-green-600', 'text-red-500');
            }
            
            try {
                const formData = new FormData(uploadForm);
                
                let submitPath = 'submit_manuscript.php';
                if (window.location.pathname.includes('/')) {
                    submitPath = '../submit_manuscript.php';
                }
                
                const response = await fetch(submitPath, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (messageEl) {
                        messageEl.textContent = result.message || 'Manuscript submitted successfully!';
                        messageEl.classList.remove('hidden', 'text-red-500');
                        messageEl.classList.add('text-green-600');
                    }
                    
                    uploadForm.reset();
                    if (fileNameDisplay) {
                        fileNameDisplay.textContent = 'No file selected.';
                        fileNameDisplay.classList.remove('text-green-600');
                    }
                    
                    setTimeout(() => { 
                        if (messageEl) messageEl.classList.add('hidden'); 
                        closeModal('manuscriptFormModal');
                    }, 3000);
                    
                } else {
                    if (messageEl) {
                        messageEl.textContent = result.message || 'Submission failed. Please try again.';
                        messageEl.classList.remove('hidden', 'text-green-600');
                        messageEl.classList.add('text-red-500');
                    }
                }
                
            } catch (error) {
                console.error('Submission error:', error);
                if (messageEl) {
                    messageEl.textContent = 'Network error. Please check your connection and try again.';
                    messageEl.classList.remove('hidden', 'text-green-600');
                    messageEl.classList.add('text-red-500');
                }
            }
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit Manuscript';
            }
        });
    }
    if (typeof openModal === 'undefined') {
        window.openModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('invisible', 'opacity-0');
                document.body.style.overflow = 'hidden';
            }
        };
    }

    if (typeof closeModal === 'undefined') {
        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('opacity-0');
                setTimeout(() => {
                    modal.classList.add('invisible');
                    document.body.style.overflow = '';
                }, 300);
            }
        };
    }

    window.addEventListener('click', function(event) {
        const modal = document.getElementById('manuscriptFormModal');
        if (modal && event.target === modal) {
            closeModal('manuscriptFormModal');
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal('manuscriptFormModal');
        }
    });
});