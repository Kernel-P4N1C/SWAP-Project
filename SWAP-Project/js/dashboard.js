// Dropdown Menu
const dropdownBtn = document.querySelector('.dropdown-btn');
const usernameDropdown = document.querySelector('.username-dropdown');

// Rotate the caret on hover
dropdownBtn.addEventListener('mouseover', () => {
    dropdownBtn.querySelector('.caret').style.transform = 'rotate(90deg)';
});
dropdownBtn.addEventListener('mouseout', () => {
    if (!usernameDropdown.classList.contains('open')) {
        dropdownBtn.querySelector('.caret').style.transform = 'rotate(0deg)';
    }
});

// Toggle the dropdown menu on click
dropdownBtn.addEventListener('click', (e) => {
    e.preventDefault();
    usernameDropdown.classList.toggle('open');

    // Ensure caret stays rotated if menu is open
    const caret = dropdownBtn.querySelector('.caret');
    if (usernameDropdown.classList.contains('open')) {
        caret.style.transform = 'rotate(90deg)';
    } else {
        caret.style.transform = 'rotate(0deg)';
    }
});

// Close the dropdown menu when clicking outside
document.addEventListener('click', (e) => {
    if (!usernameDropdown.contains(e.target) && e.target !== dropdownBtn) {
        usernameDropdown.classList.remove('open');
        dropdownBtn.querySelector('.caret').style.transform = 'rotate(0deg)';
    }
});

// Tab Navigation
document.querySelectorAll('.tab-link').forEach((tabLink) => {
    tabLink.addEventListener('click', (e) => {
        e.preventDefault();

        // Remove active state from all tabs and contents
        document.querySelectorAll('.tab-link').forEach((link) => link.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach((content) => content.classList.remove('active'));

        // Add active state to clicked tab and corresponding content
        tabLink.classList.add('active');
        const activeTabContent = document.querySelector(tabLink.getAttribute('href'));
        activeTabContent.classList.add('active');

        // Save active tab in localStorage
        localStorage.setItem('activeTab', tabLink.getAttribute('href'));
    });
});

// Persist Active Tab on Page Reload
const activeTab = localStorage.getItem('activeTab');
if (activeTab) {
    document.querySelectorAll('.tab-link').forEach((link) => link.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach((content) => content.classList.remove('active'));

    const activeTabLink = document.querySelector(`.tab-link[href="${activeTab}"]`);
    const activeTabContent = document.querySelector(activeTab);

    if (activeTabLink && activeTabContent) {
        activeTabLink.classList.add('active');
        activeTabContent.classList.add('active');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('button_delete')) {
            const button = event.target;
            const assignmentId = button.dataset.assignmentId;

            if (confirm('Are you sure you want to delete this assignment?')) {
                fetch('facility_manager_dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        ajax_delete: true,
                        assignment_id: assignmentId,
                    }),
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            const row = button.closest('tr');
                            row.remove();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while processing the request.');
                    });
            }
        }
    });
});
