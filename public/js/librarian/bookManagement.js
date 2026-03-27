document.addEventListener("DOMContentLoaded", () => {
    // --- Elements ---
    const bookTableBody = document.getElementById("bookTableBody");
    const bookSearchInput = document.getElementById("bookSearchInput");
    const sortDropdownBtn = document.getElementById("sortDropdownBtn");
    const sortDropdownValue = document.getElementById("sortDropdownValue");
    const sortDropdownMenu = document.getElementById("sortDropdownMenu");
    const statusDropdownBtn = document.getElementById("statusDropdownBtn");
    const statusDropdownValue = document.getElementById("statusDropdownValue");
    const statusDropdownMenu = document.getElementById("statusDropdownMenu");
    const campusDropdownBtn = document.getElementById("campusDropdownBtn");
    const campusDropdownValue = document.getElementById("campusDropdownValue");
    const campusDropdownMenu = document.getElementById("campusDropdownMenu");
    const resultsIndicator = document.getElementById("resultsIndicator");

    const addBookModal = document.getElementById("addBookModal");
    const openAddBookBtn = document.getElementById("openAddBookBtn");
    const closeAddBookModal = document.getElementById("closeAddBookModal");
    const cancelAddBook = document.getElementById("cancelAddBook");
    const addBookForm = document.getElementById("addBookForm");

    const editBookModal = document.getElementById("editBookModal");
    const closeEditBookModal = document.getElementById("closeEditBookModal");
    const cancelEditBook = document.getElementById("cancelEditBook");
    const editBookForm = document.getElementById("editBookForm");

    const viewBookModal = document.getElementById("viewBookModal");
    const closeViewModal = document.getElementById("closeViewModal");
    const closeViewModalBtn = document.getElementById("closeViewModalBtn");

    const historyModal = document.getElementById("historyModal");
    const closeHistoryModal = document.getElementById("closeHistoryModal");
    const closeHistoryBtn = document.getElementById("closeHistoryBtn");

    const multiSelectBtn = document.getElementById("multiSelectBtn");
    const multiSelectActions = document.getElementById("multiSelectActions");
    const selectAllBtn = document.getElementById("selectAllBtn");
    const cancelSelectionBtn = document.getElementById("cancelSelectionBtn");
    const multiDeleteBtn = document.getElementById("multiDeleteBtn");
    const selectionCount = document.getElementById("selectionCount");

    // --- State ---
    let books = [];
    let currentSort = "default";
    let currentStatus = "All Status";
    let currentCampusId = 0;
    let currentPage = 1;
    let limit = 30;
    let totalCount = 0;
    let isMultiSelectMode = false;
    let selectedBookIds = new Set();
    let searchTimeout;

    // --- Helpers ---
    const showToast = (title, icon = "success") => {
        Swal.fire({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            icon: icon,
            title: title,
        });
    };

    // --- Fetching ---
    const fetchBooks = async () => {
        try {
            const search = bookSearchInput.value.trim();
            const offset = (currentPage - 1) * limit;

            const params = new URLSearchParams({
                search,
                status: currentStatus,
                sort: currentSort,
                campus_id: currentCampusId,
                limit,
                offset,
            });

            const response = await fetch(`api/librarian/booksmanagement/fetch?${params}`);
            const data = await response.json();

            if (data.success) {
                books = data.books;
                totalCount = data.totalCount;
                renderTable();
                renderPagination();
                updateResultsIndicator();
            }
        } catch (error) {
            console.error("Error fetching books:", error);
        }
    };

    const loadCampuses = async () => {
        try {
            const response = await fetch("api/campuses/all");
            const data = await response.json();

            if (data.success) {
                // Populate Filter Dropdown
                const filterMenu = document.getElementById("campusDropdownMenu");
                const allOption = filterMenu.querySelector('.campus-item');
                filterMenu.innerHTML = '';
                filterMenu.appendChild(allOption);

                // Populate Modal Selects
                const addSelect = addBookForm.querySelector('select[name="campus_id"]');
                const editSelect = editBookForm.querySelector('select[name="campus_id"]');
                
                addSelect.innerHTML = '<option value="">Select Campus</option>';
                editSelect.innerHTML = '<option value="">Select Campus</option>';

                data.campuses.forEach(campus => {
                    const item = document.createElement("div");
                    item.className = "campus-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm";
                    item.textContent = campus.campus_name;
                    item.onclick = () => selectCampus(item, campus.campus_id, campus.campus_name);
                    filterMenu.appendChild(item);

                    const optAdd = document.createElement("option");
                    optAdd.value = campus.campus_id;
                    optAdd.textContent = campus.campus_name;
                    addSelect.appendChild(optAdd);

                    const optEdit = document.createElement("option");
                    optEdit.value = campus.campus_id;
                    optEdit.textContent = campus.campus_name;
                    editSelect.appendChild(optEdit);
                });
            }
        } catch (error) {
            console.error("Error loading campuses:", error);
        }
    };

    const renderTable = () => {
        bookTableBody.innerHTML = "";
        if (books.length === 0) {
            bookTableBody.innerHTML = `<tr><td colspan="6" class="py-10 text-center text-gray-500">No books found.</td></tr>`;
            return;
        }

        books.forEach((book) => {
            const isSelected = selectedBookIds.has(book.book_id);
            const row = document.createElement("tr");
            row.className = `hover:bg-orange-50 transition-colors ${isSelected ? "bg-orange-100" : ""}`;
            row.innerHTML = `
                <td class="py-3 px-4 ${isMultiSelectMode ? "" : "hidden"}">
                    <input type="checkbox" class="accent-orange-500" ${isSelected ? "checked" : ""} onchange="toggleBookSelection(${book.book_id})">
                </td>
                <td class="py-3 px-4">
                    <p class="font-medium text-gray-900">${book.title}</p>
                    <p class="text-xs text-gray-500 font-mono">${book.accession_number} | ${book.call_number}</p>
                </td>
                <td class="py-3 px-4 text-center">
                    <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded-md">
                        ${book.campus_name || "N/A"}
                    </span>
                </td>
                <td class="py-3 px-4 text-gray-600">${book.author || "N/A"}</td>
                <td class="py-3 px-4 text-center">
                    <span class="px-2 py-1 rounded-full text-[10px] font-bold ${getStatusClass(book.availability)}">
                        ${book.availability.toUpperCase()}
                    </span>
                </td>
                <td class="py-3 px-4">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick="viewBook(${book.book_id})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-md transition" title="View Details">
                            <i class="ph ph-eye text-lg"></i>
                        </button>
                        <button onclick="editBook(${book.book_id})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md transition" title="Edit Book">
                            <i class="ph ph-note-pencil text-lg"></i>
                        </button>
                        <button onclick="deleteBook(${book.book_id}, '${book.title}')" class="p-1.5 text-red-600 hover:bg-red-50 rounded-md transition" title="Delete Book">
                            <i class="ph ph-trash text-lg"></i>
                        </button>
                    </div>
                </td>
            `;
            bookTableBody.appendChild(row);
        });
    };

    const getStatusClass = (status) => {
        switch (status.toLowerCase()) {
            case "available": return "bg-green-100 text-green-700";
            case "borrowed": return "bg-blue-100 text-blue-700";
            case "damaged": return "bg-yellow-100 text-yellow-700";
            case "lost": return "bg-red-100 text-red-700";
            default: return "bg-gray-100 text-gray-700";
        }
    };

    const updateResultsIndicator = () => {
        const start = (currentPage - 1) * limit + 1;
        const end = Math.min(currentPage * limit, totalCount);
        resultsIndicator.innerHTML = totalCount > 0 
            ? `Showing <span class="text-gray-900 font-bold">${start}-${end}</span> of <span class="text-gray-900 font-bold">${totalCount}</span> books`
            : "No books to display";
    };

    const renderPagination = () => {
        const paginationList = document.getElementById("paginationList");
        paginationList.innerHTML = "";
        const totalPages = Math.ceil(totalCount / limit);
        if (totalPages <= 1) return;

        // Previous
        paginationList.innerHTML += `
            <li>
                <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? "disabled" : ""} 
                    class="flex items-center gap-1 px-3 py-1.5 rounded-lg hover:bg-orange-50 text-gray-600 disabled:opacity-50 transition">
                    <i class="ph ph-caret-left"></i> Previous
                </button>
            </li>
        `;

        // Pages
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                paginationList.innerHTML += `
                    <li>
                        <button onclick="changePage(${i})" 
                            class="w-9 h-9 flex items-center justify-center rounded-full transition ${currentPage === i ? "bg-orange-600 text-white shadow-md" : "hover:bg-orange-50 text-gray-600"}">
                            ${i}
                        </button>
                    </li>
                `;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                paginationList.innerHTML += `<li><span class="px-2 text-gray-400">...</span></li>`;
            }
        }

        // Next
        paginationList.innerHTML += `
            <li>
                <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? "disabled" : ""} 
                    class="flex items-center gap-1 px-3 py-1.5 rounded-lg hover:bg-orange-50 text-gray-600 disabled:opacity-50 transition">
                    Next <i class="ph ph-caret-right"></i>
                </button>
            </li>
        `;
    };

    // --- Actions ---
    window.changePage = (page) => {
        currentPage = page;
        fetchBooks();
    };

    window.selectSort = (el, val) => {
        currentSort = val;
        sortDropdownValue.textContent = el.textContent;
        currentPage = 1;
        fetchBooks();
        sortDropdownMenu.classList.add("hidden");
    };

    window.selectStatus = (el, val) => {
        currentStatus = val;
        statusDropdownValue.textContent = el.textContent;
        currentPage = 1;
        fetchBooks();
        statusDropdownMenu.classList.add("hidden");
    };

    window.selectCampus = (el, id, name) => {
        currentCampusId = id;
        campusDropdownValue.textContent = name;
        currentPage = 1;
        fetchBooks();
        campusDropdownMenu.classList.add("hidden");
    };

    window.toggleBookSelection = (id) => {
        if (selectedBookIds.has(id)) {
            selectedBookIds.delete(id);
        } else {
            selectedBookIds.add(id);
        }
        selectionCount.textContent = selectedBookIds.size;
        renderTable();
    };

    window.viewBook = async (id) => {
        try {
            const response = await fetch(`api/librarian/booksmanagement/details/${id}`);
            const data = await response.json();
            if (data.success) {
                const book = data.book;
                document.getElementById("viewModalTitle").textContent = book.title;
                document.getElementById("viewModalAuthor").textContent = `by ${book.author || "Unknown"}`;
                document.getElementById("viewModalStatus").textContent = book.availability;
                document.getElementById("viewModalCampus").textContent = book.campus_name || "N/A";
                document.getElementById("viewModalAccessionNumber").textContent = book.accession_number;
                document.getElementById("viewModalCallNumber").textContent = book.call_number;
                document.getElementById("viewModalIsbn").textContent = book.book_isbn || "N/A";
                document.getElementById("viewModalSubject").textContent = book.subject || "N/A";
                document.getElementById("viewModalPlace").textContent = book.book_place || "N/A";
                document.getElementById("viewModalPublisher").textContent = book.book_publisher || "N/A";
                document.getElementById("viewModalYear").textContent = book.year || "N/A";
                document.getElementById("viewModalEdition").textContent = book.book_edition || "N/A";
                document.getElementById("viewModalSupplementary").textContent = book.book_supplementary || "N/A";
                document.getElementById("viewModalDescription").textContent = book.description || "No description provided.";
                
                const img = document.getElementById("viewModalImg");
                if (book.cover) {
                    img.src = book.cover;
                    img.classList.remove("hidden");
                } else {
                    img.classList.add("hidden");
                }

                viewBookModal.classList.remove("hidden", "opacity-0");
                viewBookModal.classList.add("opacity-100");
            }
        } catch (error) {
            console.error("Error viewing book:", error);
        }
    };

    window.editBook = async (id) => {
        try {
            const response = await fetch(`api/librarian/booksmanagement/details/${id}`);
            const data = await response.json();
            if (data.success) {
                const book = data.book;
                document.getElementById("edit_book_id").value = book.book_id;
                document.getElementById("edit_accession_number").value = book.accession_number;
                document.getElementById("edit_call_number").value = book.call_number;
                document.getElementById("edit_title").value = book.title;
                document.getElementById("edit_author").value = book.author;
                document.getElementById("edit_availability").value = book.availability;
                document.getElementById("edit_campus_id").value = book.campus_id || "";
                document.getElementById("edit_book_isbn").value = book.book_isbn || "";
                document.getElementById("edit_book_place").value = book.book_place || "";
                document.getElementById("edit_book_publisher").value = book.book_publisher || "";
                document.getElementById("edit_year").value = book.year || "";
                document.getElementById("edit_book_edition").value = book.book_edition || "";
                document.getElementById("edit_book_supplementary").value = book.book_supplementary || "";
                document.getElementById("edit_subject").value = book.subject || "";
                document.getElementById("edit_description").value = book.description || "";
                
                if (book.cover) {
                    document.getElementById("editPreviewImage").src = book.cover;
                    document.getElementById("editPreviewContainer").classList.remove("hidden");
                    document.getElementById("removeImageBtn").classList.remove("hidden");
                } else {
                    document.getElementById("editPreviewContainer").classList.add("hidden");
                    document.getElementById("removeImageBtn").classList.add("hidden");
                }

                editBookModal.classList.remove("hidden");
            }
        } catch (error) {
            console.error("Error loading book for edit:", error);
        }
    };

    window.deleteBook = async (id, title) => {
        const result = await Swal.fire({
            title: "Are you sure?",
            text: `You are about to delete "${title}". This action cannot be undone.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Yes, delete it!",
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`api/librarian/booksmanagement/delete/${id}`, { method: "POST" });
                const data = await response.json();
                if (data.success) {
                    showToast("Book deleted successfully!");
                    fetchBooks();
                } else {
                    Swal.fire("Error", data.message, "error");
                }
            } catch (error) {
                console.error("Error deleting book:", error);
            }
        }
    };

    // --- Event Listeners ---
    bookSearchInput.addEventListener("input", () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage = 1;
            fetchBooks();
        }, 500);
    });

    [sortDropdownBtn, statusDropdownBtn, campusDropdownBtn].forEach(btn => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            const menu = btn.nextElementSibling;
            document.querySelectorAll(".absolute.z-20").forEach(m => {
                if (m !== menu) m.classList.add("hidden");
            });
            menu.classList.toggle("hidden");
        });
    });

    document.addEventListener("click", () => {
        document.querySelectorAll(".absolute.z-20").forEach(m => m.classList.add("hidden"));
    });

    openAddBookBtn.addEventListener("click", () => addBookModal.classList.remove("hidden"));
    [closeAddBookModal, cancelAddBook].forEach(btn => btn.addEventListener("click", () => {
        addBookModal.classList.add("hidden");
        addBookForm.reset();
        document.getElementById("previewContainer").classList.add("hidden");
    }));

    [closeEditBookModal, cancelEditBook].forEach(btn => btn.addEventListener("click", () => {
        editBookModal.classList.add("hidden");
        editBookForm.reset();
    }));

    [closeViewModal, closeViewModalBtn].forEach(btn => btn.addEventListener("click", () => {
        viewBookModal.classList.add("opacity-0");
        setTimeout(() => viewBookModal.classList.add("hidden"), 300);
    }));

    addBookForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const formData = new FormData(addBookForm);
        try {
            const response = await fetch("api/librarian/booksmanagement/add", {
                method: "POST",
                body: formData,
            });
            const data = await response.json();
            if (data.success) {
                showToast("Book added successfully!");
                addBookModal.classList.add("hidden");
                addBookForm.reset();
                fetchBooks();
            } else {
                Swal.fire("Error", data.message, "error");
            }
        } catch (error) {
            console.error("Error adding book:", error);
        }
    });

    editBookForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const bookId = document.getElementById("edit_book_id").value;
        const formData = new FormData(editBookForm);
        try {
            const response = await fetch(`api/librarian/booksmanagement/update/${bookId}`, {
                method: "POST",
                body: formData,
            });
            const data = await response.json();
            if (data.success) {
                showToast("Book updated successfully!");
                editBookModal.classList.add("hidden");
                fetchBooks();
            } else {
                Swal.fire("Error", data.message, "error");
            }
        } catch (error) {
            console.error("Error updating book:", error);
        }
    });

    // Multi-select Logic
    multiSelectBtn.addEventListener("click", () => {
        isMultiSelectMode = true;
        document.getElementById("multi-select-header").classList.remove("hidden");
        multiSelectBtn.classList.add("hidden");
        multiSelectActions.classList.remove("hidden");
        renderTable();
    });

    cancelSelectionBtn.addEventListener("click", () => {
        isMultiSelectMode = false;
        selectedBookIds.clear();
        document.getElementById("multi-select-header").classList.add("hidden");
        multiSelectBtn.classList.remove("hidden");
        multiSelectActions.classList.add("hidden");
        renderTable();
    });

    selectAllBtn.addEventListener("click", () => {
        if (selectedBookIds.size === books.length) {
            selectedBookIds.clear();
        } else {
            books.forEach(b => selectedBookIds.add(b.book_id));
        }
        selectionCount.textContent = selectedBookIds.size;
        renderTable();
    });

    multiDeleteBtn.addEventListener("click", async () => {
        if (selectedBookIds.size === 0) return;

        const result = await Swal.fire({
            title: "Bulk Delete",
            text: `Are you sure you want to delete ${selectedBookIds.size} books?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, delete them!",
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch("api/librarian/booksmanagement/deleteMultiple", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ book_ids: Array.from(selectedBookIds) }),
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message);
                    selectedBookIds.clear();
                    cancelSelectionBtn.click();
                    fetchBooks();
                }
            } catch (error) {
                console.error("Error bulk deleting books:", error);
            }
        }
    });

    // Image Previews
    const setupPreview = (inputId, previewImgId, containerId, textId) => {
        const input = document.getElementById(inputId);
        input.addEventListener("change", () => {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById(previewImgId).src = e.target.result;
                    document.getElementById(containerId).classList.remove("hidden");
                    if (textId) document.getElementById(textId).textContent = "Change Image";
                };
                reader.readAsDataURL(file);
            }
        });
    };

    setupPreview("book_image", "previewImage", "previewContainer", "uploadText");
    setupPreview("edit_book_image", "editPreviewImage", "editPreviewContainer", "editUploadText");

    document.getElementById("removeImageBtn").addEventListener("click", () => {
        document.getElementById("edit_remove_image").value = "1";
        document.getElementById("editPreviewContainer").classList.add("hidden");
        document.getElementById("removeImageBtn").classList.add("hidden");
        document.getElementById("editUploadText").textContent = "Upload Image";
    });

    // Initial Load
    loadCampuses();
    fetchBooks();
});