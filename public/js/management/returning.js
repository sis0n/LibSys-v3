/**
 * Unified Returning JS
 * Smart logic for handling book returns across roles.
 */

// --- CORE CONFIRMATION FUNCTION (FINAL TEMPLATE) ---
async function showCustomConfirmationModal(title, text, confirmText = "Confirm") {
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
        "!rounded-xl !shadow-lg !p-6 !bg-white !border-2 !border-orange-500 !border-solid",
      confirmButton:
        "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg hover:!bg-orange-700 !mx-2 !font-semibold !text-base",
      cancelButton:
        "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg hover:!bg-gray-300 !mx-2 !font-semibold !text-base",
      actions: "!mt-4",
    },
  });
  return result.isConfirmed;
}

// ==========================================================
// SWEETALERT UTILITY FUNCTIONS
// ==========================================================

const showReturningToast = (title, text, duration = 3000) => {
  const iconClass = "ph-x-circle";
  const contentColor = "text-red-600";
  const bgColor = "bg-red-100";
  const inlineStyle =
    "border: 2px solid #dc2626 !important; box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);";

  Swal.fire({
    toast: true,
    position: "bottom-end",
    showConfirmButton: false,
    timer: duration,
    width: "360px",
    background: "white",
    backdrop: `transparent`,
    customClass: { popup: `!rounded-xl !p-4 backdrop-blur-sm` },
    html: `
            <div class="flex flex-col text-left">
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full ${bgColor} ${contentColor}">        
                        <i class="ph ${iconClass} text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-semibold ${contentColor}">${title}</h3>
                        <p class="text-[13px] text-gray-700 mt-0.5">${text}</p>
                    </div>
                </div>
            </div>
        `,
    didOpen: (toast) => {
      toast.style.cssText = inlineStyle + " " + toast.style.cssText;
    },
  });
};

const showFinalReturningModal = (isSuccess, title, message) => {
  if (typeof Swal == "undefined") return alert(`${title}: ${message}`);

  const duration = 3000;
  let timerInterval;

  const theme = isSuccess
    ? {
        bg: "bg-green-50",
        border: "border-green-300",
        text: "text-green-700",
        iconBg: "bg-green-100",
        iconColor: "text-green-600",
        iconClass: "ph-check-circle",
        progressBarColor: "bg-green-500",
      }
    : {
        bg: "bg-red-50",
        border: "border-red-300",
        text: "text-red-700",
        iconBg: "bg-red-100",
        iconColor: "text-red-600",
        iconClass: "ph-x-circle",
        progressBarColor: "bg-red-500",
      };

  Swal.fire({
    showConfirmButton: false,
    showCancelButton: false,
    buttonsStyling: false,
    width: "450px",
    backdrop: `rgba(0,0,0,0.3) backdrop-filter: blur(6px)`,
    timer: duration,
    didOpen: () => {
      const progressBar =
        Swal.getHtmlContainer().querySelector("#progress-bar");
      let width = 100;
      timerInterval = setInterval(() => {
        width -= 100 / (duration / 100);
        if (progressBar) progressBar.style.width = width + "%";
      }, 100);
    },
    willClose: () => clearInterval(timerInterval),
    html: `
            <div class="w-full ${theme.bg} border-2 ${theme.border} rounded-2xl p-8 shadow-xl text-center">
                <div class="flex items-center justify-center w-16 h-16 rounded-full ${theme.iconBg} mx-auto mb-4">
                    <i class="ph ${theme.iconClass} ${theme.iconColor} text-3xl"></i>
                </div>
                <h3 class="text-2xl font-bold ${theme.text}">${title}</h3>
                <p class="text-base ${theme.text} mt-3 mb-4">${message}</p>
                <div class="w-full bg-gray-200 h-2 rounded mt-4 overflow-hidden">
                    <div id="progress-bar" class="${theme.progressBarColor} h-2 w-full transition-all duration-100 ease-linear"></div>
                </div>
            </div>
        `,
    customClass: {
      popup:
        "!block !bg-transparent !shadow-none !p-0 !border-0 !w-auto !min-w-0 !max-w-none",
    },
  });
};

const showSuccessReturningToast = (title, body = "") => {
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
};

// ==========================================================

document.addEventListener("DOMContentLoaded", function () {
  const returnModal = document.getElementById("return-modal");
  const closeButton = document.getElementById("modal-close-button");
  const cancelButton = document.getElementById("modal-cancel-button");
  const modalReturnButton = document.getElementById("modal-return-button");
  const modalExtendButton = document.getElementById("modal-extend-button");
  const availableBookModal = document.getElementById("available-book-modal");
  const availableModalCloseButton = document.getElementById(
    "available-modal-close-button",
  );
  const availableModalCloseAction = document.getElementById(
    "available-modal-close-action",
  );
  const accessionInput = document.getElementById("accession-input");
  const scanButton = document.getElementById("scan-button");
  const qrCodeValueInput = document.getElementById("qrCodeValue");
  const recentReturnsFeed = document.getElementById("recent-returns-feed");

  const closeReturnModal = () => {
    if (returnModal) returnModal.classList.add("hidden");
  };
  const closeAvailableModal = () => {
    if (availableBookModal) availableBookModal.classList.add("hidden");
  };

  async function fetchRecentReturns() {
    try {
      const response = await fetch(`${RETURNING_API_BASE}/getRecent?limit=10`);
      const result = await response.json();
      if (result.success) {
        renderRecentReturns(result.list);
      }
    } catch (error) {
      console.error("Error:", error);
    }
  }

  function renderRecentReturns(list) {
    if (!recentReturnsFeed) return;
    if (!list || list.length === 0) {
      recentReturnsFeed.innerHTML = `<tr><td colspan="5" class="py-10 text-center text-gray-400 italic">No returns processed yet.</td></tr>`;
      return;
    }

    recentReturnsFeed.innerHTML = list
      .map(
        (item) => `
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-4">
                    <div class="flex flex-col">
                        <span class="font-bold text-gray-800 text-base leading-tight">${
                          item.first_name
                        } ${item.last_name}</span>
                        <span class="text-[14px] text-orange-600 font-mono font-black uppercase tracking-tight mt-0.5">${
                          item.identifier || "N/A"
                        }</span>
                    </div>
                </td>
                <td class="px-4 py-4 text-center text-sm text-gray-700 font-bold">${
                  item.year_section || "N/A"
                }</td>
                <td class="px-4 py-4 text-center text-sm text-gray-600 font-mono font-black">${
                  item.accession_number || "N/A"
                }</td>
                <td class="px-4 py-4 text-gray-800 text-sm font-medium truncate max-w-[200px]" title="${
                  item.item_title
                }">${item.item_title}</td>
                <td class="px-4 py-4 text-right text-orange-700 text-base font-black font-mono">${formatTime(
                  item.returned_at,
                )}</td>
            </tr>
        `,
      )
      .join("");
  }

  function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  }

  const selectionModal = document.getElementById("selection-modal");
  const selectionList = document.getElementById("selection-list");
  const selectionModalClose = document.getElementById("selection-modal-close");

  const closeSelectionModal = () => {
    if (selectionModal) selectionModal.classList.add("hidden");
  };

  let currentMatches = [];

  async function handleBookCheck(accessionNumber) {
    if (!accessionNumber) return;
    const formData = new FormData();
    formData.append("accession_number", accessionNumber);

    try {
      const response = await fetch(`${RETURNING_API_BASE}/checkBook`, {
        method: "POST",
        body: formData,
      });
      const result = await response.json();
      if (result.success) {
        const data = result.data;
        if (data.status === "borrowed") {
          if (data.count > 1 && data.matches) {
            currentMatches = data.matches;
            renderSelectionList(data.matches);
            selectionModal.classList.remove("hidden");
          } else if (data.matches && data.matches.length > 0) {
            openReturnModal(data.matches[0]);
          } else if (data.details) {
            openReturnModal(data.details);
          }
        } else if (data.status === "available") {
          openAvailableModal(data.details);
        } else {
          showReturningToast(
            "Not Found",
            "No active borrowing or item found with this identifier.",
            4000,
          );
        }
      } else {
        showReturningToast("Error", result.message || "An error occurred.");
      }
    } catch (error) {
      console.error("Error:", error);
      showReturningToast("Error", "Could not connect to server.");
    }
    accessionInput.value = "";
    qrCodeValueInput.value = "";
    qrCodeValueInput.focus();
  }

  function renderSelectionList(matches) {
    if (!selectionList) return;
    selectionList.innerHTML = matches
      .map(
        (match, index) => `
        <div class="flex items-center justify-between p-4 bg-orange-50 border border-orange-200 rounded-xl hover:border-orange-400 transition-all cursor-pointer group" onclick="window.selectBorrowerByIndex(${index})">
          <div class="flex flex-col">
            <span class="font-bold text-gray-800 text-lg">${match.borrower_name}</span>
            <span class="text-sm text-gray-600 font-mono">${match.id_number} • ${match.course_or_department}</span>
            <span class="text-xs text-orange-600 mt-1 font-bold italic">Borrowed from: ${match.home_campus_name}</span>
          </div>
          <div class="flex items-center gap-3">
             <span class="text-[10px] bg-white border border-orange-200 px-2 py-1 rounded text-gray-500 uppercase font-bold">${match.availability}</span>
             <i class="ph ph-arrow-right text-orange-500 text-xl group-hover:translate-x-1 transition-transform"></i>
          </div>
        </div>
      `,
      )
      .join("");
  }

  window.selectBorrowerByIndex = (index) => {
    const match = currentMatches[index];
    if (match) {
      closeSelectionModal();
      openReturnModal(match);
    }
  };

  function openReturnModal(data) {
    document.getElementById("modal-book-title").textContent = data.title;
    document.getElementById("modal-book-author").textContent =
      data.author || "";
    document.getElementById("modal-book-accessionnumber").textContent =
      data.accession_number || data.title;
    document.getElementById("modal-book-callnumber").textContent =
      data.call_number || "N/A";
    document.getElementById("modal-borrower-name").textContent =
      data.borrower_name;
    document.getElementById("modal-student-id").textContent = data.id_number;
    document.getElementById("modal-borrower-course").textContent =
      data.course_or_department;
    document.getElementById("modal-borrower-year-section").textContent =
      data.student_year_section;
    document.getElementById("modal-due-date").textContent = data.due_date;

    const warningEl = document.getElementById("cross-campus-warning");
    const campusNameEl = document.getElementById("home-campus-name");
    if (warningEl && campusNameEl) {
      if (data.is_cross_campus) {
        campusNameEl.textContent = data.home_campus_name;
        warningEl.classList.remove("hidden");
      } else {
        warningEl.classList.add("hidden");
      }
    }

    const statusEl = document.getElementById("modal-book-status");
    if (statusEl) {
      const status = data.availability.toUpperCase();
      statusEl.textContent = status;
      statusEl.className =
        status === "OVERDUE"
          ? "bg-red-200 text-red-800 text-xs font-semibold px-3 py-1 rounded-full"
          : "bg-orange-200 text-orange-800 text-xs font-semibold px-3 py-1 rounded-full";
    }

    modalReturnButton.dataset.borrowingId = data.borrowing_id;
    if (modalExtendButton)
      modalExtendButton.dataset.borrowingId = data.borrowing_id;
    returnModal.classList.remove("hidden");
  }

  function openAvailableModal(data) {
    document.getElementById("available-modal-title").textContent = data.title;
    document.getElementById("available-modal-author").textContent =
      data.author || "";
    document.getElementById("available-modal-accession").textContent =
      data.accession_number || "N/A";
    document.getElementById("available-modal-call-number").textContent =
      data.call_number || "N/A";
    document.getElementById("available-modal-isbn").textContent =
      data.book_isbn || "N/A";
    document.getElementById("available-modal-publisher").textContent =
      data.book_publisher || "N/A";
    document.getElementById("available-modal-year").textContent =
      data.year || "N/A";
    document.getElementById("available-modal-edition").textContent =
      data.book_edition || "N/A";
    document.getElementById("available-modal-subject").textContent =
      data.subject || "N/A";
    document.getElementById("available-modal-description").textContent =
      data.description || "No description available.";

    const img = document.getElementById("available-modal-img");
    const placeholder = document.getElementById(
      "available-modal-img-placeholder",
    );
    if (data.cover && img) {
      img.src = `${STORAGE_URL}/${data.cover}`;
      img.classList.remove("hidden");
      placeholder.classList.add("hidden");
    } else if (img) {
      img.classList.add("hidden");
      placeholder.classList.remove("hidden");
    }

    availableBookModal.classList.remove("hidden");
  }

  if (modalReturnButton) {
    modalReturnButton.addEventListener("click", async () => {
      const id = modalReturnButton.dataset.borrowingId;
      const condition =
        document.querySelector('input[name="item_condition"]:checked')?.value ||
        "good";

      const confirmed = await showCustomConfirmationModal(
        "Return Item?",
        "Are you sure you want to mark this item as returned?",
        "Yes, Return It!",
      );

      if (confirmed) {
        Swal.fire({
          background: "transparent",
          html: `
                    <div class="flex flex-col items-center justify-center gap-2">
                        <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div>
                        <p class="text-gray-700 text-[14px]">Processing return...<br><span class="text-sm text-gray-500">Just a moment.</span></p>
                    </div>
                `,
          allowOutsideClick: false,
          showConfirmButton: false,
          customClass: {
            popup:
              "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
          },
        });

        const formData = new FormData();
        formData.append("borrowing_id", id);
        formData.append("condition", condition);

        try {
          const response = await fetch(`${RETURNING_API_BASE}/markReturned`, {
            method: "POST",
            body: formData,
          });
          const result = await response.json();
          Swal.close();
          if (result.success) {
            closeReturnModal();
            showFinalReturningModal(true, "Item Returned", result.message);
            renderRecentReturns(result.recent);
          } else {
            showFinalReturningModal(false, "Return Failed", result.message);
          }
        } catch (error) {
          Swal.close();
          showFinalReturningModal(false, "Error", "Could not process return.");
        }
      }
    });
  }

  if (modalExtendButton) {
    modalExtendButton.addEventListener("click", async () => {
      const id = modalExtendButton.dataset.borrowingId;
      const { value: days } = await Swal.fire({
        title: "Extend Due Date",
        input: "number",
        inputLabel: "Days to extend",
        inputValue: 7,
        showCancelButton: true,
        confirmButtonText: "Extend",
        customClass: {
          popup:
            "!rounded-xl !shadow-md !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] !border-2 !border-orange-400",
          confirmButton: "!bg-orange-600 !text-white !px-5 !py-2.5 !rounded-lg",
          cancelButton: "!bg-gray-200 !text-gray-800 !px-5 !py-2.5 !rounded-lg",
        },
      });
      if (days) {
        const formData = new FormData();
        formData.append("borrowing_id", id);
        formData.append("days", days);
        const response = await fetch(`${RETURNING_API_BASE}/extend`, {
          method: "POST",
          body: formData,
        });
        const result = await response.json();
        if (result.success) {
          document.getElementById("modal-due-date").textContent =
            result.new_due_date;
          showSuccessReturningToast(
            "Due Date Extended",
            `New due date: ${result.new_due_date}`,
          );
        } else {
          showReturningToast("Extension Failed", result.message);
        }
      }
    });
  }

  if (scanButton)
    scanButton.addEventListener("click", () =>
      handleBookCheck(accessionInput.value.trim()),
    );
  if (accessionInput)
    accessionInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter") handleBookCheck(accessionInput.value.trim());
    });

  if (qrCodeValueInput) {
    qrCodeValueInput.addEventListener("input", () => {
      const val = qrCodeValueInput.value.trim();
      if (val) {
        handleBookCheck(val);
        qrCodeValueInput.value = "";
      }
    });
  }

  closeButton?.addEventListener("click", closeReturnModal);
  cancelButton?.addEventListener("click", closeReturnModal);
  availableModalCloseButton?.addEventListener("click", closeAvailableModal);
  availableModalCloseAction?.addEventListener("click", closeAvailableModal);
  selectionModalClose?.addEventListener("click", closeSelectionModal);

  fetchRecentReturns();
});
