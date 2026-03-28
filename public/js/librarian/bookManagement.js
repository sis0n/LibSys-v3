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

  // Import Modal Elements
  const bulkImportBtn = document.getElementById("bulkImportBtn");
  const importModal = document.getElementById("importModal");
  const closeImportModal = document.getElementById("closeImportModal");
  const cancelImport = document.getElementById("cancelImport");
  const bulkImportForm = document.getElementById("bulkImportForm");
  const csvFile = document.getElementById("csvFile");
  const uploadText = document.querySelector(
    '#importModal label[for="csvFile"] p:first-of-type',
  ); // Get the specific span for text
  const uploadInstruction = document.querySelector(
    '#importModal label[for="csvFile"] p:last-of-type',
  ); // Get the specific span for instruction text

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
  function showSuccessToast(title, body = "Successfully processed.") {
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
        popup:
          "!rounded-xl !shadow-md !border-2 !border-green-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#f0fff5] shadow-[0_0_8px_#22c55e70]",
      },
    });
  }

  function showErrorToast(title, body = "Please check the input details.") {
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
        popup:
          "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]",
      },
    });
  }

  function showLoadingModal(
    message = "Processing request...",
    subMessage = "Please wait.",
  ) {
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
        popup:
          "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
      },
    });
  }

  // --- Modals & Modifiers ---
  const openModal = (modal) => {
    modal.classList.remove("hidden");
    modal.classList.add("opacity-100");
    modal
      .querySelector(".animate-fadeIn, .animate-scaleIn")
      .classList.remove("animate-fadeOut", "opacity-0", "scale-95");
  };

  const closeModal = (modal) => {
    modal.classList.add("opacity-0");
    modal.classList.remove("opacity-100");
    modal
      .querySelector(".animate-fadeIn, .animate-scaleIn")
      .classList.add("animate-fadeOut", "opacity-0", "scale-95");
    setTimeout(() => {
      modal.classList.add("hidden");
      modal
        .querySelector(".animate-fadeOut, .opacity-0")
        .classList.remove("animate-fadeOut", "opacity-0", "scale-95");
    }, 300);
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

      const response = await fetch(
        `api/librarian/booksmanagement/fetch?${params}`,
      );
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
        const allOption = filterMenu.querySelector(".campus-item");
        filterMenu.innerHTML = ""; // Clear existing options
        filterMenu.appendChild(allOption); // Add back the "All Campuses" option

        // Populate Modal Selects
        const addSelect = addBookForm.querySelector('select[name="campus_id"]');
        const editSelect = editBookForm.querySelector(
          'select[name="campus_id"]',
        );

        addSelect.innerHTML = '<option value="">Select Campus</option>'; // Reset add modal select
        editSelect.innerHTML = '<option value="">Select Campus</option>'; // Reset edit modal select

        data.campuses.forEach((campus) => {
          const item = document.createElement("div");
          item.className =
            "campus-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm";
          item.textContent = campus.campus_name;
          item.onclick = () =>
            selectCampus(item, campus.campus_id, campus.campus_name);
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
      case "available":
        return "bg-green-100 text-green-700";
      case "borrowed":
        return "bg-blue-100 text-blue-700";
      case "damaged":
        return "bg-yellow-100 text-yellow-700";
      case "lost":
        return "bg-red-100 text-red-700";
      default:
        return "bg-gray-100 text-gray-700";
    }
  };

  const updateResultsIndicator = () => {
    const start = (currentPage - 1) * limit + 1;
    const end = Math.min(currentPage * limit, totalCount);
    resultsIndicator.innerHTML =
      totalCount > 0
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
      if (
        i === 1 ||
        i === totalPages ||
        (i >= currentPage - 1 && i <= currentPage + 1)
      ) {
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
    if (page < 1 || page > Math.ceil(totalCount / limit)) return;
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
    renderTable(); // Re-render to update row selection visuals
  };

  window.viewBook = async (id) => {
    try {
      const response = await fetch(
        `api/librarian/booksmanagement/details/${id}`,
      );
      const data = await response.json();
      if (data.success) {
        const book = data.book;
        document.getElementById("viewModalTitle").textContent = book.title;
        document.getElementById("viewModalAuthor").textContent =
          `by ${book.author || "Unknown"}`;
        document.getElementById("viewModalStatus").textContent =
          book.availability;
        document.getElementById("viewModalCampus").textContent =
          book.campus_name || "N/A";
        document.getElementById("viewModalAccessionNumber").textContent =
          book.accession_number;
        document.getElementById("viewModalCallNumber").textContent =
          book.call_number;
        document.getElementById("viewModalIsbn").textContent =
          book.book_isbn || "N/A";
        document.getElementById("viewModalSubject").textContent =
          book.subject || "N/A";
        document.getElementById("viewModalPlace").textContent =
          book.book_place || "N/A";
        document.getElementById("viewModalPublisher").textContent =
          book.book_publisher || "N/A";
        document.getElementById("viewModalYear").textContent =
          book.year || "N/A";
        document.getElementById("viewModalEdition").textContent =
          book.book_edition || "N/A";
        document.getElementById("viewModalSupplementary").textContent =
          book.book_supplementary || "N/A";
        document.getElementById("viewModalDescription").textContent =
          book.description || "No description provided.";

        const img = document.getElementById("viewModalImg");
        if (book.cover) {
          img.src = book.cover;
          img.classList.remove("hidden");
        } else {
          img.classList.add("hidden");
        }

        openModal(viewBookModal);
      }
    } catch (error) {
      console.error("Error viewing book:", error);
      showToast("Failed to load book details.", "error");
    }
  };

  window.editBook = async (id) => {
    try {
      const response = await fetch(
        `api/librarian/booksmanagement/details/${id}`,
      );
      const data = await response.json();
      if (data.success) {
        const book = data.book;
        document.getElementById("edit_book_id").value = book.book_id;
        document.getElementById("edit_accession_number").value =
          book.accession_number;
        document.getElementById("edit_call_number").value = book.call_number;
        document.getElementById("edit_title").value = book.title;
        document.getElementById("edit_author").value = book.author;
        document.getElementById("edit_availability").value = book.availability;
        document.getElementById("edit_campus_id").value = book.campus_id || "";
        document.getElementById("edit_book_isbn").value = book.book_isbn || "";
        document.getElementById("edit_book_place").value =
          book.book_place || "";
        document.getElementById("edit_book_publisher").value =
          book.book_publisher || "";
        document.getElementById("edit_year").value = book.year || "";
        document.getElementById("edit_book_edition").value =
          book.book_edition || "";
        document.getElementById("edit_book_supplementary").value =
          book.book_supplementary || "";
        document.getElementById("edit_subject").value = book.subject || "";
        document.getElementById("edit_description").value =
          book.description || "";

        if (book.cover) {
          document.getElementById("editPreviewImage").src = book.cover;
          document
            .getElementById("editPreviewContainer")
            .classList.remove("hidden");
          document.getElementById("removeImageBtn").classList.remove("hidden");
          document.getElementById("editUploadText").textContent =
            "Change Image";
        } else {
          document
            .getElementById("editPreviewContainer")
            .classList.add("hidden");
          document.getElementById("removeImageBtn").classList.add("hidden");
          document.getElementById("editUploadText").textContent =
            "Upload Image";
        }

        openModal(editBookModal);
      }
    } catch (error) {
      console.error("Error loading book for edit:", error);
      showToast("Failed to load book for editing.", "error");
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
        const response = await fetch(
          `api/librarian/booksmanagement/delete/${id}`,
          { method: "POST" },
        );
        const data = await response.json();
        if (data.success) {
          showToast("Book deleted successfully!");
          fetchBooks(); // Refresh list
        } else {
          Swal.fire("Error", data.message, "error");
        }
      } catch (error) {
        console.error("Error deleting book:", error);
        showToast("Failed to delete book.", "error");
      }
    }
  };

  // --- Bulk Import Modal Handling ---
  bulkImportBtn.addEventListener("click", () => {
    openModal(importModal);
    bulkImportForm.reset(); // Reset form when modal opens
    if (uploadText)
      uploadText.textContent = "Drop CSV file here or click to browse";
    if (uploadInstruction) uploadInstruction.style.display = ""; // Ensure instruction is visible
  });

  [closeImportModal, cancelImport].forEach((btn) =>
    btn.addEventListener("click", () => closeModal(importModal)),
  );

  // Close modal when clicking outside content
  importModal.addEventListener("click", (e) => {
    if (e.target === importModal) {
      closeModal(importModal);
    }
  });

  // --- Bulk Import Form Submission ---
  bulkImportForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(bulkImportForm);
    const fileInput = document.getElementById("csvFile");
    const file = fileInput.files[0];

    if (!file) {
      Swal.fire("Error", "Please select a CSV file to upload.", "warning");
      return;
    }

    if (!file.name.toLowerCase().endsWith(".csv")) {
      Swal.fire(
        "Error",
        "Invalid file type. Please upload a CSV file.",
        "warning",
      );
      return;
    }

    showLoadingModal(
      "Importing Books...",
      "Please wait while we process your CSV file.",
    );
    try {
      const response = await fetch(
        "api/librarian/booksmanagement/bulkImport",
        {
          method: "POST",
          body: formData,
        },
      );
      const data = await response.json();

      if (data.success) {
        showSuccessToast(
          "Import Successful!",
          `Imported ${data.imported} books.`,
        );
        closeModal(importModal);
        fetchBooks();
      } else {
        showErrorToast(
          "Import Failed",
          data.message || "An unknown error occurred during import.",
        );
      }
    } catch (error) {
      console.error("Full error:", error);

      try {
        const text = await response.text();
        console.error("Raw response:", text);
      } catch (e) {}

      showErrorToast(
        "Import Failed",
        "An error occurred while processing the bulk import. Please try again.",
      );
    }
  });

  csvFile.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file) {
      if (uploadText) uploadText.textContent = file.name;
      if (uploadInstruction) uploadInstruction.style.display = "none";

      bulkImportForm.requestSubmit();
    } else {
      if (uploadText)
        uploadText.textContent = "Drop CSV file here or click to browse";
      if (uploadInstruction) uploadInstruction.style.display = "";
    }
  });

  // --- Event Listeners ---
  bookSearchInput.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      currentPage = 1;
      fetchBooks();
    }, 500);
  });

  [sortDropdownBtn, statusDropdownBtn, campusDropdownBtn].forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const menu = btn.nextElementSibling;
      // Close other dropdowns if this one is opened
      document.querySelectorAll(".absolute.z-20").forEach((m) => {
        if (m !== menu) m.classList.add("hidden");
      });
      menu.classList.toggle("hidden");
    });
  });

  // Close dropdowns when clicking anywhere else on the document
  document.addEventListener("click", () => {
    document
      .querySelectorAll(".absolute.z-20")
      .forEach((m) => m.classList.add("hidden"));
  });

  openAddBookBtn.addEventListener("click", () => openModal(addBookModal));
  [closeAddBookModal, cancelAddBook].forEach((btn) =>
    btn.addEventListener("click", () => {
      closeModal(addBookModal);
      addBookForm.reset();
      document.getElementById("previewContainer").classList.add("hidden"); // Hide image preview on close
    }),
  );
  // Close modal when clicking outside content for Add Book Modal
  addBookModal.addEventListener("click", (e) => {
    if (e.target === addBookModal) {
      closeModal(addBookModal);
      addBookForm.reset();
      document.getElementById("previewContainer").classList.add("hidden");
    }
  });

  [closeEditBookModal, cancelEditBook].forEach((btn) =>
    btn.addEventListener("click", () => {
      closeModal(editBookModal);
      editBookForm.reset(); // Reset form on cancel/close
    }),
  );
  // Close modal when clicking outside content for Edit Book Modal
  editBookModal.addEventListener("click", (e) => {
    if (e.target === editBookModal) {
      closeModal(editBookModal);
      editBookForm.reset();
    }
  });

  [closeViewModal, closeViewModalBtn].forEach((btn) =>
    btn.addEventListener("click", () => closeModal(viewBookModal)),
  );
  // Close modal when clicking outside content for View Book Modal
  viewBookModal.addEventListener("click", (e) => {
    if (e.target === viewBookModal) {
      closeModal(viewBookModal);
    }
  });

  [closeHistoryModal, closeHistoryBtn].forEach((btn) =>
    btn.addEventListener("click", () => closeModal(historyModal)),
  );
  // Close modal when clicking outside content for History Modal
  historyModal.addEventListener("click", (e) => {
    if (e.target === historyModal) {
      closeModal(historyModal);
    }
  });

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
        closeModal(addBookModal);
        addBookForm.reset();
        // Hide image preview after successful add
        document.getElementById("previewContainer").classList.add("hidden");
        fetchBooks(); // Refresh list
      } else {
        Swal.fire("Error", data.message, "error");
      }
    } catch (error) {
      console.error("Error adding book:", error);
      showToast("Failed to add book.", "error");
    }
  });

  editBookForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const bookId = document.getElementById("edit_book_id").value;
    const formData = new FormData(editBookForm);
    try {
      const response = await fetch(
        `api/librarian/booksmanagement/update/${bookId}`,
        {
          method: "POST",
          body: formData,
        },
      );
      const data = await response.json();
      if (data.success) {
        showToast("Book updated successfully!");
        closeModal(editBookModal);
        fetchBooks(); // Refresh list
      } else {
        Swal.fire("Error", data.message, "error");
      }
    } catch (error) {
      console.error("Error updating book:", error);
      showToast("Failed to update book.", "error");
    }
  });

  // Multi-select Logic
  multiSelectBtn.addEventListener("click", () => {
    isMultiSelectMode = true;
    document.getElementById("multi-select-header").classList.remove("hidden");
    multiSelectBtn.classList.add("hidden"); // Hide multi-select trigger button
    multiSelectActions.classList.remove("hidden"); // Show delete/cancel actions
    selectionCount.textContent = selectedBookIds.size; // Update count display
    renderTable(); // Re-render table to show checkboxes
  });

  cancelSelectionBtn.addEventListener("click", () => {
    isMultiSelectMode = false;
    selectedBookIds.clear(); // Clear selections
    document.getElementById("multi-select-header").classList.add("hidden");
    multiSelectBtn.classList.remove("hidden"); // Show multi-select trigger button
    multiSelectActions.classList.add("hidden"); // Hide delete/cancel actions
    selectionCount.textContent = "0";
    renderTable(); // Re-render table to hide checkboxes
  });

  selectAllBtn.addEventListener("click", () => {
    if (selectedBookIds.size === books.length) {
      selectedBookIds.clear(); // Deselect all
    } else {
      // Select all
      books.forEach((b) => selectedBookIds.add(b.book_id));
    }
    selectionCount.textContent = selectedBookIds.size;
    renderTable(); // Re-render to update visuals
  });

  multiDeleteBtn.addEventListener("click", async () => {
    if (selectedBookIds.size === 0) {
      showToast("Please select books to delete.", "info");
      return;
    }

    const result = await Swal.fire({
      title: "Bulk Delete",
      html: `Are you sure you want to delete ${selectedBookIds.size} books? This action cannot be undone.`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Yes, delete them!",
    });

    if (result.isConfirmed) {
      try {
        const response = await fetch(
          "api/librarian/booksmanagement/deleteMultiple",
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ book_ids: Array.from(selectedBookIds) }),
          },
        );
        const data = await response.json();
        if (data.success) {
          showToast(`Successfully deleted ${data.deleted_count} book(s)!`);
          selectedBookIds.clear(); // Clear selections
          cancelSelectionBtn.click(); // Exit multi-select mode
          fetchBooks(); // Refresh list
        } else {
          // Display errors if any
          Swal.fire(
            "Error",
            `Failed to delete some books. ${data.errors.join("<br>")}`,
            "error",
          );
        }
      } catch (error) {
        console.error("Error bulk deleting books:", error);
        showToast("Failed to delete books. Please try again.", "error");
      }
    }
  });

  // Image Previews for Add Book
  const setupAddBookPreview = (
    inputId,
    previewImgId,
    containerId,
    textElementId,
  ) => {
    const input = document.getElementById(inputId);
    const previewImg = document.getElementById(previewImgId);
    const container = document.getElementById(containerId);
    const textElement = document.getElementById(textElementId);

    input.addEventListener("change", () => {
      const file = input.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          previewImg.src = e.target.result;
          container.classList.remove("hidden");
          if (textElement) textElement.textContent = "Change Image";
        };
        reader.readAsDataURL(file);
      } else {
        // Reset if file input is cleared
        previewImg.src = "";
        container.classList.add("hidden");
        if (textElement) textElement.textContent = "Upload Image";
      }
    });
  };

  // Image Previews for Edit Book
  const setupEditBookPreview = (
    inputId,
    previewImgId,
    containerId,
    textElementId,
    removeBtnId,
    removeInputId,
  ) => {
    const input = document.getElementById(inputId);
    const previewImg = document.getElementById(previewImgId);
    const container = document.getElementById(containerId);
    const textElement = document.getElementById(textElementId);
    const removeBtn = document.getElementById(removeBtnId);
    const removeInput = document.getElementById(removeInputId);

    input.addEventListener("change", () => {
      const file = input.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          previewImg.src = e.target.result;
          container.classList.remove("hidden");
          removeBtn.classList.remove("hidden"); // Show remove button
          if (textElement) textElement.textContent = "Change Image";
          removeInput.value = "0"; // Reset remove flag
        };
        reader.readAsDataURL(file);
      } else {
        // If user clears the file input, revert to original state if any, or hide.
        // This part might need more context on how to revert if not deleting.
        // For now, if file is cleared, we hide preview and show upload text.
        previewImg.src = "";
        container.classList.add("hidden");
        removeBtn.classList.add("hidden");
        if (textElement) textElement.textContent = "Upload Image";
        removeInput.value = "0";
      }
    });

    // Handler for removing the current image
    if (removeBtn) {
      removeBtn.addEventListener("click", () => {
        previewImg.src = "";
        container.classList.add("hidden");
        removeBtn.classList.add("hidden");
        if (textElement) textElement.textContent = "Upload Image";
        removeInput.value = "1"; // Mark for removal
        input.value = ""; // Clear the file input itself
      });
    }
  };

  // Initialize Image Previews
  setupAddBookPreview(
    "book_image",
    "previewImage",
    "previewContainer",
    "uploadText",
  );
  setupEditBookPreview(
    "edit_book_image",
    "editPreviewImage",
    "editPreviewContainer",
    "editUploadText",
    "removeImageBtn",
    "edit_remove_image",
  );

  // Initial Load
  loadCampuses();
  fetchBooks();
});
