const recordsContainer = document.getElementById('recordsContainer');
const loadingIndicator = document.getElementById('loadingIndicator');
const paginationContainer = document.getElementById('paginationContainer');

const statTotal = document.getElementById('statTotal');
const statCurrent = document.getElementById('statCurrent');
const statOverdue = document.getElementById('statOverdue');
const statReturned = document.getElementById('statReturned');

let currentPage = 1;
try {
    const savedPage = sessionStorage.getItem('userBorrowingHistoryPage');
    if (savedPage) {
        const parsedPage = parseInt(savedPage, 10);
        if (!isNaN(parsedPage) && parsedPage > 0) {
            currentPage = parsedPage;
        } else {
            sessionStorage.removeItem('userBorrowingHistoryPage');
        }
    }
} catch (e) {
    console.error("SessionStorage Error:", e);
    currentPage = 1;
}
const limit = 5;

function renderBorrowingTable(records) {
  if (!records || records.length === 0) {
    recordsContainer.innerHTML = `<div class="text-center py-10 text-gray-500">No borrowing records found.</div>`;
    return;
  }

  const html = records.map(record => {
    const returnedBoxClass = record.statusText === 'Returned' ? 'bg-[var(--color-green-50)]' : 'bg-[var(--color-gray-100)]';
    const returnedIconClass = record.returnedDate !== 'Not returned' ? 'text-[var(--color-green-600)]' : 'text-gray-400';
    const overdueClass = record.isOverdue ? 'bg-red-50 border-red-200' : 'border-[var(--color-border)]';
    const itemIconClass = record.item_type === 'Book' ? 'ph ph-book' : 'ph ph-package';

    return `
      <div class="relative rounded-lg p-4 border ${overdueClass} bg-[var(--color-card)] shadow-sm">
          <span class="absolute top-3 right-3 ${record.statusBgClass} text-xs font-medium px-3 py-1 rounded-full">
              ${record.statusText}
          </span>
          <h4 class="font-semibold text-[var(--color-foreground)] pr-24">${record.title}</h4>
          <p class="text-sm text-gray-600 mb-3">${record.item_type === 'Book' ? 'by ' + record.author : 'Equipment / Item'}</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-sm">
              <div class="bg-orange-50 p-2 rounded"><i class="${itemIconClass} text-orange-600 mr-1"></i>Borrowed<br><span class="font-medium">${record.borrowedDate}</span></div>
              <div class="bg-gray-100 p-2 rounded"><i class="ph ph-calendar-blank text-gray-600 mr-1"></i>Due Date<br><span class="font-medium">${record.dueDate}</span></div>
              <div class="${returnedBoxClass} p-2 rounded"><i class="ph ph-check-circle ${returnedIconClass} mr-1"></i>Returned<br><span class="font-medium">${record.returnedDate}</span></div>
              <div class="bg-gray-100 p-2 rounded"><i class="ph ph-user text-gray-600 mr-1"></i>Librarian<br><span class="font-medium">${record.librarianName || 'N/A'}</span></div>
          </div>
      </div>`;
  }).join('');

  recordsContainer.innerHTML = html;
}

function renderPagination(totalPages, currentPage) {
  const paginationContainer = document.getElementById("paginationContainer");
  if (totalPages <= 1) {
    paginationContainer.innerHTML = '';
    return;
  }
  let paginationHTML = `
    <div class="flex items-center justify-center">
      <div class="flex items-center justify-center space-x-1 bg-white border border-gray-200 rounded-full px-2 py-1 shadow-sm 
        max-w-[420px] overflow-x-auto scrollbar-hide">
        
        <button class="pagination-link flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 
          ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" 
          data-page="${currentPage - 1}">
          <i class="ph ph-caret-left"></i> Previous
        </button>
  `;
  const createPageButton = (page, active = false) => `
    <button data-page="${page}" 
      class="pagination-link w-8 h-8 rounded-full text-sm font-medium transition ${
        active 
          ? 'bg-orange-600 text-white shadow-sm' 
          : 'text-gray-700 hover:bg-gray-100'
      }">
      ${page}
    </button>
  `;

  let startPage = Math.max(1, currentPage - 1);
  let endPage = Math.min(totalPages, currentPage + 1);

  if (currentPage <= 2) endPage = Math.min(3, totalPages);
  if (currentPage >= totalPages - 1) startPage = Math.max(totalPages - 2, 1);

  if (startPage > 1) {
    paginationHTML += createPageButton(1, currentPage === 1);
    if (startPage > 2) paginationHTML += `<span class="px-2 text-gray-400">...</span>`;
  }

  for (let i = startPage; i <= endPage; i++) {
    paginationHTML += createPageButton(i, i === currentPage);
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) paginationHTML += `<span class="px-2 text-gray-400">...</span>`;
    paginationHTML += createPageButton(totalPages, currentPage === totalPages);
  }

  paginationHTML += `
      <button class="pagination-link flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-700 ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''}" data-page="${currentPage + 1}">
        Next <i class="ph ph-caret-right"></i>
      </button>
    </div>
  `;

  paginationContainer.innerHTML = paginationHTML;
}

async function fetchBorrowingData(page = 1) {
    const start = Date.now();
    
    try {
        sessionStorage.setItem('userBorrowingHistoryPage', page);
    } catch (e) {
        console.error("SessionStorage Error:", e);
    }

    if (typeof BASE_URL_JS === 'undefined') {
        recordsContainer.innerHTML = `<div class="text-center py-10 text-red-500">Configuration error.</div>`;
        return;
    }

    recordsContainer.innerHTML = ''; 
    paginationContainer.innerHTML = '';
    
    if (typeof Swal != 'undefined') {
        Swal.fire({
            background: "transparent",
            html: `
                <div class="flex flex-col items-center justify-center gap-2">
                    <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div>
                    <p class="text-gray-700 text-[14px]">Loading borrowing history...<br><span class="text-sm text-gray-500">Please wait.</span></p>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false,
            customClass: {
                popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
            },
        });
    }

    try {
        const response = await fetch(`${BASE_URL_JS}/api/borrowing-history/pagination?page=${page}&limit=${limit}`);
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();

        const elapsed = Date.now() - start;
        const minDelay = 500; // Minimum 300ms loading time
        if (elapsed < minDelay) await new Promise(r => setTimeout(r, minDelay - elapsed));
        if (typeof Swal != 'undefined') Swal.close();


        if (data.success) {
            renderBorrowingTable(data.borrowingHistory);
            renderPagination(data.totalPages, data.currentPage);
            if (page === 1) {
                fetchStats();
            }
        } else {
            recordsContainer.innerHTML = `<div class="text-center py-10 text-red-500">${data.message || 'Failed to load history.'}</div>`;
        }
    } catch (error) {
        if (typeof Swal != 'undefined') {
            Swal.close();
            Swal.fire({
                toast: true,
                position: "bottom-end",
                showConfirmButton: false,
                timer: 4000,
                width: "360px",
                background: "transparent",
                html: `
                    <div class="flex flex-col text-left">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600">
                                <i class="ph ph-x-circle text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-[15px] font-semibold text-red-600">Loading Failed</h3>
                                <p class="text-[13px] text-gray-700 mt-0.5">Could not retrieve borrowing history data.</p>
                            </div>
                        </div>
                    </div>
                `,
                customClass: {
                    popup: "!rounded-xl !shadow-md !border-2 !border-red-400 !p-4 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ff6b6b70]",
                },
            });
        }
        
        recordsContainer.innerHTML = `<div class="text-center py-10 text-red-500">Network error. Please try again.</div>`;
        console.error('Fetch error:', error);
    }
}
async function fetchStats() {
    try {
        const response = await fetch(`${BASE_URL_JS}/api/borrowing-history/stats`);
        const data = await response.json();
        if(data.success && data.stats) {
            statTotal.textContent = data.stats.total_borrowed;
            statCurrent.textContent = data.stats.currently_borrowed;
            statOverdue.textContent = data.stats.total_overdue;
            statReturned.textContent = data.stats.total_returned;
        }
    } catch (error) {
        console.error('Failed to fetch stats:', error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
  fetchBorrowingData(currentPage);

  paginationContainer.addEventListener('click', (e) => {
    e.preventDefault();
    const link = e.target.closest('.pagination-link');
    if (link && !link.classList.contains('cursor-not-allowed')) {
      const page = parseInt(link.dataset.page, 10);
      if (!isNaN(page)) {
        fetchBorrowingData(page);
      }
    }
  });
});