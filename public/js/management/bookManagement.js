/**
 * Unified Book Management JS
 * Role-agnostic logic for handling Book CRUD, Bulk Import.
 */

let currentPage = 1;
let currentSearch = '';
let currentCampus = 0;
let currentStatus = '';

const API_BASE = `${BASE_URL_JS}/api/bookManagement`;

document.addEventListener('DOMContentLoaded', () => {
    loadBooks();
    loadCampuses();
    initEventListeners();
});

function initEventListeners() {
    // Search with debounce
    const searchInput = document.getElementById('bookSearchInput');
    if (searchInput) {
        let timeout = null;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                currentSearch = e.target.value;
                currentPage = 1;
                loadBooks();
            }, 500);
        });
    }

    // Modal Toggles
    setupModal('openAddBookBtn', 'addBookModal', 'closeAddBookModal', 'cancelAddBook');
    setupModal(null, 'editBookModal', 'closeEditBookModal', 'cancelEditBook');
    setupModal(null, 'viewBookModal', 'closeViewModal', 'closeViewModalBtn');
    setupModal(null, 'historyModal', 'closeHistoryModal', 'closeHistoryBtn');
    setupModal('bulkImportBtn', 'importModal', 'closeImportModal', 'cancelImport');

    // Forms
    const addForm = document.getElementById('addBookForm');
    if (addForm) {
        addForm.addEventListener('submit', handleAddBook);
    }

    const editForm = document.getElementById('editBookForm');
    if (editForm) {
        editForm.addEventListener('submit', handleEditBook);
    }

    const importForm = document.getElementById('bulkImportForm');
    if (importForm) {
        importForm.addEventListener('submit', handleBulkImport);
        
        // Auto-trigger submission when a file is selected
        const csvFileInput = document.getElementById('csvFile');
        if (csvFileInput) {
            csvFileInput.addEventListener('change', () => {
                if (csvFileInput.files.length > 0) {
                    importForm.dispatchEvent(new Event('submit'));
                }
            });
        }
    }

    // Image Previews
    setupImagePreview('book_image', 'previewImage', 'previewContainer', 'uploadText');
    setupImagePreview('edit_book_image', 'editPreviewImage', 'editPreviewContainer', 'editUploadText');

    // Dropdowns (Outside click to close)
    window.addEventListener('click', (e) => {
        closeDropdowns(e);
    });

    ['campusDropdownBtn', 'statusDropdownBtn'].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const menu = document.getElementById(id.replace('Btn', 'Menu'));
                const isHidden = menu.classList.contains('hidden');
                closeDropdowns();
                if (isHidden) menu.classList.remove('hidden');
            });
        }
    });
}

function setupModal(openBtnId, modalId, closeBtnId, cancelBtnId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    if (openBtnId) {
        const openBtn = document.getElementById(openBtnId);
        if (openBtn) openBtn.onclick = () => modal.classList.remove('hidden');
    }

    const closeBtn = document.getElementById(closeBtnId);
    if (closeBtn) closeBtn.onclick = () => modal.classList.add('hidden');

    const cancelBtn = document.getElementById(cancelBtnId);
    if (cancelBtn) cancelBtn.onclick = () => modal.classList.add('hidden');
}

function setupImagePreview(inputId, imgId, containerId, textId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(imgId).src = e.target.result;
                document.getElementById(containerId).classList.remove('hidden');
                if (textId) document.getElementById(textId).textContent = "Change Image";
            }
            reader.readAsDataURL(file);
        }
    });
}

function closeDropdowns(e) {
    document.querySelectorAll('[id$="Menu"]').forEach(menu => {
        if (!e || !menu.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });
}

async function loadBooks() {
    const tbody = document.getElementById('bookTableBody');
    if (!tbody) return;

    tbody.innerHTML = `<tr><td colspan="5" class="py-10 text-center text-gray-500"><i class="ph ph-spinner animate-spin text-2xl"></i></td></tr>`;

    try {
        const params = new URLSearchParams({
            page: currentPage,
            search: currentSearch,
            campus_id: currentCampus,
            status: currentStatus
        });

        const response = await fetch(`${API_BASE}/fetch?${params}`);
        const data = await response.json();

        if (data.books && data.books.length > 0) {
            renderBooks(data.books);
            renderPagination(data.totalCount);
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="py-10 text-center text-gray-500">No books found.</td></tr>`;
            document.getElementById('paginationControls').classList.add('hidden');
        }
        
        updateResultsIndicator(data.totalCount || 0);
    } catch (error) {
        console.error('Error loading books:', error);
        tbody.innerHTML = `<tr><td colspan="5" class="py-10 text-center text-red-500">Failed to load books.</td></tr>`;
    }
}

function renderBooks(books) {
    const tbody = document.getElementById('bookTableBody');
    tbody.innerHTML = '';

    books.forEach(book => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-orange-50/50 transition-colors';
        tr.innerHTML = `
            <td class="py-3 px-4 font-medium text-gray-900">${book.title}</td>
            <td class="py-3 px-4 text-center">
                <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-md text-xs font-semibold uppercase tracking-wider">
                    ${book.campus_name || 'N/A'}
                </span>
            </td>
            <td class="py-3 px-4 text-gray-600">${book.author}</td>
            <td class="py-3 px-4 text-center">
                ${getStatusBadge(book.availability)}
            </td>
            <td class="py-3 px-4 text-center">
                <div class="flex items-center justify-center gap-2">
                    <button onclick="viewBook(${book.book_id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="View Details">
                        <i class="ph ph-eye text-lg"></i>
                    </button>
                    <button onclick="editBook(${book.book_id})" class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition" title="Edit">
                        <i class="ph ph-pencil-simple text-lg"></i>
                    </button>
                    <button onclick="viewHistory(${book.book_id})" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="Borrowing History">
                        <i class="ph ph-clock-counter-clockwise text-lg"></i>
                    </button>
                    <button onclick="deleteBook(${book.book_id}, '${book.title.replace(/'/g, "\\'")}')" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="Deactivate">
                        <i class="ph ph-trash text-lg"></i>
                    </button>
                </div>
            </td>
        `;

        tbody.appendChild(tr);
    });
}

function getStatusBadge(status) {
    const s = status.toLowerCase();
    const colors = {
        available: 'bg-green-100 text-green-700 border-green-200',
        borrowed: 'bg-blue-100 text-blue-700 border-blue-200',
        damaged: 'bg-yellow-100 text-yellow-700 border-yellow-200',
        lost: 'bg-red-100 text-red-700 border-red-200',
        inactive: 'bg-gray-100 text-gray-700 border-gray-200'
    };
    const colorClass = colors[s] || 'bg-gray-100 text-gray-700 border-gray-200';
    return `<span class="px-2.5 py-1 rounded-full text-xs font-bold border ${colorClass} uppercase tracking-wider">${status}</span>`;
}

function renderPagination(totalCount) {
    const totalPages = Math.ceil(totalCount / 10);
    const paginationList = document.getElementById('paginationList');
    const controls = document.getElementById('paginationControls');

    if (totalPages <= 1) {
        controls.classList.add('hidden');
        return;
    }

    controls.classList.remove('hidden');
    paginationList.innerHTML = '';

    // Previous
    paginationList.appendChild(createPageItem('ph-caret-left', currentPage > 1 ? currentPage - 1 : null));

    // Pages
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
            paginationList.appendChild(createPageItem(i, i, i === currentPage));
        } else if (i === currentPage - 2 || i === currentPage + 2) {
            paginationList.appendChild(createPageItem('...', null));
        }
    }

    // Next
    paginationList.appendChild(createPageItem('ph-caret-right', currentPage < totalPages ? currentPage + 1 : null));
}

function createPageItem(content, page, isActive = false) {
    const li = document.createElement('li');
    if (page === null) {
        li.className = "flex items-center justify-center w-9 h-9 text-gray-400 cursor-not-allowed";
        li.innerHTML = typeof content === 'string' && content.startsWith('ph-') ? `<i class="${content}"></i>` : content;
    } else {
        li.className = `flex items-center justify-center w-9 h-9 rounded-full cursor-pointer transition-all ${isActive ? 'bg-orange-500 text-white shadow-md font-bold' : 'text-gray-600 hover:bg-orange-100'}`;
        li.innerHTML = typeof content === 'string' && content.startsWith('ph-') ? `<i class="${content}"></i>` : content;
        li.onclick = () => {
            currentPage = page;
            loadBooks();
        };
    }
    return li;
}

function updateResultsIndicator(total) {
    const indicator = document.getElementById('resultsIndicator');
    if (indicator) {
        indicator.textContent = `Showing ${total} book${total !== 1 ? 's' : ''} total`;
    }
}

// Select Campus / Status
window.selectCampus = (el, id, name) => {
    currentCampus = id === 0 ? '' : id;
    const val = document.getElementById('campusDropdownValue');
    if (val) val.textContent = name;
    currentPage = 1;
    loadBooks();
    closeDropdowns();
};

window.selectStatus = (el, name) => {
    currentStatus = name === 'All Status' ? '' : name.toLowerCase();
    const val = document.getElementById('statusDropdownValue');
    if (val) val.textContent = name;
    currentPage = 1;
    loadBooks();
    closeDropdowns();
};

async function loadCampuses() {
    const campusMenu = document.getElementById('campusDropdownMenu');
    
    try {
        const response = await fetch(`${BASE_URL_JS}/api/campuses/active`);
        const data = await response.json();
        
        const addCampusSelect = document.querySelector('#addBookForm select[name="campus_id"]');
        const editCampusSelect = document.getElementById('edit_campus_id');

        data.forEach(campus => {
            // Dropdown filter
            if (campusMenu) {
                const div = document.createElement('div');
                div.className = "campus-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm";
                div.textContent = campus.campus_name;
                div.onclick = () => selectCampus(div, campus.campus_id, campus.campus_name);
                campusMenu.appendChild(div);
            }

            // Selects in modals
            const opt = `<option value="${campus.campus_id}">${campus.campus_name}</option>`;
            if (addCampusSelect && !addCampusSelect.disabled) addCampusSelect.innerHTML += opt;
            if (editCampusSelect && !editCampusSelect.disabled) editCampusSelect.innerHTML += opt;
        });
    } catch (e) { console.error('Error loading campuses:', e); }
}

// CRUD Actions
async function handleAddBook(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch(`${API_BASE}/store`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (response.ok) {
            Swal.fire('Success', result.message, 'success');
            e.target.reset();
            document.getElementById('addBookModal').classList.add('hidden');
            loadBooks();
        } else {
            Swal.fire('Error', result.error || 'Failed to add book', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Network error occurred', 'error');
    }
}

async function viewBook(id) {
    try {
        const response = await fetch(`${API_BASE}/details/${id}`);
        const data = await response.json();
        const book = data.book;

        document.getElementById('viewModalTitle').textContent = book.title;
        document.getElementById('viewModalAuthor').textContent = `by ${book.author}`;
        document.getElementById('viewModalStatus').textContent = book.availability;
        document.getElementById('viewModalCampus').textContent = book.campus_name || 'N/A';
        document.getElementById('viewModalAccessionNumber').textContent = book.accession_number;
        document.getElementById('viewModalCallNumber').textContent = book.call_number;
        document.getElementById('viewModalIsbn').textContent = book.book_isbn || 'N/A';
        document.getElementById('viewModalPublisher').textContent = book.book_publisher || 'N/A';
        document.getElementById('viewModalEdition').textContent = book.book_edition || 'N/A';
        document.getElementById('viewModalPlace').textContent = book.book_place || 'N/A';
        document.getElementById('viewModalSubject').textContent = book.subject || 'N/A';
        document.getElementById('viewModalYear').textContent = book.year || 'N/A';
        document.getElementById('viewModalDuration').textContent = book.borrowing_duration_override > 0 ? `${book.borrowing_duration_override} Days` : 'Global Policy';
        document.getElementById('viewModalDescription').textContent = book.description || 'No description provided.';

        const img = document.getElementById('viewModalImg');
        if (book.cover) {
            img.src = `${BASE_URL_JS}/storage/uploads/book_covers/${book.cover}`;
            img.classList.remove('hidden');
        } else {
            img.classList.add('hidden');
        }

        const modal = document.getElementById('viewBookModal');
        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.remove('opacity-0'), 10);
        document.getElementById('viewBookModalContent').classList.remove('scale-95');
    } catch (e) { Swal.fire('Error', 'Failed to load book details', 'error'); }
}

async function editBook(id) {
    try {
        const response = await fetch(`${API_BASE}/details/${id}`);
        const data = await response.json();
        const book = data.book;

        const form = document.getElementById('editBookForm');
        form.edit_book_id.value = book.book_id;
        form.accession_number.value = book.accession_number;
        form.call_number.value = book.call_number;
        form.title.value = book.title;
        form.author.value = book.author;
        form.availability.value = book.availability.toLowerCase();
        
        if (form.campus_id && !form.campus_id.disabled) {
            form.campus_id.value = book.campus_id;
        }
        
        form.book_place.value = book.book_place || '';
        form.book_publisher.value = book.book_publisher || '';
        form.book_edition.value = book.book_edition || '';
        form.book_supplementary.value = book.book_supplementary || '';
        form.subject.value = book.subject || '';
        form.borrowing_duration_override.value = book.borrowing_duration_override || 0;
        
        form.book_isbn.value = book.book_isbn || '';
        form.year.value = book.year || '';
        form.description.value = book.description || '';

        const preview = document.getElementById('editPreviewImage');
        const container = document.getElementById('editPreviewContainer');
        const removeBtn = document.getElementById('removeImageBtn');
        
        if (book.cover) {
            preview.src = `${BASE_URL_JS}/storage/uploads/book_covers/${book.cover}`;
            container.classList.remove('hidden');
            removeBtn.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
            removeBtn.classList.add('hidden');
        }

        document.getElementById('editBookModal').classList.remove('hidden');
    } catch (e) { Swal.fire('Error', 'Failed to load book details', 'error'); }
}

async function handleEditBook(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const id = formData.get('book_id');
    
    try {
        const response = await fetch(`${API_BASE}/update/${id}`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (response.ok) {
            Swal.fire('Success', result.message, 'success');
            document.getElementById('editBookModal').classList.add('hidden');
            loadBooks();
        } else {
            Swal.fire('Error', result.error || 'Failed to update book', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Network error occurred', 'error');
    }
}

async function deleteBook(id, title) {
    const result = await Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to deactivate "${title}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, deactivate it!'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(`${API_BASE}/destroy/${id}`, { method: 'POST' });
            const data = await response.json();
            if (response.ok) {
                Swal.fire('Deactivated!', data.message, 'success');
                loadBooks();
            } else {
                Swal.fire('Error', data.error, 'error');
            }
        } catch (e) { Swal.fire('Error', 'Network error', 'error'); }
    }
}

async function viewHistory(id) {
    try {
        const response = await fetch(`${API_BASE}/history/${id}`);
        const data = await response.json();
        const tbody = document.getElementById('historyTableBody');
        const emptyState = document.getElementById('historyEmptyState');
        const container = document.getElementById('historyTableContainer');

        tbody.innerHTML = '';
        if (data.history && data.history.length > 0) {
            data.history.forEach(h => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="py-3 px-5">
                        <div class="font-medium text-gray-900">${h.first_name} ${h.last_name}</div>
                        <div class="text-xs text-gray-500 font-mono">${h.identifier}</div>
                    </td>
                    <td class="py-3 px-5 uppercase text-xs font-bold text-gray-500">${h.role}</td>
                    <td class="py-3 px-5 text-gray-600">${new Date(h.borrowed_at).toLocaleDateString()}</td>
                    <td class="py-3 px-5 text-gray-600">${h.returned_at ? new Date(h.returned_at).toLocaleDateString() : '—'}</td>
                    <td class="py-3 px-5 text-center">${getStatusBadge(h.status)}</td>
                `;
                tbody.appendChild(tr);
            });
            container.classList.remove('hidden');
            emptyState.classList.add('hidden');
        } else {
            container.classList.add('hidden');
            emptyState.classList.remove('hidden');
        }

        document.getElementById('historyModal').classList.remove('hidden');
    } catch (e) { Swal.fire('Error', 'Failed to load history', 'error'); }
}

async function handleBulkImport(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    Swal.fire({
        title: 'Importing...',
        text: 'Please wait while we process the CSV file.',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch(`${API_BASE}/bulk-import`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (response.ok) {
            Swal.fire({
                title: 'Import Complete',
                html: `Successfully imported: <b>${result.imported_count}</b><br>Errors: <b>${result.error_count}</b>`,
                icon: result.error_count > 0 ? 'warning' : 'success'
            });
            document.getElementById('importModal').classList.add('hidden');
            loadBooks();
        } else {
            Swal.fire('Error', result.error || 'Failed to import books', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Network error occurred', 'error');
    }
}
