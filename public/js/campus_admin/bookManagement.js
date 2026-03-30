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
  );
  const uploadInstruction = document.querySelector(
    '#importModal label[for="csvFile"] p:last-of-type',
  );

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
  function truncateText(text, length = 30) {
    if (!text) return "N/A";
    return text.length > length ? text.substring(0, length) + "..." : text;
  }

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

  async function showConfirmationModal(title, text, confirmText = "Confirm") {
    if (typeof Swal == "undefined") return confirm(title);
    const result = await Swal.fire({
      background: "transparent",
      buttonsStyling: false,
      width: "450px",
      html: `
                <div class="flex flex-col text-center">
                    <div class="flex justify-center mb-3">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 text-orange-600">
                            <i class="ph ph-warning-circle text-3xl"></i>
                        </div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">${title}</h3>
                    <p class="text-[14px] text-gray-700 mt-1">${text}</p>
                </div>
            `,
      showCancelButton: true,
      confirmButtonText: confirmText,
      cancelButtonText: "Cancel",
      customClass: {
        popup:
          "!rounded-xl !shadow-lg !p-6 !bg-white !border-2 !border-orange-400 shadow-[0_0_15px_#ffb34780]",
        confirmButton:
          "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700 !mx-2 !font-semibold !text-base",
        cancelButton:
          "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300 !mx-2 !font-semibold !text-base",
        actions: "!mt-4",
      },
    });
    return result.isConfirmed;
  }

  // --- Modals & Modifiers ---

  const openModal = (modal) => {
    modal.classList.remove("hidden");
    // Force reflow
    modal.offsetHeight;
    modal.classList.add("opacity-100");
    modal.classList.remove("opacity-0");
    
    const content = modal.querySelector(".animate-fadeIn, .animate-scaleIn, #viewBookModalContent");
    if (content) {
      content.classList.remove("animate-fadeOut", "opacity-0", "scale-95");
      if (content.id === "viewBookModalContent") {
        content.classList.remove("scale-95");
        content.classList.add("scale-100");
      }
    }
  };

  const closeModal = (modal) => {
    modal.classList.add("opacity-0");
    modal.classList.remove("opacity-100");
    
    const content = modal.querySelector(".animate-fadeIn, .animate-scaleIn, #viewBookModalContent");
    if (content) {
      content.classList.add("animate-fadeOut", "opacity-0");
      if (content.id === "viewBookModalContent") {
        content.classList.add("scale-95");
        content.classList.remove("scale-100");
      }
    }

    setTimeout(() => {
      modal.classList.add("hidden");
      if (content) {
        content.classList.remove("animate-fadeOut", "opacity-0");
      }
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
        `api/campus_admin/booksmanagement/fetch?${params}`,
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
        const filterMenu = document.getElementById("campusDropdownMenu");
        const allOption = filterMenu.querySelector(".campus-item");
        filterMenu.innerHTML = "";
        filterMenu.appendChild(allOption);

        const addSelect = addBookForm.querySelector('select[name="campus_id"]');
        const editSelect = editBookForm.querySelector(
          'select[name="campus_id"]',
        );

        addSelect.innerHTML = '<option value="">Select Campus</option>';
        editSelect.innerHTML = '<option value="">Select Campus</option>';

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
      row.className = `hover:bg-orange-50 transition-colors ${isSelected ? "bg-orange-100" : ""} ${book.is_active == 0 ? "opacity-60 bg-gray-50" : ""}`;
      row.innerHTML = `
                <td class="py-3 px-4 ${isMultiSelectMode ? "" : "hidden"}">
                    <input type="checkbox" class="accent-orange-500" ${isSelected ? "checked" : ""} onchange="toggleBookSelection(${book.book_id})">
                </td>
                <td class="py-3 px-4">
                    <div class="flex items-center gap-2">
                        ${book.is_active == 0 ? '<i class="ph ph-eye-slash text-gray-400" title="Inactive"></i>' : ""}
                        <div>
                            <p class="font-medium ${book.is_active == 0 ? "text-gray-500" : "text-gray-900"}">${truncateText(book.title, 35)}</p>
                            <p class="text-xs text-gray-500 font-mono">${book.accession_number} | ${book.call_number}</p>
                        </div>
                    </div>
                </td>
                <td class="py-3 px-4 text-center">
                    <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded-md">
                        ${book.campus_name || "N/A"}
                    </span>
                </td>
                <td class="py-3 px-4 text-gray-600">${book.author || "N/A"}</td>
                <td class="py-3 px-4 text-center">
                    <span onclick="toggleBookActive(${book.book_id}, ${book.is_active}, '${book.title.replace(/'/g, "\\'")}')" 
                          class="px-2 py-1 rounded-full text-[10px] font-bold cursor-pointer hover:opacity-80 transition ${getStatusClass(book.availability, book.is_active)}">
                        ${book.is_active == 1 ? book.availability.toUpperCase() : "INACTIVE"}
                    </span>
                </td>
                <td class="py-3 px-4">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick="viewHistory(${book.book_id})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-md transition" title="View History">
                            <i class="ph ph-eye text-lg"></i>
                        </button>
                        <button onclick="viewBook(${book.book_id})" class="p-1.5 text-orange-600 hover:bg-orange-50 rounded-md transition" title="View Details">
                            <i class="ph ph-info text-lg"></i>
                        </button>
                        <button onclick="editBook(${book.book_id})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md transition" title="Edit Book">
                            <i class="ph ph-note-pencil text-lg"></i>
                        </button>
                    </div>
                </td>
            `;
      bookTableBody.appendChild(row);
    });
  };

  const getStatusClass = (status, isActive = 1) => {
    if (isActive == 0) return "bg-gray-300 text-gray-700";
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

  window.toggleBookActive = async (id, currentStatus, title) => {
    const newStatus = currentStatus == 1 ? 0 : 1;
    const actionText = newStatus == 1 ? "reactivate" : "deactivate";
    const confirmBtnText = newStatus == 1 ? "Yes, Reactivate" : "Yes, Deactivate";

    const isConfirmed = await showConfirmationModal(
      `Confirm ${actionText.charAt(0).toUpperCase() + actionText.slice(1)}`,
      `Are you sure you want to ${actionText} **"${title}"**?`,
      confirmBtnText,
    );

    if (!isConfirmed) return;

    showLoadingModal(
      `${actionText.charAt(0).toUpperCase() + actionText.slice(1)}ing book...`,
      "Please wait.",
    );

    try {
      const endpoint =
        newStatus == 1
          ? `api/campus_admin/booksmanagement/reactivate/${id}`
          : `api/campus_admin/booksmanagement/delete/${id}`;

      const response = await fetch(endpoint, { method: "POST" });
      const data = await response.json();
      Swal.close();

      if (data.success) {
        showSuccessToast(
          "Success",
          `Book ${actionText}d successfully!`,
        );
        fetchBooks();
      } else {
        showErrorToast("Error", data.message);
      }
    } catch (error) {
      Swal.close();
      console.error(`Error ${actionText}ing book:`, error);
      showErrorToast("Error", `Failed to ${actionText} book.`);
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

    paginationList.innerHTML += `
            <li>
                <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? "disabled" : ""} 
                    class="flex items-center gap-1 px-3 py-1.5 rounded-lg hover:bg-orange-50 text-gray-600 disabled:opacity-50 transition">
                    <i class="ph ph-caret-left"></i> Previous
                </button>
            </li>
        `;

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

    paginationList.innerHTML += `
            <li>
                <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? "disabled" : ""} 
                    class="flex items-center gap-1 px-3 py-1.5 rounded-lg hover:bg-orange-50 text-gray-600 disabled:opacity-50 transition">
                    Next <i class="ph ph-caret-right"></i>
                </button>
            </li>
        `;
  };

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
    renderTable();
  };

  window.viewHistory = async (id) => {
    const historyTableBody = document.getElementById("historyTableBody");
    const historyEmptyState = document.getElementById("historyEmptyState");
    const historyTableContainer = document.getElementById(
      "historyTableContainer",
    );

    historyTableBody.innerHTML = "";
    historyEmptyState.classList.add("hidden");
    historyTableContainer.classList.remove("hidden");

    try {
      const response = await fetch(
        `api/campus_admin/booksmanagement/history/${id}`,
      );
      const data = await response.json();

      if (data.success && data.history.length > 0) {
        data.history.forEach((row) => {
          const tr = document.createElement("tr");
          tr.className = "hover:bg-orange-50 transition-colors";
          const fullName = `${row.first_name || ""} ${row.last_name || ""}`.trim() || "N/A";
          const idRole = `${row.identifier || "N/A"} / <span class="capitalize">${row.role || "N/A"}</span>`;
          
          tr.innerHTML = `
                        <td class="px-4 py-3 text-sm text-gray-700 font-medium">${fullName}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">${idRole}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">${row.borrowed_at || "N/A"}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">${row.returned_at || '<span class="text-orange-600 font-semibold italic text-[11px]">Pending</span>'}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${row.status === "returned" ? "bg-green-100 text-green-700" : "bg-orange-100 text-orange-700"}">
                                ${row.status}
                            </span>
                        </td>
                    `;
          historyTableBody.appendChild(tr);
        });
        openModal(historyModal);
      } else {
        historyEmptyState.classList.remove("hidden");
        historyTableContainer.classList.add("hidden");
        openModal(historyModal);
      }
    } catch (error) {
      console.error("Error fetching borrowing history:", error);
    }
  };

  window.viewBook = async (id) => {
    try {
      const response = await fetch(
        `api/campus_admin/booksmanagement/details/${id}`,
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
    }
  };

  window.editBook = async (id) => {
    try {
      const response = await fetch(
        `api/campus_admin/booksmanagement/details/${id}`,
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
    }
  };

  bulkImportBtn.addEventListener("click", () => {
    openModal(importModal);
    bulkImportForm.reset();
    if (uploadText)
      uploadText.textContent = "Drop CSV file here or click to browse";
    if (uploadInstruction) uploadInstruction.style.display = "";
  });

  [closeImportModal, cancelImport].forEach((btn) =>
    btn.addEventListener("click", () => closeModal(importModal)),
  );

  importModal.addEventListener("click", (e) => {
    if (e.target === importModal) closeModal(importModal);
  });

  bulkImportForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(bulkImportForm);
    const fileInput = document.getElementById("csvFile");
    const file = fileInput.files[0];

    if (!file) {
      Swal.fire("Error", "Please select a CSV file to upload.", "warning");
      return;
    }

    showLoadingModal("Importing Books...", "Please wait.");
    try {
      const response = await fetch(
        "api/campus_admin/booksmanagement/bulkImport",
        {
          method: "POST",
          body: formData,
        },
      );
      const data = await response.json();

      if (data.success) {
        showSuccessToast("Import Successful!", `Imported ${data.imported} books.`);
        closeModal(importModal);
        fetchBooks();
      } else {
        showErrorToast("Import Failed", data.message);
      }
    } catch (error) {
      console.error("Import error:", error);
      showErrorToast("Import Failed", "An error occurred.");
    }
  });

  csvFile.addEventListener("change", (e) => {
    const file = e.target.files[0];
    if (file) {
      if (uploadText) uploadText.textContent = file.name;
      if (uploadInstruction) uploadInstruction.style.display = "none";
      bulkImportForm.requestSubmit();
    }
  });

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
      document.querySelectorAll(".absolute.z-20").forEach((m) => {
        if (m !== menu) m.classList.add("hidden");
      });
      menu.classList.toggle("hidden");
    });
  });

  document.addEventListener("click", () => {
    document.querySelectorAll(".absolute.z-20").forEach((m) => m.classList.add("hidden"));
  });

  openAddBookBtn.addEventListener("click", () => openModal(addBookModal));
  [closeAddBookModal, cancelAddBook].forEach((btn) =>
    btn.addEventListener("click", () => {
      closeModal(addBookModal);
      addBookForm.reset();
      document.getElementById("previewContainer").classList.add("hidden");
    }),
  );

  [closeEditBookModal, cancelEditBook].forEach((btn) =>
    btn.addEventListener("click", () => {
      closeModal(editBookModal);
      editBookForm.reset();
    }),
  );

  [closeViewModal, closeViewModalBtn].forEach((btn) =>
    btn.addEventListener("click", () => closeModal(viewBookModal)),
  );

  [closeHistoryModal, closeHistoryBtn].forEach((btn) =>
    btn.addEventListener("click", () => closeModal(historyModal)),
  );

  addBookForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(addBookForm);
    try {
      const response = await fetch("api/campus_admin/booksmanagement/add", {
        method: "POST",
        body: formData,
      });
      const data = await response.json();
      if (data.success) {
        showSuccessToast("Book added successfully!");
        closeModal(addBookModal);
        addBookForm.reset();
        document.getElementById("previewContainer").classList.add("hidden");
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
      const response = await fetch(
        `api/campus_admin/booksmanagement/update/${bookId}`,
        {
          method: "POST",
          body: formData,
        },
      );
      const data = await response.json();
      if (data.success) {
        showSuccessToast("Book updated successfully!");
        closeModal(editBookModal);
        fetchBooks();
      } else {
        Swal.fire("Error", data.message, "error");
      }
    } catch (error) {
      console.error("Error updating book:", error);
    }
  });

  multiSelectBtn.addEventListener("click", () => {
    isMultiSelectMode = true;
    document.getElementById("multi-select-header").classList.remove("hidden");
    multiSelectBtn.classList.add("hidden");
    multiSelectActions.classList.remove("hidden");
    selectionCount.textContent = selectedBookIds.size;
    renderTable();
  });

  cancelSelectionBtn.addEventListener("click", () => {
    isMultiSelectMode = false;
    selectedBookIds.clear();
    document.getElementById("multi-select-header").classList.add("hidden");
    multiSelectBtn.classList.remove("hidden");
    multiSelectActions.classList.add("hidden");
    selectionCount.textContent = "0";
    renderTable();
  });

  selectAllBtn.addEventListener("click", () => {
    if (selectedBookIds.size === books.length) {
      selectedBookIds.clear();
    } else {
      books.forEach((b) => selectedBookIds.add(b.book_id));
    }
    selectionCount.textContent = selectedBookIds.size;
    renderTable();
  });

  multiDeleteBtn.addEventListener("click", async () => {
    if (selectedBookIds.size === 0) return;

    const result = await showConfirmationModal(
      "Bulk Deactivate",
      `Are you sure you want to deactivate ${selectedBookIds.size} books?`,
      "Yes, Deactivate Them!"
    );

    if (result) {
      try {
        const response = await fetch(
          "api/campus_admin/booksmanagement/deleteMultiple",
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ book_ids: Array.from(selectedBookIds) }),
          },
        );
        const data = await response.json();
        if (data.success) {
          showSuccessToast(`Successfully deactivated ${data.deleted_count} book(s)!`);
          selectedBookIds.clear();
          cancelSelectionBtn.click();
          fetchBooks();
        } else {
          Swal.fire("Error", data.message, "error");
        }
      } catch (error) {
        console.error("Error bulk deleting books:", error);
      }
    }
  });

  // Image Previews
  const setupPreview = (inputId, previewImgId, containerId, textElementId) => {
    const input = document.getElementById(inputId);
    input.addEventListener("change", () => {
      const file = input.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          document.getElementById(previewImgId).src = e.target.result;
          document.getElementById(containerId).classList.remove("hidden");
          if (textElementId) document.getElementById(textElementId).textContent = "Change Image";
        };
        reader.readAsDataURL(file);
      }
    });
  };

  setupPreview("book_image", "previewImage", "previewContainer", "uploadText");
  
  const editInput = document.getElementById("edit_book_image");
  editInput.addEventListener("change", () => {
    const file = editInput.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        document.getElementById("editPreviewImage").src = e.target.result;
        document.getElementById("editPreviewContainer").classList.remove("hidden");
        document.getElementById("removeImageBtn").classList.remove("hidden");
        document.getElementById("editUploadText").textContent = "Change Image";
        document.getElementById("edit_remove_image").value = "0";
      };
      reader.readAsDataURL(file);
    }
  });

  document.getElementById("removeImageBtn").addEventListener("click", () => {
    document.getElementById("editPreviewImage").src = "";
    document.getElementById("editPreviewContainer").classList.add("hidden");
    document.getElementById("removeImageBtn").classList.add("hidden");
    document.getElementById("editUploadText").textContent = "Upload Image";
    document.getElementById("edit_remove_image").value = "1";
    editInput.value = "";
  });

  loadCampuses();
  fetchBooks();
});
