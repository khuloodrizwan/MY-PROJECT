/**
 * PayCampus Payment Portal JavaScript
 * Handles payment processing with AJAX and UI updates
 */

// Get student information from hidden inputs
const studentId = document.getElementById('studentId').value;
const studentName = document.getElementById('studentName').value;
const studentEmail = document.getElementById('studentEmail').value;
const studentPhone = document.getElementById('studentPhone').value;

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type}`;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 4000);
}

/**
 * Show/hide loading overlay
 */
function toggleLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    overlay.style.display = show ? 'flex' : 'none';
}

/**
 * Make Payment Function
 * @param {number} installmentNo - Installment number to pay
 * @param {number} amount - Amount to pay
 */
function makePayment(installmentNo, amount) {
    // Disable the button immediately to prevent double clicks
    const button = document.querySelector(`button[data-installment="${installmentNo}"]`);
    const originalButtonHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    // Show loading overlay
    toggleLoading(true);
    
    // ==========================================
    // RAZORPAY INTEGRATION POINT
    // ==========================================
    // To integrate Razorpay, replace the simulatePayment() call below with:
    // initiateRazorpayPayment(installmentNo, amount);
    // See the initiateRazorpayPayment function at the bottom of this file
    // ==========================================
    
    // For now, simulate payment
    simulatePayment(installmentNo, amount, button, originalButtonHTML);
}

/**
 * Simulate Payment (temporary function until Razorpay is integrated)
 */
function simulatePayment(installmentNo, amount, button, originalButtonHTML) {
    // Simulate a 2-second payment process
    setTimeout(() => {
        // Generate a temporary transaction ID
        const txnId = 'TMP-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        // Send payment to server
        processPaymentOnServer(installmentNo, amount, txnId, 'Simulated', button, originalButtonHTML);
    }, 2000);
}

/**
 * Process payment on server via AJAX
 */
function processPaymentOnServer(installmentNo, amount, txnId, paymentMethod, button, originalButtonHTML) {
    // Create FormData
    const formData = new FormData();
    formData.append('installment_no', installmentNo);
    formData.append('amount', amount);
    formData.append('txn_id', txnId);
    formData.append('payment_method', paymentMethod);
    
    // Send AJAX request
    fetch('update_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        toggleLoading(false);
        
        if (data.success) {
            // Update UI
            updateUIAfterPayment(installmentNo, data);
            showToast(data.message, 'success');
        } else {
            // Re-enable button on error
            button.disabled = false;
            button.innerHTML = originalButtonHTML;
            showToast(data.message || 'Payment failed. Please try again.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toggleLoading(false);
        button.disabled = false;
        button.innerHTML = originalButtonHTML;
        showToast('An error occurred. Please try again.', 'error');
    });
}

/**
 * Update UI after successful payment
 */
function updateUIAfterPayment(installmentNo, data) {
    // Update the row in installments table
    const row = document.querySelector(`tr[data-installment="${installmentNo}"]`);
    if (row) {
        row.classList.add('row-paid');
        
        // Update status badge
        const statusCell = row.querySelector('.status-badge');
        if (statusCell) {
            statusCell.className = 'status-badge status-paid';
            statusCell.innerHTML = '<i class="fas fa-check-circle"></i> Paid';
        }
        
        // Update button
        const button = row.querySelector('button');
        if (button) {
            button.className = 'btn-paid';
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-check"></i> Paid';
        }
    }
    
    // Update fee summary
    if (data.new_due_fees !== undefined) {
        const dueElement = document.querySelector('.fee-value.due');
        if (dueElement) {
            dueElement.textContent = '₹' + parseFloat(data.new_due_fees).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
    
    if (data.paid_amount !== undefined) {
        const paidElement = document.querySelector('.fee-value.paid');
        if (paidElement) {
            paidElement.textContent = '₹' + parseFloat(data.paid_amount).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
    }
    
    // Update progress bar
    if (data.payment_progress !== undefined) {
        const progressFill = document.querySelector('.progress-fill');
        const progressPercentage = document.querySelector('.progress-percentage');
        
        if (progressFill) {
            progressFill.style.width = data.payment_progress + '%';
        }
        
        if (progressPercentage) {
            progressPercentage.textContent = Math.round(data.payment_progress) + '%';
        }
    }
    
    // Add to payment history (prepend to table)
    if (data.payment_details) {
        addPaymentToHistory(data.payment_details);
    }
    
    // Reload page after 2 seconds to sync all data
    setTimeout(() => {
        window.location.reload();
    }, 2000);
}

/**
 * Add payment to history table
 */
function addPaymentToHistory(payment) {
    const historyTable = document.querySelector('.history-table tbody');
    if (!historyTable) return;
    
    // Remove "no payments" message if exists
    const noPayments = document.querySelector('.no-payments');
    if (noPayments) {
        noPayments.remove();
    }
    
    // Create new row
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${payment.date}</td>
        <td>Installment ${payment.installment_no}</td>
        <td class="amount-cell">₹${parseFloat(payment.amount).toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}</td>
        <td class="txn-id">${payment.txn_id}</td>
        <td>${payment.payment_method}</td>
        <td><span class="status-badge status-paid">Paid</span></td>
    `;
    
    // Prepend to table (show newest first)
    historyTable.insertBefore(row, historyTable.firstChild);
    
    // Add highlight animation
    row.style.backgroundColor = '#d4edda';
    setTimeout(() => {
        row.style.transition = 'background-color 1s ease';
        row.style.backgroundColor = '';
    }, 500);
}

// ==========================================
// RAZORPAY INTEGRATION FUNCTIONS
// ==========================================
// Uncomment and configure these functions when ready to integrate Razorpay

/**
 * Initiate Razorpay Payment
 * @param {number} installmentNo - Installment number
 * @param {number} amount - Amount to pay
 */
function initiateRazorpayPayment(installmentNo, amount) {
    // Razorpay configuration
    const options = {
        key: 'YOUR_RAZORPAY_KEY_ID', // Replace with your Razorpay key
        amount: amount * 100, // Amount in paise (₹1 = 100 paise)
        currency: 'INR',
        name: 'PayCampus',
        description: `Installment ${installmentNo} Payment`,
        image: '/logo.png', // Your logo URL
        
        // Prefill student details
        prefill: {
            name: studentName,
            email: studentEmail,
            contact: studentPhone
        },
        
        // Theme
        theme: {
            color: '#4a90e2'
        },
        
        // Handler for successful payment
        handler: function(response) {
            // response.razorpay_payment_id
            // response.razorpay_order_id (if using orders)
            // response.razorpay_signature (if using orders)
            
            const button = document.querySelector(`button[data-installment="${installmentNo}"]`);
            const originalButtonHTML = button.innerHTML;
            
            // Verify payment on server
            verifyRazorpayPayment(
                installmentNo,
                amount,
                response.razorpay_payment_id,
                response.razorpay_signature || '',
                button,
                originalButtonHTML
            );
        },
        
        // Handler for payment modal close
        modal: {
            ondismiss: function() {
                toggleLoading(false);
                const button = document.querySelector(`button[data-installment="${installmentNo}"]`);
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-credit-card"></i> Pay Now';
                showToast('Payment cancelled', 'warning');
            }
        }
    };
    
    // Create Razorpay instance and open
    const rzp = new Razorpay(options);
    rzp.open();
    
    // Handle payment failure
    rzp.on('payment.failed', function(response) {
        toggleLoading(false);
        const button = document.querySelector(`button[data-installment="${installmentNo}"]`);
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-credit-card"></i> Pay Now';
        
        showToast('Payment failed: ' + response.error.description, 'error');
    });
}

/**
 * Verify Razorpay payment on server
 */
function verifyRazorpayPayment(installmentNo, amount, paymentId, signature, button, originalButtonHTML) {
    toggleLoading(true);
    
    // Create FormData
    const formData = new FormData();
    formData.append('installment_no', installmentNo);
    formData.append('amount', amount);
    formData.append('txn_id', paymentId);
    formData.append('payment_method', 'Razorpay');
    formData.append('razorpay_signature', signature);
    
    // Send to server for verification
    fetch('update_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        toggleLoading(false);
        
        if (data.success) {
            updateUIAfterPayment(installmentNo, data);
            showToast('Payment successful!', 'success');
        } else {
            button.disabled = false;
            button.innerHTML = originalButtonHTML;
            showToast(data.message || 'Payment verification failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        toggleLoading(false);
        button.disabled = false;
        button.innerHTML = originalButtonHTML;
        showToast('An error occurred during verification', 'error');
    });
}

// ==========================================
// END RAZORPAY INTEGRATION
// ==========================================

// Add smooth scrolling for any anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// Log page load
console.log('PayCampus Payment Portal loaded successfully');
console.log('Student ID:', studentId);