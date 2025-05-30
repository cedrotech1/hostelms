<?php
require_once 'room_list.php';
?>

<!-- Rooms Modal -->
<div class="modal fade" id="roomsModal" tabindex="-1" aria-labelledby="roomsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="roomsModalLabel">
                    <i class="bi bi-building me-2"></i>
                    <span id="modalHostelName">Loading...</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="rooms-container">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let refreshInterval;
let currentHostelId = null;
let isRefreshing = false;
let currentPage = 1;

// Handle modal show event
document.getElementById('roomsModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const hostelId = button.getAttribute('data-hostel-id');
    const hostelName = button.getAttribute('data-hostel-name');
    
    currentHostelId = hostelId;
    currentPage = 1;
    document.getElementById('modalHostelName').textContent = hostelName;
    
    // Load initial data
    loadRooms(hostelId, currentPage);
    
    // Start refresh interval - using 0.5 second interval
    refreshInterval = setInterval(() => {
        if (!isRefreshing) {
            loadRooms(hostelId, currentPage);
        }
    }, 1000); // Refresh every 1 second
});

function loadRooms(hostelId, page) {
    if (isRefreshing) return;
    
    isRefreshing = true;
    currentPage = page;
    fetch(`get_rooms.php?hostel_id=${hostelId}&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateRoomList(data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        })
        .finally(() => {
            isRefreshing = false;
        });
}

function updateRoomList(data) {
    const roomsContainer = document.getElementById('rooms-container');
    const currentContent = roomsContainer.innerHTML;
    
    let html = '<div class="room-list">';
    if (data.rooms.length === 0) {
        html = `
            <div class="alert alert-info">
                No rooms available at the moment.
            </div>
        `;
    } else {
        data.rooms.forEach(room => {
            html += `
                <div class="card room-card">
                    <div class="card-body">
                        <div class="room-info">
                            <div class="room-code">
                                <i class="bi bi-door-open me-2"></i>
                                ${room.room_code}
                            </div>
                            <div class="room-stats">
                                <div class="room-stat">
                                    <i class="bi bi-people text-primary"></i>
                                    ${room.number_of_beds} Beds
                                </div>
                                <div class="room-stat">
                                    <i class="bi bi-check-circle text-success"></i>
                                    ${room.remain} Available
                                </div>
                                
                            </div>
                            <div class="room-actions">
                                <form action="apply_room.php" method="POST" class="d-inline apply-room-form">
                                    <input type="hidden" name="room_id" value="${room.id}">
                                    <input type="hidden" name="hostel_id" value="${data.hostel_id}">
                                    <button type="submit" class="btn btn-sm btn-primary apply-btn" 
                                            ${room.remain <= 0 ? 'disabled' : ''}>
                                        <i class="bi bi-check-circle me-1"></i>
                                        Apply
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
    }
    
    // Add pagination if needed
    if (data.total_pages > 1) {
        html += `
            <div class="pagination-container mt-3">
                <nav aria-label="Room pagination">
                    <ul class="pagination justify-content-center">
        `;
        
        for (let i = 1; i <= data.total_pages; i++) {
            html += `
                <li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        html += `
                    </ul>
                </nav>
            </div>
        `;
    }
    
    // Only update if content has changed
    if (currentContent !== html) {
        roomsContainer.innerHTML = html;
        
        // Add pagination event listeners
        document.querySelectorAll('.pagination .page-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const newPage = parseInt(this.dataset.page);
                if (newPage !== currentPage) {
                    loadRooms(currentHostelId, newPage);
                }
            });
        });
    }
}

// Clean up interval when modal is closed
document.getElementById('roomsModal').addEventListener('hidden.bs.modal', function () {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
    currentHostelId = null;
    currentPage = 1;
    isRefreshing = false;
});

// Handle room application form submission
document.addEventListener('submit', function(e) {
    if (e.target.classList.contains('apply-room-form')) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('.apply-btn');
        const originalBtnText = submitBtn.innerHTML;
        
        // Disable button and show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Applying...';
        
        // Get form data
        const formData = new FormData(form);
        
        // Send AJAX request
        fetch('apply_room.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    timer: 3000,
                    timerProgressBar: true
                }).then((result) => {
                    // Redirect to index.php regardless of whether user clicked OK or timer finished
                    window.location.href = 'index.php';
                });
            } else {
                // Show error message
                Swal.fire({
                    title: 'Error!',
                    text: data.message,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while submitting your application. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    }
});
</script>

<style>
.room-list {
    max-height: 70vh;
    overflow-y: auto;
}

.room-card {
    transition: all 0.2s ease;
    border: 1px solid #dee2e6;
    margin-bottom: 0.5rem;
}

.room-card:hover {
    transform: translateX(5px);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.room-card .card-body {
    padding: 0.75rem;
}

.room-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.room-code {
    font-weight: 500;
    color: #0d6efd;
}

.room-stats {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.room-stat {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.room-stat i {
    font-size: 1rem;
}

.room-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}
</style> 