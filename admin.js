/**
 * PayCampus Admin Dashboard - JavaScript
 * Handles search, filter, sort, and interactive features
 */

// ====================== //
// Global Variables       //
// ====================== //
let currentView = 'table';
let sortOrder = {};

// ====================== //
// DOM Elements           //
// ====================== //
const searchInput = document.getElementById('searchInput');
const courseFilter = document.getElementById('courseFilter');
const yearFilter = document.getElementById('yearFilter');
const statusFilter = document.getElementById('statusFilter');
const tableViewBtn = document.getElementById('tableViewBtn');
const cardViewBtn = document.getElementById('cardViewBtn');
const tableView = document.getElementById('tableView');
const cardView = document.getElementById('cardView');
const noResults = document.getElementById('noResults');
const noResultsCard = document.getElementById('noResultsCard');
const deleteModal = document.getElementById('deleteModal');
const closeModalBtn = document.querySelector('.close-modal');
const cancelDeleteBtn = document.getElementById('cancelDelete');

// ====================== //
// Initialize on Load     //
// ====================== //
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    initializeSorting();
    
    // Show success message if student was deleted
    if (window.location.search.includes('deleted=success')) {
        showNotification('Student deleted successfully', 'success');
        // Remove query parameter from URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

// ====================== //
// Initialize Listeners   //
// ====================== //
function initializeEventListeners() {
    // Search functionality with debounce
    searchInput.addEventListener('input', debounce(filterStudents, 300));
    
    // Filter functionality
    courseFilter.addEventListener('change', filterStudents);
    yearFilter.addEventListener('change', filterStudents);
    statusFilter.addEventListener('change', filterStudents);
    
    // View toggle buttons
    tableViewBtn.addEventListener('click', () => switchView('table'));
    cardViewBtn.addEventListener('click', () => switchView('card'));
    
    // Modal close events
    closeModalBtn.addEventListener('click', closeDeleteModal);
    cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    
    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === deleteModal) {
            closeDeleteModal();
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', handleKeyboardShortcuts);
}

// ====================== //
// Debounce Function      //
// ====================== //
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ====================== //
/* Filter Students        */
// ====================== //
function filterStudents() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    const courseValue = courseFilter.value.toLowerCase();
    const yearValue = yearFilter.value;
    const statusValue = statusFilter.value;
    
    let visibleCount = 0;
    
    // Filter table rows
    const tableRows = document.querySelectorAll('#studentsTableBody tr');
    tableRows.forEach(row => {
        const fullname = row.dataset.fullname.toLowerCase();
        const email = row.dataset.email.toLowerCase();
        const course = row.dataset.course.toLowerCase();
        const year = row.dataset.year;
        const status = row.dataset.status;
        
        // Check all filter conditions
        const matchesSearch = searchTerm === '' || 
                            fullname.includes(searchTerm) || 
                            email.includes(searchTerm) || 
                            course.includes(searchTerm);
        const matchesCourse = courseValue === '' || course === courseValue;
        const matchesYear = yearValue === '' || year === yearValue;
        const matchesStatus = statusValue === '' || status === statusValue;
        
        if (matchesSearch && matchesCourse && matchesYear && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Filter cards
    const cards = document.querySelectorAll('.student-card');
    let visibleCardCount = 0;
    cards.forEach(card => {
        const fullname = card.dataset.fullname.toLowerCase();
        const email = card.dataset.email.toLowerCase();
        const course = card.dataset.course.toLowerCase();
        const year = card.dataset.year;
        const status = card.dataset.status;
        
        const matchesSearch = searchTerm === '' || 
                            fullname.includes(searchTerm) || 
                            email.includes(searchTerm) || 
                            course.includes(searchTerm);
        const matchesCourse = courseValue === '' || course === courseValue;
        const matchesYear = yearValue === '' || year === yearValue;
        const matchesStatus = statusValue === '' || status === statusValue;
        
        if (matchesSearch && matchesCourse && matchesYear && matchesStatus) {
            card.style.display = '';
            visibleCardCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    if (currentView === 'table') {
        noResults.style.display = visibleCount === 0 ? 'block' : 'none';
    } else {
        noResultsCard.style.display = visibleCardCount === 0 ? 'block' : 'none';
    }
    
    // Update statistics based on visible students
    updateStatistics();
}

// ====================== //
// Update Statistics      //
// ====================== //
function updateStatistics() {
    let visibleStudents = [];
    
    if (currentView === 'table') {
        const visibleRows = Array.from(document.querySelectorAll('#studentsTableBody tr'))
            .filter(row => row.style.display !== 'none');
        visibleStudents = visibleRows.map(row => ({
            status: row.dataset.status,
            dueFees: parseInt(row.dataset.dueFees) || 0
        }));
    } else {
        const visibleCards = Array.from(document.querySelectorAll('.student-card'))
            .filter(card => card.style.display !== 'none');
        visibleStudents = visibleCards.map(card => ({
            status: card.dataset.status,
            dueFees: parseInt(card.dataset.dueFees) || 0
        }));
    }
    
    const totalCount = visibleStudents.length;
    const paidCount = visibleStudents.filter(s => s.status === 'paid').length;
    const pendingCount = visibleStudents.filter(s => s.status === 'pending').length;
    const totalDue = visibleStudents.reduce((sum, s) => sum + s.dueFees, 0);
    
    // Only update if filtering is active
    const isFiltering = searchInput.value !== '' || 
                       courseFilter.value !== '' || 
                       yearFilter.value !== '' || 
                       statusFilter.value !== '';
    
    if (isFiltering) {
        document.getElementById('total-students').textContent = totalCount;
        document.getElementById('paid-students').textContent = paidCount;
        document.getElementById('pending-students').textContent = pendingCount;
        document.getElementById('total-due').textContent = '₹' + totalDue.toLocaleString('en-IN');
    }
}

// ====================== //
// Switch View            //
// ====================== //
function switchView(view) {
    currentView = view;
    
    if (view === 'table') {
        tableView.style.display = 'block';
        cardView.style.display = 'none';
        tableViewBtn.classList.add('active');
        cardViewBtn.classList.remove('active');
    } else {
        tableView.style.display = 'none';
        cardView.style.display = 'grid';
        cardViewBtn.classList.add('active');
        tableViewBtn.classList.remove('active');
    }
    
    // Re-apply filters and show/hide appropriate no results message
    filterStudents();
}

// ====================== //
// Initialize Sorting     //
// ====================== //
function initializeSorting() {
    const sortHeaders = document.querySelectorAll('th[data-sort]');
    
    sortHeaders.forEach(header => {
        const column = header.dataset.sort;
        sortOrder[column] = 'asc';
        
        header.addEventListener('click', () => {
            sortTable(column);
            sortOrder[column] = sortOrder[column] === 'asc' ? 'desc' : 'asc';
            updateSortIcons(header, sortOrder[column]);
        });
    });
}

// ====================== //
// Sort Table             //
// ====================== //
function sortTable(column) {
    const tbody = document.getElementById('studentsTableBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const order = sortOrder[column];
    
    rows.sort((a, b) => {
        let aValue, bValue;
        
        switch(column) {
            case 'fullname':
                aValue = a.dataset.fullname.toLowerCase();
                bValue = b.dataset.fullname.toLowerCase();
                break;
            case 'coursename':
                aValue = a.dataset.course.toLowerCase();
                bValue = b.dataset.course.toLowerCase();
                break;
            case 'total_fees':
                aValue = parseInt(a.dataset.totalFees) || 0;
                bValue = parseInt(b.dataset.totalFees) || 0;
                break;
            case 'due_fees':
                aValue = parseInt(a.dataset.dueFees) || 0;
                bValue = parseInt(b.dataset.dueFees) || 0;
                break;
            case 'status':
                aValue = a.dataset.status;
                bValue = b.dataset.status;
                break;
            default:
                return 0;
        }
        
        if (aValue < bValue) return order === 'asc' ? -1 : 1;
        if (aValue > bValue) return order === 'asc' ? 1 : -1;
        return 0;
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

// ====================== //
// Update Sort Icons      //
// ====================== //
function updateSortIcons(activeHeader, order) {
    // Reset all sort icons
    document.querySelectorAll('th[data-sort] i').forEach(icon => {
        icon.className = 'fas fa-sort';
    });
    
    // Update active header icon
    const icon = activeHeader.querySelector('i');
    if (order === 'asc') {
        icon.className = 'fas fa-sort-up';
    } else {
        icon.className = 'fas fa-sort-down';
    }
}

// ====================== //
// Delete Student Modal   //
// ====================== //
function deleteStudent(id, name) {
    document.getElementById('studentNameModal').textContent = name;
    document.getElementById('deleteStudentId').value = id;
    deleteModal.style.display = 'block';
}

// Make deleteStudent available globally
window.deleteStudent = deleteStudent;

// ====================== //
// Close Delete Modal     //
// ====================== //
function closeDeleteModal() {
    deleteModal.style.display = 'none';
}

// ====================== //
// Keyboard Shortcuts     //
// ====================== //
function handleKeyboardShortcuts(e) {
    // Ctrl/Cmd + K to focus search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        searchInput.focus();
        searchInput.select();
    }
    
    // Escape to close modal
    if (e.key === 'Escape' && deleteModal.style.display === 'block') {
        closeDeleteModal();
    }
    
    // Ctrl/Cmd + 1 for table view
    if ((e.ctrlKey || e.metaKey) && e.key === '1') {
        e.preventDefault();
        switchView('table');
    }
    
    // Ctrl/Cmd + 2 for card view
    if ((e.ctrlKey || e.metaKey) && e.key === '2') {
        e.preventDefault();
        switchView('card');
    }
}

// ====================== //
// Show Notification      //
// ====================== //
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#10b981' : '#ef4444'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        z-index: 3000;
        display: flex;
        align-items: center;
        gap: 0.8rem;
        font-weight: 500;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ====================== //
// Add CSS Animations     //
// ====================== //
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { 
            transform: translateX(400px); 
            opacity: 0; 
        }
        to { 
            transform: translateX(0); 
            opacity: 1; 
        }
    }
    
    @keyframes slideOut {
        from { 
            transform: translateX(0); 
            opacity: 1; 
        }
        to { 
            transform: translateX(400px); 
            opacity: 0; 
        }
    }
`;
document.head.appendChild(style);