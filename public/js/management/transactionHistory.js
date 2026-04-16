// --- API Base Configuration ---
const API_BASE = typeof API_BASE_PATH !== 'undefined' ? API_BASE_PATH : `${BASE_URL_JS}/api/transactionHistory/getTableData`;

// --- SweetAlert Helper Functions ---
function showErrorToast(title, body = "An error occurred during processing.") {
    if (typeof Swal == "undefined") return alert(title);
    Swal.fire({
        toast: true,
        position: "bottom-end",
        showConfirmButton: false,
        timer: 4000,
        width: "360px",
        background: "transparent",
        html: `<div class="flex flex-col text-left"><div class="flex items-center gap-3 mb-2"><div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600"><i class="ph ph-x-circle text-lg"></i></div><div><h3 class="text-[15px] font-semibold text-red-600">${title}</h3><p class="text-[13px] text-gray-700 mt-0.5">${body}</p></div></div></div>`,
        customClass: { popup: "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]" },
    });
}

function showLoadingModal(message = "Processing request...", subMessage = "Please wait.") {
    if (typeof Swal == "undefined") return;
    Swal.fire({
        background: "transparent",
        html: `<div class="flex flex-col items-center justify-center gap-2"><div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div><p class="text-gray-700 text-[14px]">${message}<br><span class="text-sm text-gray-500">${subMessage}</span></p></div>`,
        allowOutsideClick: false,
        showConfirmButton: false,
        customClass: { popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]" },
    });
}

// --- Filter Actions ---
window.clearAllFilters = function() {
    const searchInput = document.getElementById('transactionSearchInput');
    const dateInput = document.getElementById('transactionDate');
    const statusValue = document.getElementById('statusFilterValue');
    
    // Get Today's Date in YYYY-MM-DD format
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const formattedToday = `${year}-${month}-${day}`;

    if (searchInput) searchInput.value = '';
    if (dateInput) dateInput.value = formattedToday;
    if (statusValue) statusValue.textContent = 'All Status';
    
    // Dispatch change event to trigger filtering
    if (dateInput) {
        const event = new Event('change');
        dateInput.dispatchEvent(event);
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const statusBtn = document.getElementById('statusFilterBtn');
    const statusMenu = document.getElementById('statusFilterMenu');
    const statusValue = document.getElementById('statusFilterValue');
    const tableBody = document.getElementById('transactionHistoryTableBody');
    const rowTemplate = document.getElementById('transaction-row-template');
    const noTransactionsTemplate = document.getElementById('no-transactions-template');
    const paginationContainer = document.getElementById('pagination-container');
    const paginationNumbers = document.getElementById('pagination-numbers');
    const prevPageBtn = document.getElementById('prev-page');
    const nextPageBtn = document.getElementById('next-page');
    const modal = document.getElementById('transactionDetailsModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const searchInput = document.getElementById('transactionSearchInput');
    const dateInput = document.getElementById('transactionDate');

    let currentPage = 1;
    const rowsPerPage = 5;
    let allTransactions = [];
    let currentFilteredTransactions = [];

    // --- INITIALIZE DATE TO TODAY (LOCAL) ---
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const formattedToday = `${year}-${month}-${day}`;
    if (dateInput) dateInput.value = formattedToday;

    async function loadTransactions() {
        showLoadingModal("Loading records...", "Synchronizing data.");
        if (tableBody) tableBody.innerHTML = '';
        if (paginationContainer) paginationContainer.classList.add('hidden');
        
        try {
            const response = await fetch(API_BASE);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            
            const rawData = await response.json();
            
            // Convert Object with numeric keys to Array
            let transactionsArray = [];
            if (Array.isArray(rawData)) {
                transactionsArray = rawData;
            } else {
                const source = rawData.data || rawData.list || rawData;
                transactionsArray = Object.values(source).filter(val => val && typeof val === 'object' && (val.transaction_id || val.id));
            }
            
            allTransactions = transactionsArray;
            if (typeof Swal != 'undefined') Swal.close();
            applyAndRenderFilters();
            
        } catch (error) {
            console.error('Error:', error);
            if (typeof Swal != 'undefined') Swal.close();
            showErrorToast('Data Load Failed', 'Could not retrieve transaction history.');
            renderEmptyState("Error loading history. Please try again.");
        }
    }

    function applyAndRenderFilters() {
        const status = statusValue ? statusValue.textContent.trim() : 'All Status';
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const selectedDate = dateInput ? dateInput.value : '';

        currentFilteredTransactions = allTransactions.filter(t => {
            // Status Match
            const statusMatch = status === 'All Status' || 
                (t.transaction_status && t.transaction_status.toLowerCase() === status.toLowerCase());
            
            // Search Match
            const borrowerName = (t.studentName || `${t.first_name || ''} ${t.last_name || ''}`).toLowerCase();
            const borrowerId = (t.student_number || t.unique_faculty_id || t.employee_id || t.guest_id || '').toString().toLowerCase();
            const itemName = (t.item_name || '').toLowerCase();
            const accession = (t.accession_number || '').toLowerCase();
            
            const searchMatch = !searchTerm || 
                borrowerName.includes(searchTerm) || 
                borrowerId.includes(searchTerm) || 
                itemName.includes(searchTerm) || 
                accession.includes(searchTerm);
                
            // Date Match (Sa borrowed_at kami tumitingin - first 10 chars "YYYY-MM-DD")
            const bDate = t.borrowed_at ? t.borrowed_at.substring(0, 10) : '';
            const rDate = t.returned_at ? t.returned_at.substring(0, 10) : '';
            const dateMatch = !selectedDate || bDate === selectedDate || rDate === selectedDate;

            return statusMatch && searchMatch && dateMatch;
        });

        currentPage = 1;
        renderTable();
    }

    function renderTable() {
        if (!tableBody) return;
        tableBody.innerHTML = '';
        
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedData = currentFilteredTransactions.slice(start, end);

        if (paginatedData.length === 0) {
            renderEmptyState();
            if (paginationContainer) paginationContainer.classList.add('hidden');
            return;
        }

        if (paginationContainer) paginationContainer.classList.remove('hidden');

        paginatedData.forEach(t => {
            if (!rowTemplate) return;
            const clone = rowTemplate.content.cloneNode(true);
            const tr = clone.querySelector('tr');
            tr.dataset.id = t.transaction_id;

            const cells = clone.querySelectorAll('td');
            const name = t.studentName || `${t.first_name || ''} ${t.last_name || ''}`;
            const id = t.student_number || t.unique_faculty_id || t.employee_id || t.guest_id || 'N/A';
            const item = t.item_type === 'Book' ? (t.accession_number || t.item_name) : t.item_name;

            cells[0].textContent = name;
            cells[1].textContent = id;
            cells[2].textContent = item || 'N/A';
            cells[3].textContent = t.borrowed_at || 'N/A';
            cells[4].textContent = t.returned_at || 'Not yet returned';

            const statusSpan = cells[5].querySelector('span');
            const status = (t.transaction_status || 'unknown').toLowerCase();
            statusSpan.textContent = status.toUpperCase();
            
            statusSpan.className = 'px-3 py-1 rounded-full font-bold text-[10px] tracking-wider ';
            if (status === 'borrowed') statusSpan.classList.add('bg-orange-100', 'text-orange-800');
            else if (status === 'returned') statusSpan.classList.add('bg-green-100', 'text-green-800');
            else if (status === 'expired' || status === 'overdue' || status === 'lost') statusSpan.classList.add('bg-red-100', 'text-red-800');
            else statusSpan.classList.add('bg-gray-100', 'text-gray-500');

            tableBody.appendChild(clone);
        });
        
        renderPagination();
    }

    function renderEmptyState(msg) {
        if (!noTransactionsTemplate || !tableBody) return;
        const clone = noTransactionsTemplate.content.cloneNode(true);
        if (msg) {
            const p = clone.querySelector('p');
            if (p) p.textContent = msg;
        }
        tableBody.appendChild(clone);
    }

    function renderPagination() {
        if (!paginationNumbers) return;
        paginationNumbers.innerHTML = '';
        const pageCount = Math.ceil(currentFilteredTransactions.length / rowsPerPage);

        if (prevPageBtn) prevPageBtn.classList.toggle('opacity-50', currentPage === 1);
        if (nextPageBtn) nextPageBtn.classList.toggle('opacity-50', currentPage === pageCount || pageCount === 0);

        if (pageCount <= 1) return;

        for (let i = 1; i <= pageCount; i++) {
            const btn = document.createElement('a');
            btn.textContent = i;
            btn.className = `w-8 h-8 flex items-center justify-center rounded-full transition cursor-pointer ${i === currentPage ? 'bg-orange-500 text-white shadow-md' : 'text-gray-600 hover:bg-orange-50'}`;
            btn.onclick = (e) => { e.preventDefault(); currentPage = i; renderTable(); };
            paginationNumbers.appendChild(btn);
        }
    }

    // Controls
    if (statusBtn && statusMenu) {
        statusBtn.onclick = (e) => { e.stopPropagation(); statusMenu.classList.toggle('hidden'); };
        document.onclick = () => statusMenu.classList.add('hidden');
        statusMenu.querySelectorAll('.dropdown-item').forEach(item => {
            item.onclick = () => {
                statusValue.textContent = item.dataset.value;
                applyAndRenderFilters();
            };
        });
    }

    if (searchInput) searchInput.oninput = applyAndRenderFilters;
    if (dateInput) dateInput.onchange = applyAndRenderFilters;
    
    if (prevPageBtn) prevPageBtn.onclick = (e) => { e.preventDefault(); if (currentPage > 1) { currentPage--; renderTable(); } };
    if (nextPageBtn) nextPageBtn.onclick = (e) => { e.preventDefault(); const pc = Math.ceil(currentFilteredTransactions.length / rowsPerPage); if (currentPage < pc) { currentPage++; renderTable(); } };

    if (tableBody) {
        tableBody.onclick = (e) => {
            const row = e.target.closest('.transaction-row');
            if (!row) return;
            const t = allTransactions.find(x => x.transaction_id == row.dataset.id);
            if (t) openModal(t);
        };
    }

    function openModal(t) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || 'N/A'; };
        const name = t.studentName || `${t.first_name || ''} ${t.last_name || ''}`;
        const idNum = t.student_number || t.unique_faculty_id || t.employee_id || t.guest_id || 'N/A';
        const campus = t.campus_name || 'N/A';

        set('modalStudentName', name);
        set('modalStudentId', idNum);
        set('modalCampus', campus);
        set('modalCourse', t.student_id ? `${t.course_code || ''} - ${t.course_title || ''}` : (t.faculty_id ? `${t.college_code || ''}` : (t.position || 'N/A')));
        set('modalYear', t.student_id ? t.year_level : (t.faculty_id ? 'Faculty' : (t.staff_id ? 'Staff' : 'N/A')));
        set('modalSection', t.section || 'N/A');
        set('modalItemTitle', t.item_name);
        set('modalBorrowedDate', t.borrowed_at);
        set('modalReturnedDate', t.returned_at || 'Not returned');
        set('modalProcessedBy', t.librarian_name || 'System');

        const statusEl = document.getElementById('modalStatus');
        if (statusEl) {
            const s = (t.transaction_status || '').toUpperCase();
            statusEl.className = `font-bold ${s === 'RETURNED' ? 'text-green-600' : (s === 'BORROWED' ? 'text-orange-600' : 'text-red-600')}`;
            statusEl.textContent = s;
        }

        // Item specifics
        const isBook = t.item_type === 'Book';
        document.getElementById('modalItemAuthorRow').style.display = isBook ? '' : 'none';
        document.getElementById('modalItemAccessionRow').style.display = isBook ? '' : 'none';
        document.getElementById('modalItemCallNoRow').style.display = isBook ? '' : 'none';
        document.getElementById('modalItemISBNRow').style.display = isBook ? '' : 'none';
        document.getElementById('modalItemAssetTagRow').style.display = isBook ? 'none' : '';

        if (isBook) {
            set('modalItemAuthor', t.book_author);
            set('modalItemAccession', t.accession_number);
            set('modalItemCallNo', t.call_number);
            set('modalItemISBN', t.book_isbn);
        } else {
            set('modalItemAssetTag', t.asset_tag);
        }

        modal.classList.remove('hidden');
    }

    if (closeModalBtn) closeModalBtn.onclick = () => modal.classList.add('hidden');
    
    // Close modal when clicking on the backdrop
    if (modal) {
        modal.onclick = (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        };
    }
    
    loadTransactions();
});
