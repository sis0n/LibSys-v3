   window.addEventListener("DOMContentLoaded", () => {
    // --- SweetAlert Helper Functions (Para ma-maintain ang design consistency) ---
    
    function showSuccessToast(title, body = "") {
        if (typeof Swal == "undefined") return alert(title);
        Swal.fire({
            toast: true,
            position: "bottom-end",
            showConfirmButton: false,
            timer: 3000,
            width: "360px",
            background: "transparent",
            html: `<div class="flex flex-col text-left"><div class="flex items-center gap-3 mb-2"><div class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600"><i class="ph ph-check-circle text-lg"></i></div><div><h3 class="text-[15px] font-semibold text-green-600">${title}</h3><p class="text-[13px] text-gray-700 mt-0.5">${body}</p></div></div></div>`,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-green-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#f0fff5] shadow-[0_0_8px_#22c55e70]",
            },
        });
    }

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

    async function showConfirmationModal(title, text, confirmText = "Confirm", icon = "ph-warning-circle") {
        if (typeof Swal == "undefined") return confirm(title);
        const result = await Swal.fire({
            background: "transparent",
            html: `
                <div class="flex flex-col text-center">
                    <div class="flex justify-center mb-3">
                        <div class="flex items-center justify-center w-14 h-14 rounded-full bg-orange-100 text-orange-600">
                            <i class="ph ${icon} text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-[17px] font-semibold text-orange-700">${title}</h3>
                    <p class="text-[14px] text-gray-700 mt-1">${text}</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: "Cancel",
            customClass: {
                popup:
                    "!rounded-xl !shadow-md !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] !border-2 !border-orange-400 shadow-[0_0_8px_#ffb34770]",
                confirmButton:
                    "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700",
                cancelButton:
                    "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300",
            },
        });
        return result.isConfirmed;
    }
    // --- End SweetAlert Helper Functions ---

    // ==========================
    // ELEMENT REFERENCES
    // ==========================
    const importModal = document.getElementById("importModal");
    const bulkImportBtn = document.getElementById("bulkImportBtn");
    const closeImportModal = document.getElementById("closeImportModal");
    const cancelImport = document.getElementById("cancelImport");

    const addBookModal = document.getElementById("addBookModal");
    const openAddBookBtn = document.getElementById("openAddBookBtn");
    const closeAddBookModal = document.getElementById("closeAddBookModal");
    const cancelAddBook = document.getElementById("cancelAddBook");
    const addBookForm = document.getElementById("addBookForm");
    const input = document.getElementById('book_image');
    const uploadText = document.getElementById('uploadText');
    const previewContainer = document.getElementById('previewContainer');
    const previewImage = document.getElementById('previewImage');

    const editBookModal = document.getElementById("editBookModal");
    const closeEditBookModal = document.getElementById("closeEditBookModal");
    const cancelEditBook = document.getElementById("cancelEditBook");
    const editBookForm = document.getElementById("editBookForm");
    const editInput = document.getElementById('edit_book_image');
    const editUploadText = document.getElementById('editUploadText');
    const editPreviewContainer = document.getElementById('editPreviewContainer');
    const editPreviewImage = document.getElementById('editPreviewImage');
    const removeImageBtn = document.getElementById('removeImageBtn');
    const editRemoveImageInput = document.getElementById('edit_remove_image');

    // --- View Modal Elements ---
    const viewBookModal = document.getElementById("viewBookModal");
    const viewBookModalContent = document.getElementById("viewBookModalContent");
    const closeViewModal = document.getElementById("closeViewModal");
    const closeViewModalBtn = document.getElementById("closeViewModalBtn");
    const viewModalImg = document.getElementById("viewModalImg");
    const viewModalTitle = document.getElementById("viewModalTitle");
    const viewModalAuthor = document.getElementById("viewModalAuthor");
    const viewModalStatus = document.getElementById("viewModalStatus");
    const viewModalCallNumber = document.getElementById("viewModalCallNumber");
    const viewModalAccessionNumber = document.getElementById("viewModalAccessionNumber");
    const viewModalIsbn = document.getElementById("viewModalIsbn");
    const viewModalSubject = document.getElementById("viewModalSubject");
    const viewModalPlace = document.getElementById("viewModalPlace");
    const viewModalPublisher = document.getElementById("viewModalPublisher");
    const viewModalYear = document.getElementById("viewModalYear");
    const viewModalEdition = document.getElementById("viewModalEdition");
    const viewModalSupplementary = document.getElementById("viewModalSupplementary");
    const viewModalDescription = document.getElementById("viewModalDescription");

    const searchInput = document.getElementById("bookSearchInput");
    const bookTableBody = document.getElementById("bookTableBody");
    const resultsIndicator = document.getElementById("resultsIndicator");

    const paginationControls = document.getElementById("paginationControls");
    const paginationList = document.getElementById("paginationList");
    const bulkImportForm = document.getElementById("bulkImportForm");
    const fileInput = document.getElementById("csvFile");
    const importMessage = document.getElementById("importMessage");

    const multiSelectBtn = document.getElementById("multiSelectBtn");
    const multiSelectActions = document.getElementById("multiSelectActions");
    const selectAllBtn = document.getElementById("selectAllBtn");
    const cancelSelectionBtn = document.getElementById("cancelSelectionBtn");
    const multiDeleteBtn = document.getElementById("multiDeleteBtn");
    const selectionCount = document.getElementById("selectionCount");

    const historyModal = document.getElementById("historyModal");
    const historyTableBody = document.getElementById("historyTableBody");
    const historyTableContainer = document.getElementById("historyTableContainer");
    const historyEmptyState = document.getElementById("historyEmptyState");
    const closeHistoryModal = document.getElementById("closeHistoryModal");
    const closeHistoryBtn = document.getElementById("closeHistoryBtn");

    if (!bookTableBody || !addBookModal || !editBookModal || !importModal || !searchInput || !paginationList || !resultsIndicator || !viewBookModal) {
        console.error("BookManagement Error: Core components missing.");
        if (bookTableBody) bookTableBody.innerHTML = `<tr data-placeholder="true"><td colspan="7" class="text-center text-red-500 py-10">Page Error: Components missing.</td></tr>`;
        return;
    }

    // ==========================
    // STATE VARIABLES
    // ==========================
    let books = [];
    let totalBooks = 0;
    let currentEditingBookId = null;
    let currentSort = 'default';
    let currentStatus = 'All Status';
    let currentSearch = '';
    let isLoading = false;
    let searchDebounce;
    const limit = 30;
    let currentPage = 1;
    let totalPages = 1;
    let isMultiSelectMode = false;
    let selectedBooks = new Set();


    fileInput.addEventListener("change", () => {
        if (fileInput.files.length) {
            bulkImportForm.requestSubmit();
        }
    });

    bulkImportForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        
        if (!fileInput.files.length) return showErrorToast("Import Error", "Please select a CSV file.");

        const formData = new FormData();
        formData.append("csv_file", fileInput.files[0]);
        
        try {
            const res = await fetch(`api/librarian/booksmanagement/bulkImport`, {
                method: "POST",
                body: formData
            });

            const data = await res.json();
            
            if (data.success) {
                if (importMessage) {
                    importMessage.textContent = `Imported: ${data.imported} rows successfully!`;
                    importMessage.classList.remove("hidden");
                    setTimeout(() => importMessage.classList.add("hidden"), 5000);
                }
                showSuccessToast("Import Successful", `Successfully imported ${data.imported} books!`);
                fileInput.value = "";
                closeModal(document.getElementById("importModal"));
                await loadBooks(1, false); 
            } else {
                showErrorToast("Import Failed", data.message || "Failed to import CSV.");
            }
        } catch (err) {
            console.error("Error importing CSV:", err);
            showErrorToast("Import Failed", "An error occurred during CSV import.");
        }
    });

    // --- Page Memory ---
    try {
        const savedPage = sessionStorage.getItem('bookManagementPage');
        if (savedPage) {
            const parsedPage = parseInt(savedPage, 10);
            if (!isNaN(parsedPage) && parsedPage > 0) currentPage = parsedPage;
            else sessionStorage.removeItem('bookManagementPage');
        }
    } catch (e) {
        console.error("SessionStorage Error:", e);
        currentPage = 1;
    }
    
    function openModal(modal) {
        if (modal) {
            modal.classList.remove("hidden");
            document.body.classList.add("overflow-hidden");
        }
    }

    function closeModal(modal) {
        if (modal) {
            modal.classList.add("hidden");
            document.body.classList.remove("overflow-hidden");
        }
    }

    bulkImportBtn?.addEventListener("click", () => openModal(importModal));
    closeImportModal?.addEventListener("click", () => closeModal(importModal));
    cancelImport?.addEventListener("click", () => closeModal(importModal));
    importModal?.addEventListener("click", e => {
        if (e.target === importModal) closeModal(importModal);
    });

    openAddBookBtn?.addEventListener("click", () => {
        addBookForm.reset();
        previewContainer.classList.add('hidden');
        uploadText.textContent = 'Upload Image';
        openModal(addBookModal);
    });
    closeAddBookModal?.addEventListener("click", () => closeModal(addBookModal));
    cancelAddBook?.addEventListener("click", () => closeModal(addBookModal));
    addBookModal?.addEventListener("click", e => {
        if (e.target === addBookModal) closeModal(addBookModal);
    });

    closeEditBookModal?.addEventListener("click", () => closeModal(editBookModal));
    cancelEditBook?.addEventListener("click", () => closeModal(editBookModal));
    editBookModal?.addEventListener("click", e => {
        if (e.target === editBookModal) closeModal(editBookModal);
    });

    input?.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const allowedTypes = ['image/jpeg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            showErrorToast('Invalid File Type', 'Please upload only JPG or PNG files.');
            input.value = '';
            uploadText.textContent = 'Upload Image';
            previewContainer.classList.add('hidden');
            previewImage.src = '';
            return;
        }
        uploadText.textContent = file.name;
        if (file.type.startsWith('image/')) {
            previewContainer.classList.remove('hidden');
            const reader = new FileReader();
            reader.onload = (event) => (previewImage.src = event.target.result);
            reader.readAsDataURL(file);
        }
    });

    editInput?.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const allowedTypes = ['image/jpeg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            showErrorToast('Invalid File Type', 'Please upload only JPG or PNG files.');
            editInput.value = '';
            editUploadText.textContent = 'Upload Image';
            editPreviewContainer.classList.add('hidden');
            editPreviewImage.src = '';
            return;
        }
        editUploadText.textContent = file.name;
        if (file.type.startsWith('image/')) {
            editPreviewContainer.classList.remove('hidden');
            const reader = new FileReader();
            reader.onload = (event) => (editPreviewImage.src = event.target.result);
            reader.readAsDataURL(file);
        }
    });

    // ==========================
    // DROPDOWN LOGIC
    // ==========================
    function setupDropdown(btnId, menuId) {
        const btn = document.getElementById(btnId);
        const menu = document.getElementById(menuId);
        if (!btn || !menu) return;
        const closeAllDropdowns = () => {
            document.querySelectorAll('.absolute.mt-1.z-20').forEach(m => m.classList.add('hidden'));
        }
        btn.addEventListener("click", e => {
            e.stopPropagation();
            const isHidden = menu.classList.contains('hidden');
            closeAllDropdowns();
            if (isHidden) menu.classList.toggle("hidden");
        });
    }
    setupDropdown("statusDropdownBtn", "statusDropdownMenu");
    setupDropdown("sortDropdownBtn", "sortDropdownMenu");
    document.addEventListener("click", () => {
        document.querySelectorAll('.absolute.mt-1.z-20').forEach(menu => menu.classList.add('hidden'));
    });

    window.selectSort = (el, val) => {
        const valueEl = document.getElementById("sortDropdownValue");
        if (valueEl) valueEl.textContent = el.textContent;
        document.querySelectorAll("#sortDropdownMenu .sort-item").forEach(i => i.classList.remove("bg-orange-50", "font-semibold"));
        if (el) el.classList.add("bg-orange-50", "font-semibold");
        currentSort = val;
        currentPage = 1;
        try {
            sessionStorage.removeItem('bookManagementPage');
        } catch (e) {}
        loadBooks(currentPage, false); 
    };
    window.selectStatus = (el, val) => {
        const valueEl = document.getElementById("statusDropdownValue");
        if (valueEl) valueEl.textContent = val;
        document.querySelectorAll("#statusDropdownMenu .status-item").forEach(i => i.classList.remove("bg-orange-50", "font-semibold"));
        if (el) el.classList.add("bg-orange-50", "font-semibold");
        currentStatus = val;
        currentPage = 1;
        try {
            sessionStorage.removeItem('bookManagementPage');
        } catch (e) {}
        loadBooks(currentPage, false);
    };

    const defaultSort = document.querySelector("#sortDropdownMenu .sort-item");
    if (defaultSort) defaultSort.classList.add("bg-orange-50", "font-semibold");
    const defaultStatus = document.querySelector("#statusDropdownMenu .status-item");
    if (defaultStatus) defaultStatus.classList.add("bg-orange-50", "font-semibold");

    searchInput.addEventListener("input", e => {
        currentSearch = e.target.value.trim();
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            currentPage = 1;
            try {
                sessionStorage.removeItem('bookManagementPage');
            } catch (e) {}
            loadBooks(currentPage, false); 
        }, 500);
    });

    // ==========================
    // DATA FETCHING (AJAX)
    // ==========================
    async function loadBooks(page = 1, isShowLoadingModal = true) {
        if (isLoading) return;
        isLoading = true;
        currentPage = page;

        const startTime = Date.now();    

        if (bookTableBody) bookTableBody.innerHTML = "";
        paginationControls.classList.add('hidden');
        resultsIndicator.textContent = 'Loading...';
        
        if (isShowLoadingModal && typeof Swal != 'undefined') {
            showLoadingModal("Loading Book Catalog...", "Retrieving library records.");
        }
        
        const offset = (page - 1) * limit;
        
        try {
            const params = new URLSearchParams({
                search: currentSearch,
                status: currentStatus === 'All Status' ? '' : currentStatus,
                sort: currentSort,
                limit: limit,
                offset: offset
            });

            const res = await fetch(`api/librarian/booksmanagement/fetch?${params.toString()}`);
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const data = await res.json();

            if (isShowLoadingModal) {
                const elapsed = Date.now() - startTime;
                const minDelay = 1000;
                if (elapsed < minDelay) await new Promise(r => setTimeout(r, minDelay - elapsed));
                if (typeof Swal != 'undefined') Swal.close();
            }

            if (data.success && Array.isArray(data.books)) {
                books = data.books;
                totalBooks = data.totalCount;
                totalPages = Math.ceil(totalBooks / limit) || 1;

                if (page > totalPages && totalPages > 0) {
                    loadBooks(totalPages, isShowLoadingModal);
                    return;
                }
                renderBooks(data.books);
                renderPagination(totalPages, currentPage);
                updateBookCounts(data.books.length, totalBooks, page, limit);
                try {
                    sessionStorage.setItem('bookManagementPage', currentPage);
                } catch (e) {}
            } else {
                throw new Error(data.message || "Invalid data format from server.");
            }
        } catch (err) {
            console.error("Fetch books error:", err);
            bookTableBody.innerHTML = `<tr data-placeholder="true"><td colspan="7" class="text-center text-red-500 py-10">Error loading books: ${err.message}</td></tr>`;
            updateBookCounts(0, 0, 1, limit);
            showErrorToast("Data Load Failed", "Could not retrieve book list data.");
            if (isShowLoadingModal && typeof Swal != 'undefined') Swal.close();
            try {
                sessionStorage.removeItem('bookManagementPage');
            } catch (e) {}
        } finally {
            isLoading = false;
        }
    }

    // ==========================
    // RENDER TABLE FUNCTION
    // ==========================
    const renderBooks = (booksToRender) => {
        bookTableBody.innerHTML = "";

        const headerRow = document.querySelector('thead tr');
        if (headerRow) {
            const firstHeader = headerRow.querySelector('th');
            if (isMultiSelectMode) {
                if (!firstHeader.classList.contains('multi-select-header')) {
                    const th = document.createElement('th');
                    th.className = 'py-3 px-4 font-medium multi-select-header';
                    headerRow.insertBefore(th, firstHeader);
                }
            } else {
                if (firstHeader && firstHeader.classList.contains('multi-select-header')) {
                    firstHeader.remove();
                }
            }
        }

        if (!booksToRender || booksToRender.length === 0) {
            const colspan = document.querySelector('thead tr').children.length;
            bookTableBody.innerHTML = `
            <tr data-placeholder="true">
                <td colspan="${colspan}" class="py-10 text-center">
                    <div class="flex flex-col items-center justify-center text-gray-500">
                        <i class="ph ph-books text-5xl mb-3"></i>
                        <p class="font-medium text-gray-700">No books found</p>
                        <p class="text-sm text-gray-500">No books match your current filters.</p>
                    </div>
                </td>
            </tr>
            `;
            return;
        }

        let rowsHtml = "";
        booksToRender.forEach((book) => {
            const isSelected = selectedBooks.has(book.book_id);
            const statusColor = book.availability === "available" ? "bg-green-600" : book.availability === "borrowed" ? "bg-orange-500" : "bg-gray-600";
            const title = book.title ? String(book.title).replace(/</g, "&lt;") : 'N/A';
            const author = book.author ? String(book.author).replace(/</g, "&lt;") : 'N/A';
            const accession = book.accession_number ? String(book.accession_number).replace(/</g, "&lt;") : 'N/A';
            const call = book.call_number ? String(book.call_number).replace(/</g, "&lt;") : 'N/A';
            const isbn = book.book_isbn ? String(book.book_isbn).replace(/</g, "&lt;") : 'N/A';
            const status = book.availability ? String(book.availability).replace(/</g, "&lt;") : 'N/A';
            const safeTitle = title.replace(/'/g, "\\'").replace(/"/g, "&quot;");

            let checkboxCell = '';
            if (isMultiSelectMode) {
                checkboxCell = `
                    <td class="px-4 py-3">
                        <input type="checkbox" class="book-checkbox accent-orange-500 pointer-events-none" data-book-id="${book.book_id}" ${isSelected ? "checked" : ""}>
                    </td>
                `;
            }

            let actionsCellHTML = `
                <td class="py-3 px-4 text-center">
                    <button onclick="openHistoryModal(${book.book_id})"
                        class="border border-blue-300 text-blue-700 px-2 py-1 rounded hover:bg-blue-100" title="View Borrowing History">
                        <i class='ph ph-eye pointer-events-none'></i>
                    </button>
                    <button onclick="editBook(${book.book_id})"
                        class="border border-orange-300 text-orange-700 px-2 py-1 rounded hover:bg-orange-100">
                        <i class='ph ph-pencil pointer-events-none'></i>
                    </button>
                    <button onclick="deleteBook(${book.book_id}, '${safeTitle}')"
                        class="border border-orange-300 text-orange-700 px-2 py-1 rounded hover:bg-orange-100">
                        <i class='ph ph-trash pointer-events-none'></i>
                    </button>
                </td>`;

            if (isMultiSelectMode) {
                actionsCellHTML = `<td class="py-3 px-4 text-center"></td>`;
            }

            rowsHtml += `
            <tr data-book-id="${book.book_id}" class="transition-colors ${isMultiSelectMode ? 'cursor-pointer' : ''} ${isSelected ? 'bg-orange-100' : ''}">
                ${checkboxCell}
                <td class="py-3 px-4">
                    <div class="max-w-[240px] ">
                        <p class="font-medium text-gray-800 whitespace-normal break-words">${title}</p>
                    </div>
                </td>
                <td class="py-3 px-4 truncate max-w-[240px] whitespace-normal break-words">${author}</td>
                <td class="px-4 py-3">${accession}</td>
                <td class="px-4 py-3">${call}</td>
                <td class="px-4 py-3">${isbn}</td>
                <td class="py-3 px-4">
                    <span class="text-white text-xs px-3 py-1 rounded-full ${statusColor}">
                        ${status}
                    </span>
                </td>
                ${actionsCellHTML}
            </tr>`;
        });
        bookTableBody.innerHTML = rowsHtml;
    };

    // ==========================
    // PAGINATION RENDER
    // ==========================
    function renderPagination(totalPages, page) {
        if (totalPages <= 1) {
            paginationControls.className = "flex justify-center mt-8 hidden";
            return;
        }

        paginationList.innerHTML = '';

        const createPageLink = (type, text, pageNum, isDisabled = false, isActive = false) => {
            const li = document.createElement("li");
            const a = document.createElement("a");
            a.href = "#";
            a.setAttribute("data-page", String(pageNum));
            let baseClasses = `flex items-center justify-center min-w-[32px] h-9 text-sm font-medium transition-all duration-200`;
            if (type === "prev" || type === "next") {
                a.innerHTML = text;
                baseClasses += ` text-gray-700 hover:text-orange-600 px-3`;
                if (isDisabled) baseClasses += ` opacity-50 cursor-not-allowed pointer-events-none`;
            } else if (type === "ellipsis") {
                a.textContent = text;
                baseClasses += ` text-gray-400 cursor-default px-2`;
            } else {
                a.textContent = text;
                if (isActive) {
                    baseClasses += ` text-white bg-orange-600 rounded-full shadow-sm px-3`;
                } else {
                    baseClasses += ` text-gray-700 hover:text-orange-600 hover:bg-orange-100 rounded-full px-3`;
                }
            }
            a.className = baseClasses;
            li.appendChild(a);
            paginationList.appendChild(li);
        };

        paginationControls.className = `flex items-center justify-center bg-white border border-gray-200 rounded-full shadow-md px-4 py-2 mt-6 w-fit mx-auto gap-3`;

        createPageLink("prev", `<i class="flex ph ph-caret-left text-lg"></i> Previous`, page - 1, page === 1);
        const window = 2;
        let pagesToShow = new Set([1, totalPages, page]);
        for (let i = 1; i <= window; i++) {
            if (page - i > 0) pagesToShow.add(page - i);
            if (page + i <= totalPages) pagesToShow.add(page + i);
        }
        const sortedPages = [...pagesToShow].sort((a, b) => a - b);
        let lastPage = 0;
        for (const p of sortedPages) {
            if (p > lastPage + 1) createPageLink("ellipsis", "…", "...", true);
            createPageLink("number", p, p, false, p === page);
            lastPage = p;
        }
        createPageLink("next", `Next <i class="flex ph ph-caret-right text-lg"></i>`, page + 1, page === totalPages);
    }

    paginationList.addEventListener('click', async (e) => {
        e.preventDefault();
        if (isLoading) return;
        const target = e.target.closest('a[data-page]');
        if (!target) return;
        const pageStr = target.dataset.page;
        if (pageStr === '...') return;
        const pageNum = parseInt(pageStr, 10);
        if (!isNaN(pageNum) && pageNum !== currentPage) {
            if (isMultiSelectMode && selectedBooks.size > 0) {
                const isConfirmed = await showConfirmationModal(
                    "Clear Selection?",
                    "Navigating to another page will clear your current selection. Do you want to continue?",
                    "Yes, Continue"
                );
                if (isConfirmed) {
                    selectedBooks.clear();
                    updateMultiSelectButtons();
                    loadBooks(pageNum);
                }
            } else {
                loadBooks(pageNum);
            }
        }
    });

    // ==========================
    // COUNT UPDATE FUNCTION
    // ==========================
    function updateBookCounts(booksLength, totalCountNum, page, perPage) {
        if (resultsIndicator) {
            if (totalCountNum === 0) {
                resultsIndicator.innerHTML = `Showing <span id="bookCount" class="font-medium text-gray-800">0</span> of <span id="bookTotal" class="font-medium text-gray-800">0</span> books`;
            } else {
                const startItem = (page - 1) * perPage + 1;
                const endItem = (page - 1) * perPage + booksLength;
                resultsIndicator.innerHTML = `Showing <span id="bookCount" class="font-medium text-gray-800">${startItem}-${endItem}</span> of <span id="bookTotal" class="font-medium text-gray-800">${totalCountNum.toLocaleString()}</span> books`;
            }
        }
    }

    // ==========================
    // ACTIONS (ADD, EDIT, DELETE)
    // ==========================
    addBookForm?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(addBookForm);
        if (!formData.get('accession_number') || !formData.get('call_number') || !formData.get('title') || !formData.get('author')) {
            showErrorToast('Missing Information', 'Please fill in all required fields (*).');
            return;
        }
        
        showLoadingModal("Adding Book...", "Saving new record to catalog.");
        const startTime = Date.now();

        try {
            const res = await fetch(`api/librarian/booksmanagement/store`, {
                method: "POST",
                body: formData
            });
            const result = await res.json();
            
            const elapsed = Date.now() - startTime;
            const minModalDisplay = 300;
            if (elapsed < minModalDisplay) await new Promise(r => setTimeout(r, minModalDisplay - elapsed));
            Swal.close();

            if (result.success) {
                showSuccessToast('Success!', result.message || 'Book added successfully!');
                closeModal(addBookModal);
                addBookForm.reset();
                previewContainer.classList.add('hidden');
                uploadText.textContent = 'Upload Image';
                loadBooks(1, false); 
            } else {
                showErrorToast('Error', result.message || 'Failed to add book.');
            }
        } catch (err) {
            Swal.close();
            console.error("Add book error:", err);
            showErrorToast('Error', 'An error occurred while adding the book.');
        }
    });

    window.editBook = async (bookId) => {
        if (!bookId) return;
        currentEditingBookId = bookId;
        
        showLoadingModal("Loading Book Data...", "Preparing form for editing.");
        
        try {
            const res = await fetch(`api/librarian/booksmanagement/get/${bookId}`);
            if (!res.ok) throw new Error("Failed to fetch book details.");
            const data = await res.json();
            
            await new Promise(r => setTimeout(r, 300));
            Swal.close();

            if (data.success && data.book) {
                const book = data.book;
                document.getElementById("edit_book_id").value = book.book_id || '';
                document.getElementById("edit_accession_number").value = book.accession_number || '';
                document.getElementById("edit_call_number").value = book.call_number || '';
                document.getElementById("edit_title").value = book.title || '';
                document.getElementById("edit_author").value = book.author || '';
                document.getElementById("edit_book_isbn").value = book.book_isbn || '';
                document.getElementById("edit_book_place").value = book.book_place || '';
                document.getElementById("edit_book_publisher").value = book.book_publisher || '';
                document.getElementById("edit_year").value = book.year || '';
                document.getElementById("edit_book_edition").value = book.book_edition || '';
                document.getElementById("edit_book_supplementary").value = book.book_supplementary || '';
                document.getElementById("edit_subject").value = book.subject || '';
                document.getElementById("edit_description").value = book.description || '';
                editUploadText.textContent = 'Change Image';
                editInput.value = '';
                
                // Reset remove image state
                editRemoveImageInput.value = "0";

                if (book.cover) {
                    editPreviewImage.src = book.cover;
                    editPreviewContainer.classList.remove('hidden');
                    removeImageBtn.classList.remove('hidden');
                } else {
                    editPreviewContainer.classList.add('hidden');
                    editPreviewImage.src = '';
                    removeImageBtn.classList.add('hidden');
                }
                openModal(editBookModal);
            } else {
                showErrorToast('Error', data.message || 'Could not find book details.');
            }
        } catch (err) {
            Swal.close();
            console.error("Edit book fetch error:", err);
            showErrorToast('Error', 'Error fetching book data.');
        }
    };

    removeImageBtn?.addEventListener('click', () => {
        editRemoveImageInput.value = "1";
        editPreviewContainer.classList.add('hidden');
        editPreviewImage.src = '';
        removeImageBtn.classList.add('hidden');
        editUploadText.textContent = 'Upload Image';
        editInput.value = '';
    });

    editBookForm?.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (!currentEditingBookId) return;
        const formData = new FormData(editBookForm);
        if (!formData.get('accession_number') || !formData.get('call_number') || !formData.get('title') || !formData.get('author')) {
            showErrorToast('Missing Information', 'Please fill in all required fields (*).');
            return;
        }
        
        showLoadingModal("Saving Changes...", "Updating book record.");
        const startTime = Date.now();

        try {
            const res = await fetch(`api/librarian/booksmanagement/update/${currentEditingBookId}`, {
                method: "POST",
                body: formData
            });
            const result = await res.json();
            
            const elapsed = Date.now() - startTime;
            const minModalDisplay = 300;
            if (elapsed < minModalDisplay) await new Promise(r => setTimeout(r, minModalDisplay - elapsed));
            Swal.close();

            if (result.success) {
                showSuccessToast('Success!', result.message || 'Book updated successfully!');
                closeModal(editBookModal);
                loadBooks(currentPage, false);
            } else {
                showErrorToast('Error', result.message || 'Failed to update book.');
            }
        } catch (err) {
            Swal.close();
            console.error("Update book error:", err);
            showErrorToast('Error', 'An error occurred while updating the book.');
        }
    });

    window.deleteBook = async (bookId, title) => {

    if (!bookId) return;

    const isConfirmed = await showConfirmationModal(
        'Confirm Deletion',
        `Are you sure you want to delete the book: **${title}**? This action cannot be undone.`,
        'Yes, Delete It!'
    );

    if (!isConfirmed) return;

    showLoadingModal("Deleting Book...", "Removing book record from the system.");
    const startTime = Date.now();

    try {
        const res = await fetch(`api/librarian/booksmanagement/delete/${bookId}`, {
            method: "POST"
        });
        const result = await res.json();

        const elapsed = Date.now() - startTime;
        const minModalDisplay = 500;
        if (elapsed < minModalDisplay) {
            await new Promise(r => setTimeout(r, minModalDisplay - elapsed));
        }
        Swal.close();

        if (result.success) {
            showSuccessToast('Deleted!', result.message || 'Book deleted successfully.');
            loadBooks(currentPage, false);
        } else {
            showErrorToast('Error', result.message || 'Failed to delete the book.');
        }

    } catch (err) {
        Swal.close();
        console.error("Delete book error:", err);
        showErrorToast('Error', 'An error occurred during deletion.');
    }
    };

    bookTableBody.addEventListener("click", (e) => {
        const row = e.target.closest("tr");
        if (!row || !row.dataset.bookId) return;

        const bookId = parseInt(row.dataset.bookId, 10);
        if (!isMultiSelectMode) return;

        if (selectedBooks.has(bookId)) {
            selectedBooks.delete(bookId);
        } else {
            selectedBooks.add(bookId);
        }
        renderBooks(books);
        updateMultiSelectButtons();
    });

    function updateMultiSelectButtons() {
        const hasSelection = selectedBooks.size > 0;

        if (isMultiSelectMode) {
            multiSelectBtn.classList.add('hidden');
            multiSelectActions.classList.remove('hidden');
            multiSelectActions.classList.add('inline-flex');
        } else {
            multiSelectBtn.classList.remove('hidden');
            multiSelectActions.classList.add('hidden');
            multiSelectActions.classList.remove('inline-flex');
        }

        multiDeleteBtn.classList.toggle('hidden', !hasSelection);
        if (selectionCount) selectionCount.textContent = selectedBooks.size;

        const allVisibleBookIds = books.map(b => b.book_id);
        const allSelectedOnPage = allVisibleBookIds.length > 0 && allVisibleBookIds.every(id => selectedBooks.has(id));

        if (allSelectedOnPage) {
            selectAllBtn.innerHTML = `<i class="ph ph-check-square-offset text-base"></i> Deselect All`;
        } else {
            selectAllBtn.innerHTML = `<i class="ph ph-check-square-offset text-base"></i> Select All`;
        }
    }

    multiSelectBtn.addEventListener('click', () => {
        isMultiSelectMode = true;
        updateMultiSelectButtons();
        renderBooks(books);
    });

    cancelSelectionBtn.addEventListener('click', () => {
        isMultiSelectMode = false;
        selectedBooks.clear();
        updateMultiSelectButtons();
        renderBooks(books);
    });

    selectAllBtn.addEventListener('click', () => {
        const allVisibleBookIds = books.map(b => b.book_id);
        const allSelectedOnPage = allVisibleBookIds.length > 0 && allVisibleBookIds.every(id => selectedBooks.has(id));

        if (allSelectedOnPage) {
            allVisibleBookIds.forEach(id => selectedBooks.delete(id));
        } else {
            allVisibleBookIds.forEach(id => selectedBooks.add(id));
        }
        renderBooks(books);
        updateMultiSelectButtons();
    });

    multiDeleteBtn.addEventListener('click', async () => {
        const bookIds = [...selectedBooks];
        if (bookIds.length === 0) {
            return showErrorToast("No Books Selected", "Please select books to delete.");
        }

        const isConfirmed = await showConfirmationModal(
            `Delete ${bookIds.length} Books?`,
            `Are you sure you want to permanently delete the selected ${bookIds.length} book(s)? This action cannot be undone.`,
            "Yes, Delete All"
        );

        if (!isConfirmed) return;

        showLoadingModal("Deleting Books...", `Processing ${bookIds.length} book(s).`);

        try {
            const res = await fetch('api/librarian/booksmanagement/deleteMultiple', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ book_ids: bookIds })
            });

            const data = await res.json();
            Swal.close();

            if (data.success) {
                showSuccessToast("Deletion Successful", data.message);
            } else {
                let errorMessage = data.message;
                if (data.errors && data.errors.length > 0) {
                    errorMessage += ` ${data.errors.join(' ')}`;
                }
                showErrorToast("Deletion Failed", errorMessage);
            }

            isMultiSelectMode = false;
            selectedBooks.clear();
            updateMultiSelectButtons();
            loadBooks(1, false);

        } catch (err) {
            Swal.close();
            console.error("Multi-delete error:", err);
            showErrorToast("Network Error", "An error occurred while connecting to the server.");
        }
    });

    // ==========================
    // INIT
    // ==========================
    loadBooks(currentPage);

    // ==========================
    // HISTORY MODAL LOGIC
    // ==========================
    window.openHistoryModal = async (bookId) => {
        if (!bookId) return;
        
        showLoadingModal("Loading History...", "Retrieving borrowing records.");
        
        try {
            const res = await fetch(`api/librarian/booksmanagement/history/${bookId}`);
            if (!res.ok) throw new Error("Failed to fetch history.");
            const data = await res.json();
            
            Swal.close();

            if (data.success && Array.isArray(data.history)) {
                renderHistory(data.history);
                openModal(historyModal);
            } else {
                showErrorToast("Error", data.message || "Could not retrieve history.");
            }
        } catch (err) {
            Swal.close();
            console.error("History fetch error:", err);
            showErrorToast("Error", "An error occurred while fetching history.");
        }
    };

    function renderHistory(history) {
        historyTableBody.innerHTML = "";
        
        if (history.length === 0) {
            historyTableContainer.classList.add("hidden");
            historyEmptyState.classList.remove("hidden");
            return;
        }

        historyTableContainer.classList.remove("hidden");
        historyEmptyState.classList.add("hidden");

        history.forEach(h => {
            const fullName = `${h.first_name} ${h.last_name}`;
            
            // Format Borrowed Date
            const bDateObj = h.borrowed_at ? new Date(h.borrowed_at) : null;
            const bDateStr = bDateObj ? bDateObj.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
            const bTimeStr = bDateObj ? bDateObj.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
            const fullBorrowedDateTime = bDateObj ? `
                <div class="flex flex-col">
                    <span class="text-gray-800 font-bold text-[15px]">${bDateStr}</span>
                    <span class="text-[12px] text-gray-500 font-medium mt-0.5">${bTimeStr}</span>
                </div>
            ` : 'N/A';

            // Format Returned Date
            const rDateObj = h.returned_at ? new Date(h.returned_at) : null;
            const rDateStr = rDateObj ? rDateObj.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
            const rTimeStr = rDateObj ? rDateObj.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', hour12: true }) : '';
            
            let fullReturnedDateTime = '';
            if (h.status === 'returned' && rDateObj) {
                fullReturnedDateTime = `
                    <div class="flex flex-col">
                        <span class="text-green-700 font-bold text-[15px]">${rDateStr}</span>
                        <span class="text-[12px] text-green-600 font-medium mt-0.5">${rTimeStr}</span>
                    </div>
                `;
            } else {
                fullReturnedDateTime = `<span class="text-gray-400 italic text-[13px]">In Circulation</span>`;
            }
            
            const statusClass = h.status === 'returned' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-orange-100 text-orange-700 border-orange-200';
            
            const row = `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-5 px-6 font-bold text-gray-900 text-[16px]">${fullName}</td>
                    <td class="py-5 px-6">
                        <div class="flex flex-col">
                            <span class="text-gray-800 font-mono text-[18px] font-black tracking-tight">${h.identifier}</span>
                            <span class="text-[11px] uppercase text-gray-500 font-bold tracking-widest">${h.role}</span>
                        </div>
                    </td>
                    <td class="py-5 px-6">${fullBorrowedDateTime}</td>
                    <td class="py-5 px-6">${fullReturnedDateTime}</td>
                    <td class="py-5 px-6 text-center">
                        <span class="px-3 py-1 rounded-full text-[11px] font-black uppercase border-2 ${statusClass}">
                            ${h.status}
                        </span>
                    </td>
                </tr>
            `;
            historyTableBody.insertAdjacentHTML('beforeend', row);
        });
    }

    closeHistoryModal?.addEventListener("click", () => closeModal(historyModal));
    closeHistoryBtn?.addEventListener("click", () => closeModal(historyModal));
    historyModal?.addEventListener("click", e => {
        if (e.target === historyModal) closeModal(historyModal);
    });
});