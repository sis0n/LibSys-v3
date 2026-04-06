// --- SweetAlert Helper Functions (Kailangan para gumana ang loading modal) ---

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
        customClass: {
            popup: "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]",
        },
    });
}

// 🟠 LOADING MODAL (ORANGE THEME)
function showLoadingModal(message = "Processing request...", subMessage = "Please wait.") {
    if (typeof Swal == "undefined") return;
    Swal.fire({
        background: "transparent",
        html: `
            <div class="flex flex-col items-center justify-center gap-2">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div>
                <p class="text-gray-700 text-[14px]">${message}<br><span class="text-sm text-gray-500">${subMessage}</span></p>
            </div>
        `,
        allowOutsideClick: false,
        showConfirmButton: false,
        customClass: {
            popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
        },
    });
}
// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------


document.addEventListener('DOMContentLoaded', function () {
    const statusBtn = document.getElementById('statusFilterBtn');
    const statusMenu = document.getElementById('statusFilterMenu');
    const statusValue = document.getElementById('statusFilterValue');
    const tableBody = document.getElementById('transactionHistoryTableBody');
    const noTransactionsMessage = document.getElementById('no-transactions-found');
    const rowTemplate = document.getElementById('transaction-row-template').content;
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

    async function loadTransactions() {
        // 1. 🟠 START LOADING
        const startTime = Date.now();
        showLoadingModal("Loading Transaction History...", "Retrieving records.");

        // Clear table body before loading
        if (tableBody) tableBody.innerHTML = '';
        if (paginationContainer) paginationContainer.classList.add('hidden');
        
        try {
            const response = await fetch('api/campus_admin/transactionHistory/json');
            if (!response.ok) throw new Error("Failed to fetch data.");
            
            allTransactions = await response.json();
            
            // 2. CLOSE LOADING with minimum delay
            const elapsed = Date.now() - startTime;
            const minDelay = 500;
            if (elapsed < minDelay) await new Promise(r => setTimeout(r, minDelay - elapsed));
            if (typeof Swal != 'undefined') Swal.close();
            
            currentFilteredTransactions = allTransactions;
            applyAndRenderFilters();
            
        } catch (error) {
            console.error('Error fetching transactions:', error);
            
            // 3. CLOSE LOADING and SHOW ERROR
            if (typeof Swal != 'undefined') Swal.close();
            showErrorToast('Data Load Failed', 'Could not retrieve transaction history from the server.');
            
            // Display error message in the table
            if (tableBody && noTransactionsMessage) {
                 tableBody.appendChild(noTransactionsMessage.cloneNode(true));
                 const errorText = tableBody.querySelector('#no-transactions-found td');
                 if (errorText) errorText.textContent = "Error loading history. Please try again.";
            }
        }
    }

    function applyAndRenderFilters() {
        const status = statusValue.textContent;
        const searchTerm = searchInput.value.toLowerCase();
        const date = dateInput.value;

        currentFilteredTransactions = allTransactions.filter(transaction => {
            const statusMatch = status === 'All Status' || (transaction.transaction_status && transaction.transaction_status.toLowerCase() === status.toLowerCase());
            const searchMatch = !searchTerm ||
                (transaction.studentName && transaction.studentName.toLowerCase().includes(searchTerm)) ||
                (transaction.studentNumber && transaction.studentNumber.toLowerCase().includes(searchTerm)) ||
                (transaction.item_name && transaction.item_name.toLowerCase().includes(searchTerm));
            const dateMatch = !date ||
                (transaction.borrowed_at && transaction.borrowed_at.startsWith(date)) ||
                (transaction.returned_at && transaction.returned_at.startsWith(date));

            return statusMatch && searchMatch && dateMatch;
        });

        currentPage = 1;
        renderTable(currentFilteredTransactions, currentPage);
        renderPagination(currentFilteredTransactions);
    }

    // Status dropdown toggle
    statusBtn.addEventListener('click', e => {
        e.stopPropagation();
        statusMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', () => statusMenu.classList.add('hidden'));

    statusMenu.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', () => {
            statusValue.textContent = item.dataset.value;
            statusMenu.classList.add('hidden');
            applyAndRenderFilters();
        });
    });

    searchInput.addEventListener('input', applyAndRenderFilters);
    dateInput.addEventListener('change', applyAndRenderFilters);

    function renderTable(data, page) {
        tableBody.innerHTML = '';
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const paginatedData = data.slice(start, end);

        if (paginatedData.length === 0 && page === 1) {
            tableBody.appendChild(noTransactionsMessage.cloneNode(true));
            paginationContainer.classList.add('hidden');
            return;
        }

        paginationContainer.classList.remove('hidden');

        paginatedData.forEach(transaction => {
            const newRow = rowTemplate.cloneNode(true);
            const tr = newRow.querySelector('tr');
            tr.dataset.transactionId = transaction.transaction_id;
            tr.classList.add('transaction-row'); // Add this class for modal event listener

            const cells = newRow.querySelectorAll('td');

            const borrowerName = transaction.studentName || `${transaction.first_name || ''} ${transaction.last_name || ''}`;
            const borrowerId = transaction.student_number || transaction.unique_faculty_id || transaction.employee_id || transaction.guest_id || 'N/A';

            cells[0].textContent = borrowerName;
            cells[1].textContent = borrowerId;

            const displayItem = transaction.item_type === 'Book' 
                ? transaction.accession_number 
                : transaction.item_name;
            cells[2].textContent = displayItem || 'N/A';


            cells[3].textContent = transaction.borrowed_at || '';
            cells[4].textContent = transaction.returned_at ? transaction.returned_at : 'Not yet returned';

            const statusCell = cells[5].querySelector('span');
            const statusText = transaction.transaction_status ? transaction.transaction_status.toLowerCase() : 'unknown';
            statusCell.textContent = transaction.transaction_status || 'N/A';
            statusCell.className = 'px-3 py-1 rounded-full font-medium text-xs';

            if (statusText === 'borrowed') {
                statusCell.classList.add('bg-red-100', 'text-red-800');
            } else if (statusText === 'returned') {
                statusCell.classList.add('bg-green-100', 'text-green-800');
            } else if (statusText === 'expired') {
                statusCell.classList.add('bg-gray-100', 'text-gray-800');
            } else {
                statusCell.classList.add('bg-gray-100', 'text-gray-500');
            }

            tableBody.appendChild(newRow);
        });
    }

    // Pagination
    function renderPagination(data) {
        paginationNumbers.innerHTML = '';
        const pageCount = Math.ceil(data.length / rowsPerPage);

        prevPageBtn.classList.toggle('text-gray-400', currentPage === 1);
        prevPageBtn.classList.toggle('hover:text-orange-700', currentPage !== 1);
        nextPageBtn.classList.toggle('text-gray-400', currentPage === pageCount);
        nextPageBtn.classList.toggle('hover:text-orange-700', currentPage !== pageCount);

        for (let i = 1; i <= pageCount; i++) {
            const pageNumber = document.createElement('a');
            pageNumber.href = '#';
            pageNumber.textContent = i;
            pageNumber.classList.add('px-4', 'py-1.5', 'rounded-full', 'transition', 'duration-200');
            if (i === currentPage) pageNumber.classList.add('bg-orange-500', 'text-white', 'shadow-sm');
            else pageNumber.classList.add('text-gray-700', 'hover:text-orange-600', 'hover:bg-orange-50');

            pageNumber.addEventListener('click', e => {
                e.preventDefault();
                currentPage = i;
                renderTable(currentFilteredTransactions, currentPage);
                renderPagination(currentFilteredTransactions);
            });
            paginationNumbers.appendChild(pageNumber);
        }
    }

    prevPageBtn.addEventListener('click', e => {
        e.preventDefault();
        if (currentPage > 1) {
            currentPage--;
            renderTable(currentFilteredTransactions, currentPage);
            renderPagination(currentFilteredTransactions);
        }
    });

    nextPageBtn.addEventListener('click', e => {
        e.preventDefault();
        const pageCount = Math.ceil(currentFilteredTransactions.length / rowsPerPage);
        if (currentPage < pageCount) {
            currentPage++;
            renderTable(currentFilteredTransactions, currentPage);
            renderPagination(currentFilteredTransactions);
        }
    });

    // Modal
    closeModalBtn.addEventListener('click', () => modal.classList.add('hidden'));
    modal.addEventListener('click', e => { if (e.target === modal) modal.classList.add('hidden'); });

    tableBody.addEventListener('click', e => {
        const row = e.target.closest('.transaction-row');
        if (!row) return;
        const transactionId = parseInt(row.dataset.transactionId, 10);
        const transaction = allTransactions.find(t => t.transaction_id === transactionId);
        if (transaction) openModalWithTransaction(transaction);
    });

    function openModalWithTransaction(transaction) {
        const isStudent = !!transaction.student_id;
        const isFaculty = !!transaction.faculty_id;
        const isStaff = !!transaction.staff_id;

        const borrowerName = transaction.studentName || `${transaction.first_name || ''} ${transaction.last_name || ''}`;
        const borrowerId = transaction.student_number || transaction.unique_faculty_id || transaction.employee_id || transaction.guest_id || 'N/A';

        let courseOrDepartment = 'N/A';
        let yearOrPosition = 'N/A';

        if (isStudent) {
            courseOrDepartment = `${transaction.course_code || ''} - ${transaction.course_title || ''}`;
            yearOrPosition = `${transaction.year_level || ''} ${transaction.section || ''}`;
        } else if (isFaculty) {
            courseOrDepartment = `${transaction.college_code || ''} - ${transaction.college_name || ''}`;
            yearOrPosition = 'Faculty';
        } else if (isStaff) {
            courseOrDepartment = transaction.position || 'N/A';
            yearOrPosition = 'Staff';
        }

        document.getElementById('modalStudentName').textContent = borrowerName;
        document.getElementById('modalStudentId').textContent = borrowerId;

        document.getElementById('modalCourse').textContent = courseOrDepartment;
        document.getElementById('modalYear').textContent = yearOrPosition;
        document.getElementById('modalSection').textContent = transaction.section || (isFaculty ? 'N/A' : (transaction.employee_id ? 'Staff' : 'N/A'));

        // Display item_name for both books and equipment
        document.getElementById('modalItemTitle').textContent = transaction.item_name || '';

        // Conditionally display book-specific details or equipment-specific details
        const itemAuthorRow = document.getElementById('modalItemAuthorRow');
        const itemAccessionRow = document.getElementById('modalItemAccessionRow');
        const itemCallNoRow = document.getElementById('modalItemCallNoRow');
        const itemISBNRow = document.getElementById('modalItemISBNRow');
        const itemAssetTagRow = document.getElementById('modalItemAssetTagRow'); // Need to add this row to the HTML

        if (transaction.item_type === 'Book') {
            document.getElementById('modalItemAuthor').textContent = transaction.book_author || '';
            document.getElementById('modalItemAccession').textContent = transaction.accession_number || '';
            document.getElementById('modalItemCallNo').textContent = transaction.call_number || '';
            document.getElementById('modalItemISBN').textContent = transaction.book_isbn || '';

            itemAuthorRow.style.display = '';
            itemAccessionRow.style.display = '';
            itemCallNoRow.style.display = '';
            itemISBNRow.style.display = '';
            if (itemAssetTagRow) itemAssetTagRow.style.display = 'none'; // Hide asset tag for books
        } else if (transaction.item_type === 'Equipment') {
            if (itemAuthorRow) itemAuthorRow.style.display = 'none'; // Hide author for equipment
            if (itemAccessionRow) itemAccessionRow.style.display = 'none'; // Hide accession for equipment
            if (itemCallNoRow) itemCallNoRow.style.display = 'none'; // Hide call number for equipment
            if (itemISBNRow) itemISBNRow.style.display = 'none'; // Hide ISBN for equipment

            // Assuming you have an element for asset tag in your modal HTML
            if (itemAssetTagRow) {
                document.getElementById('modalItemAssetTag').textContent = transaction.asset_tag || 'N/A';
                itemAssetTagRow.style.display = '';
            }
        } else {
            // Default for unknown or if you want to hide all
            if (itemAuthorRow) itemAuthorRow.style.display = 'none';
            if (itemAccessionRow) itemAccessionRow.style.display = 'none';
            if (itemCallNoRow) itemCallNoRow.style.display = 'none';
            if (itemISBNRow) itemISBNRow.style.display = 'none';
            if (itemAssetTagRow) itemAssetTagRow.style.display = 'none';
        }

        document.getElementById('modalBorrowedDate').textContent = transaction.borrowed_at || '';
        document.getElementById('modalReturnedDate').textContent = transaction.returned_at ?? 'Not yet returned';
        document.getElementById('modalProcessedBy').textContent = transaction.librarian_name ?? 'Not yet processed';

        const statusEl = document.getElementById('modalStatus');
        statusEl.innerHTML = '';
        const statusSpan = document.createElement('span');
        const statusText = transaction.transaction_status ? transaction.transaction_status.toLowerCase() : 'unknown';
        statusSpan.textContent = transaction.transaction_status?.toUpperCase() || 'N/A';
        statusSpan.classList.add('font-semibold', 'tracking-wide');

        if (statusText === 'borrowed') statusSpan.classList.add('text-orange-600');
        else if (statusText === 'returned') statusSpan.classList.add('text-green-600');
        else if (statusText === 'expired') statusSpan.classList.add('text-gray-600');

        statusEl.appendChild(statusSpan);
        modal.classList.remove('hidden');
    }

    // Default date
    const today = new Date();
    dateInput.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

    loadTransactions();
});
