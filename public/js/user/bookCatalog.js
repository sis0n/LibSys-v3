window.addEventListener("DOMContentLoaded", () => {
    const grid = document.getElementById("booksGrid");
    const skeletons = document.getElementById("loadingSkeletons");
    const searchInput = document.querySelector("input[placeholder*='Search']");
    const cartCount = document.getElementById("cart-count");
    const noBooksFound = document.getElementById("noBooksFound");
    const resultsIndicator = document.getElementById("resultsIndicator");

    // Dropdown Elements
    const statusBtn = document.getElementById("statusDropdownBtn");
    const statusMenu = document.getElementById("statusDropdownMenu");
    const statusValue = document.getElementById("statusDropdownValue");
    const sortBtn = document.getElementById("sortDropdownBtn");
    const sortMenu = document.getElementById("sortDropdownMenu");
    const sortValue = document.getElementById("sortDropdownValue");
    const campusBtn = document.getElementById("campusDropdownBtn");
    const campusMenu = document.getElementById("campusDropdownMenu");
    const campusValue = document.getElementById("campusDropdownValue");

    // Modal Elements
    const modal = document.getElementById("bookModal");
    const modalContent = document.getElementById("bookModalContent");
    const closeModalBtn = document.getElementById("closeModal");
    const modalImg = document.getElementById("modalImg");
    const modalTitle = document.getElementById("modalTitle");
    const modalAuthor = document.getElementById("modalAuthor");
    const modalCallNumber = document.getElementById("modalCallNumber");
    const modalAccessionNumber = document.getElementById("modalAccessionNumber");
    const modalIsbn = document.getElementById("modalIsbn");
    const modalSubject = document.getElementById("modalSubject");
    const modalDescription = document.getElementById("modalDescription");
    const modalPlace = document.getElementById("modalPlace");
    const modalPublisher = document.getElementById("modalPublisher");
    const modalYear = document.getElementById("modalYear");
    const modalEdition = document.getElementById("modalEdition");
    const modalSupplementary = document.getElementById("modalSupplementary");
    const modalStatus = document.getElementById("modalStatus");
    const modalCampus = document.getElementById("modalCampus");
    const addToCartBtn = document.getElementById("addToCartBtn");

    const paginationControls = document.getElementById("paginationControls");
    const paginationList = document.getElementById("paginationList");

    const limit = 30;
    let totalPages = 1;
    let totalCount = 0;
    let isLoading = false;
    let searchValue = "";
    let statusValueFilter = "All Status";
    let sortValueFilter = "default";
    let campusValueFilter = "all";
    let cart = [];

    const userRole = document.getElementById("userRole")?.value || 'user';
    
    let currentPage = 1;
    let currentBook = null;
    try {
        const savedPage = sessionStorage.getItem(`bookCatalogPage_${userRole}`);
        if (savedPage) {
            const parsedPage = parseInt(savedPage, 10);
            if (!isNaN(parsedPage) && parsedPage > 0) currentPage = parsedPage;
            else sessionStorage.removeItem(`bookCatalogPage_${userRole}`);
        }

        const savedCampus = sessionStorage.getItem(`bookCatalogCampus_${userRole}`);
        const savedCampusText = sessionStorage.getItem(`bookCatalogCampusText_${userRole}`);
        if (savedCampus && savedCampusText) {
            campusValueFilter = savedCampus;
            if (campusValue) campusValue.textContent = savedCampusText;
        } else {
            const homeId = document.getElementById("userHomeCampusId")?.value;
            const homeName = document.getElementById("userHomeCampusName")?.value;
            if (homeId && homeName) {
                campusValueFilter = homeId;
                if (campusValue) campusValue.textContent = homeName;
            }
        }
    } catch (e) {
        console.error("SessionStorage Error:", e);
    }

    // --- DROPDOWN LOGIC ---
    function closeAllMenus() {
        if (statusMenu) statusMenu.classList.add("hidden");
        if (sortMenu) sortMenu.classList.add("hidden");
        if (campusMenu) campusMenu.classList.add("hidden");
    }

    if (campusBtn && campusMenu) {
        campusBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            const isHidden = campusMenu.classList.contains("hidden");
            closeAllMenus();
            if (isHidden) campusMenu.classList.remove("hidden");
        });

        window.selectCampus = function (el, value, text) {
            if (campusValue) campusValue.textContent = text;
            campusValueFilter = value;
            document.querySelectorAll("#campusDropdownMenu .campus-item").forEach(i => i.classList.remove("bg-[var(--color-orange-200)]", "font-semibold"));
            if (el) el.classList.add("bg-[var(--color-orange-200)]", "font-semibold");
            closeAllMenus();
            currentPage = 1;
            try {
                sessionStorage.setItem(`bookCatalogCampus_${userRole}`, value);
                sessionStorage.setItem(`bookCatalogCampusText_${userRole}`, text);
                sessionStorage.removeItem(`bookCatalogPage_${userRole}`);
            } catch (e) { }
            loadBooks(1);
            loadAvailableCount();
        }
    }

    if (statusBtn && statusMenu) {
        statusBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            const isHidden = statusMenu.classList.contains("hidden");
            closeAllMenus();
            if (isHidden) statusMenu.classList.remove("hidden");
        });
        window.selectStatus = function (el, value) {
            if (statusValue) statusValue.textContent = value;
            statusValueFilter = value;
            document.querySelectorAll("#statusDropdownMenu .status-item").forEach(i => i.classList.remove("bg-[var(--color-orange-200)]", "font-semibold"));
            if (el) el.classList.add("bg-[var(--color-orange-200)]", "font-semibold");
            closeAllMenus();
            currentPage = 1;
            try { sessionStorage.removeItem(`bookCatalogPage_${userRole}`); } catch (e) { }
            loadBooks(1);
        }
    }

    if (sortBtn && sortMenu) {
        sortBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            const isHidden = sortMenu.classList.contains("hidden");
            closeAllMenus();
            if (isHidden) sortMenu.classList.remove("hidden");
        });
        window.selectSort = function (el, value, text) {
            if (sortValue) sortValue.textContent = text;
            sortValueFilter = value;
            document.querySelectorAll("#sortDropdownMenu .sort-item").forEach(i => i.classList.remove("bg-[var(--color-orange-200)]", "font-semibold"));
            if (el) el.classList.add("bg-[var(--color-orange-200)]", "font-semibold");
            closeAllMenus();
            currentPage = 1;
            try { sessionStorage.removeItem(`bookCatalogPage_${userRole}`); } catch (e) { }
            loadBooks(1);
        }
    }

    document.addEventListener("click", (e) => {
        if (statusMenu && statusBtn && !statusBtn.contains(e.target) && !statusMenu.contains(e.target)) statusMenu.classList.add("hidden");
        if (sortMenu && sortBtn && !sortBtn.contains(e.target) && !sortMenu.contains(e.target)) sortMenu.classList.add("hidden");
        if (campusMenu && campusBtn && !campusBtn.contains(e.target) && !campusMenu.contains(e.target)) campusMenu.classList.add("hidden");
    });

    // --- CART LOGIC ---
    async function loadCart() {
        try {
            const r = await fetch("api/cart/json");
            if (!r.ok) throw Error();
            const data = await r.json();
            cart = data.items || [];
            updateCartBadge();
        } catch (e) {
            cart = [];
            updateCartBadge();
        }
    }

    async function updateCartBadge() {
        if (!cartCount) return;
        while (cartCount.firstChild) cartCount.removeChild(cartCount.firstChild);
        const i = document.createElement("i");
        i.className = "ph ph-shopping-cart text-xs mr-1";
        cartCount.appendChild(i);
        const c = (cart && cart.length) ? cart.length : 0;
        cartCount.appendChild(document.createTextNode(`${c} item(s)`));
    }

    async function addToCart(id) {
        if (!id) return;
        try {
            const r = await fetch(`api/cart/add/${id}`);
            if (!r.ok) throw Error((await r.json()).message || `Err ${r.status}`);
            const d = await r.json();
            if (d.success) {
                cart = d.cart || [];
                updateCartBadge();
            }
            if (typeof Swal != "undefined") {
                const isSuccess = d.success;
                const mainTitle = d.message || (isSuccess ? "Added to Cart" : "Already in Cart");
                const bodyText = isSuccess ? "The book has been successfully added to your request cart." : "This book is already in your cart or not available for request.";
                const icon = isSuccess ? "ph-check-circle" : "ph-warning";
                const accentColor = isSuccess ? "text-green-600" : "text-orange-600";
                const accentBg = isSuccess ? "bg-green-100" : "bg-orange-100";
                const borderColor = isSuccess ? "!border-green-400" : "!border-orange-400";
                const popupBgGradient = isSuccess ? "!to-[#f0fff5]" : "!to-[#fff6ef]";
                const shadowColor = isSuccess ? "shadow-[0_0_8px_#22c55e70]" : "shadow-[0_0_8px_#ffb34770]";

                Swal.fire({
                    toast: true,
                    position: "bottom-end",
                    showConfirmButton: false,
                    timer: 3000,
                    width: "360px",
                    background: "transparent",
                    html: `
                        <div class="flex flex-col text-left">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full ${accentBg} ${accentColor}">
                                    <i class="ph ${icon} text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-[15px] font-semibold ${accentColor}">${mainTitle}</h3>
                                    <p class="text-[13px] text-gray-700 mt-0.5">${bodyText}</p>
                                </div>
                            </div>
                        </div>
                    `,
                    customClass: {
                        popup: `!rounded-xl !shadow-md !border-2 ${borderColor} !p-4 !bg-gradient-to-b !from-[#fffdfb] ${popupBgGradient} backdrop-blur-sm ${shadowColor}`,
                    },
                });
                closeModal();
            } else {
                alert(d.message || (d.success ? "Added to Cart!" : "Already in Cart / Not Available"));
                closeModal(); 
            }
        } catch (e) {
            console.error("Add cart err:", e);
        }
    }

    // --- DATA LOADING ---
    async function loadBooksInitial(page = 1) {
        if (isLoading || typeof page !== 'number' || page < 1) return;
        isLoading = true;
        currentPage = page;
        if (grid) grid.innerHTML = "";
        if (noBooksFound) noBooksFound.classList.add("hidden");
        if (skeletons) skeletons.style.display = "grid";
        if (paginationControls) paginationControls.style.display = "none";
        if (resultsIndicator) resultsIndicator.textContent = 'Loading...';
        const start = Date.now();
        const offset = (page - 1) * limit;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                background: "transparent",
                html: `
                    <div class="flex flex-col items-center justify-center gap-2">
                        <div class="animate-spin rounded-full h-10 w-10 border-4 border-orange-200 border-t-orange-600"></div>
                        <p class="text-gray-700 text-[14px]">Loading books...<br><span class="text-sm text-gray-500">Please wait.</span></p>
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
            const params = new URLSearchParams({ limit, offset, search: searchValue });
            if (statusValueFilter !== "All Status") params.set('status', statusValueFilter.toLowerCase());
            if (sortValueFilter !== "default") params.set('sort', sortValueFilter);
            if (campusValueFilter !== "all") params.set('campus_id', campusValueFilter);
            
            const res = await fetch(`api/bookCatalog/fetch?${params.toString()}`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            
            const elapsed = Date.now() - start;
            if (elapsed < 1000) await new Promise(r => setTimeout(r, 1000 - elapsed));
            if (typeof Swal !== 'undefined') Swal.close(); 

            let books = (data && data.books && Array.isArray(data.books)) ? data.books : [];
            totalCount = data.totalCount || 0;
            totalPages = Math.ceil(totalCount / limit) || 1;

            if (skeletons) skeletons.style.display = "none";
            if (books.length === 0) {
                if (noBooksFound) noBooksFound.classList.remove("hidden");
                updateResultsIndicator(0, totalCount);
            } else {
                if (noBooksFound) noBooksFound.classList.add("hidden");
                renderBooks(books);
                updateResultsIndicator(books.length, totalCount);
            }
            renderPagination(totalPages, currentPage);
            try { sessionStorage.setItem(`bookCatalogPage_${userRole}`, currentPage); } catch(e){}
        } catch (err) {
            console.error("LoadBooksInitial error:", err);
            if (typeof Swal !== 'undefined') Swal.close();
            if (skeletons) skeletons.style.display = "none";
            if (noBooksFound) noBooksFound.classList.remove("hidden");
        } finally {
            isLoading = false;
        }
    }
    
    async function loadBooks(page = 1) {
        if (isLoading || typeof page !== 'number' || page < 1) return;
        isLoading = true;
        currentPage = page;
        if (grid) grid.innerHTML = "";
        if (noBooksFound) noBooksFound.classList.add("hidden");
        if (skeletons) skeletons.style.display = "grid"; 
        if (paginationControls) paginationControls.style.display = "none";
        if (resultsIndicator) resultsIndicator.textContent = 'Loading...';
        const offset = (page - 1) * limit;

        try {
            const params = new URLSearchParams({ limit, offset, search: searchValue });
            if (statusValueFilter !== "All Status") params.set('status', statusValueFilter.toLowerCase());
            if (sortValueFilter !== "default") params.set('sort', sortValueFilter);
            if (campusValueFilter !== "all") params.set('campus_id', campusValueFilter);

            const res = await fetch(`api/bookCatalog/fetch?${params.toString()}`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            
            let books = (data && data.books && Array.isArray(data.books)) ? data.books : [];
            totalCount = data.totalCount || 0;
            totalPages = Math.ceil(totalCount / limit) || 1;

            if (skeletons) skeletons.style.display = "none";
            if (books.length === 0) {
                if (noBooksFound) noBooksFound.classList.remove("hidden");
                updateResultsIndicator(0, totalCount);
            } else {
                if (noBooksFound) noBooksFound.classList.add("hidden");
                renderBooks(books);
                updateResultsIndicator(books.length, totalCount);
            }
            renderPagination(totalPages, currentPage);
            try { sessionStorage.setItem(`bookCatalogPage_${userRole}`, currentPage); } catch(e){}
        } catch (err) {
            console.error("LoadBooks error:", err);
            if (skeletons) skeletons.style.display = "none";
            if (noBooksFound) noBooksFound.classList.remove("hidden");
        } finally {
            isLoading = false;
        }
    }

    function updateResultsIndicator(booksLength, currentTotal) {
        if (!resultsIndicator) return;
        if (typeof currentTotal !== 'number') currentTotal = 0;
        if (currentTotal === 0) {
            resultsIndicator.textContent = 'No books found.';
            return;
        }
        const startItem = Math.max(1, (currentPage - 1) * limit + 1);
        const endItem = (currentPage - 1) * limit + booksLength;
        const totalFormatted = currentTotal.toLocaleString('en-US');
        resultsIndicator.innerHTML = `Results: <span class="font-bold text-gray-900">${startItem}-${endItem}</span> of <span class="font-bold text-gray-900">${totalFormatted}</span>`;
    }

    function renderPagination(totalPages, page) {
        if (!paginationControls || !paginationList) return;
        if (totalPages <= 1) {
            paginationControls.style.display = "none";
            return;
        }
        paginationControls.style.display = "flex";
        paginationList.innerHTML = "";

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
                if (isActive) baseClasses += ` text-white bg-orange-600 rounded-full shadow-sm px-3`;
                else baseClasses += ` text-gray-700 hover:text-orange-600 hover:bg-orange-100 rounded-full px-3`;
            }
            a.className = baseClasses;
            li.appendChild(a);
            paginationList.appendChild(li);
        };

        createPageLink("prev", `<i class="ph ph-caret-left text-lg"></i> Previous`, page - 1, page === 1);
        const isMobile = window.innerWidth < 640; 
        let pagesToShow = new Set();
        if (isMobile) {
            for (let i = -1; i <= 1; i++) {
                const p = page + i;
                if (p > 0 && p <= totalPages) pagesToShow.add(p);
            }
        } else {
            pagesToShow.add(1);
            pagesToShow.add(totalPages);
            for (let i = -2; i <= 2; i++) {
                const p = page + i;
                if (p > 0 && p <= totalPages) pagesToShow.add(p);
            }
        }
        const sortedPagesList = [...pagesToShow].sort((a, b) => a - b);
        let lastPage = 0;
        for (const p of sortedPagesList) {
            if (!isMobile && p > lastPage + 1) createPageLink("ellipsis", "…", "...", true);
            createPageLink("number", p, p, false, p === page);
            lastPage = p;
        }
        createPageLink("next", `Next <i class="ph ph-caret-right text-lg"></i>`, page + 1, page === totalPages);
    }

    if (paginationList) {
        paginationList.addEventListener("click", (e) => {
            e.preventDefault();
            const target = e.target.closest("a[data-page]");
            if (!target) return;
            const pageStr = target.dataset.page;
            if (pageStr === "...") return;
            const pageNum = parseInt(pageStr, 10);
            if (!isNaN(pageNum) && pageNum !== currentPage) loadBooks(pageNum); 
        });
    }

    async function loadAvailableCount() {
        try {
            const params = new URLSearchParams();
            if (campusValueFilter !== "all") params.set('campus_id', campusValueFilter);
            const r = await fetch(`api/bookCatalog/availableCount?${params.toString()}`);
            if (!r.ok) throw Error();
            const d = await r.json();
            const el = document.getElementById("availableCount");
            if (el) {
                while (el.firstChild) el.removeChild(el.firstChild);
                const i = document.createElement('i');
                i.className = 'ph ph-check-circle mr-1';
                el.appendChild(i);
                el.appendChild(document.createTextNode(` Available: ${d.available || 0}`));
            }
        } catch (e) {
            console.error("Err count:", e);
        }
    }

    function renderBooks(books) {
        if (!grid) return;
        grid.innerHTML = '';
        if (!books || books.length === 0) return;
        books.forEach(book => {
            const card = document.createElement("div");
            card.className = "book-card relative bg-[var(--color-card)] shadow-sm rounded-xl overflow-hidden group transform transition duration-400 hover:-translate-y-1 hover:shadow-lg max-w-[230px] cursor-pointer";
            card.dataset.book = JSON.stringify(book);
            const imgWrap = document.createElement("div");
            imgWrap.className = "w-full aspect-[2/3] bg-white flex items-center justify-center overflow-hidden";
            const coverUrl = book.cover || null;
            if (coverUrl) {
                const img = document.createElement("img");
                img.src = coverUrl;
                img.className = "h-full w-auto object-contain group-hover:scale-105 transition duration-300";
                img.onerror = () => { imgWrap.innerHTML = `<i class="ph ph-img-brkn text-5xl text-gray-300"></i>`; };
                imgWrap.appendChild(img);
            } else {
                imgWrap.innerHTML = `<i class="ph ph-book text-5xl text-gray-400"></i>`;
            }
            const statusBadge = document.createElement("span");
            const isAvailable = book.availability === "available";
            statusBadge.className = `absolute top-2 left-2 ${isAvailable ? "bg-green-500" : "bg-orange-500"} text-white text-xs px-2 py-1 rounded-full shadow`;
            statusBadge.textContent = isAvailable ? "Available" : "Borrowed";
            const info = document.createElement("div");
            info.className = "p-2 group-hover:bg-gray-100 transition";
            const titleText = book.title || 'Untitled';
            const authorText = book.author || 'Unknown';
            const subjectText = book.subject || '';
            info.innerHTML = ` <h4 class="text-xs font-semibold mb-0.5 truncate w-full group-hover:text-[var(--color-primary)]" title="${titleText}">${titleText}</h4> <p class="text-[10px] text-gray-500 truncate w-full" title="${authorText}">by ${authorText}</p> <p class="text-[10px] font-medium text-[var(--color-primary)] mt-0.5 truncate w-full" title="${subjectText}"> ${subjectText} </p> `;
            card.appendChild(imgWrap);
            card.appendChild(statusBadge);
            card.appendChild(info);
            if (cart && cart.some(c => c.book_id == book.book_id)) {
                const badge = document.createElement("span");
                badge.className = "absolute bottom-2 left-2 bg-orange-500 text-white text-xs px-2 py-1 rounded-full shadow";
                badge.textContent = "In Cart";
                card.appendChild(badge);
            }
            grid.appendChild(card);
        });
    }

    function openModal(book) {
        if (!book || !modal) return;
        currentBook = book;
        if (addToCartBtn) {
            addToCartBtn.dataset.id = book.book_id || '';
            const availabilityText = (book.availability || "unknown").toUpperCase();
            addToCartBtn.disabled = availabilityText !== "AVAILABLE";
        }
        if (modalImg) {
            const coverUrl = book.cover || null;
            if (coverUrl) {
                modalImg.src = coverUrl;
                modalImg.classList.remove("hidden");
            } else {
                modalImg.classList.add("hidden");
            }
        }
        if (modalTitle) modalTitle.textContent = book.title || 'No Title';
        if (modalAuthor) modalAuthor.textContent = "by " + (book.author || "Unknown");
        if (modalCallNumber) modalCallNumber.textContent = book.call_number || "N/A";
        if (modalAccessionNumber) modalAccessionNumber.textContent = book.accession_number || "N/A";
        if (modalIsbn) modalIsbn.textContent = book.book_isbn || "N/A";
        if (modalSubject) modalSubject.textContent = book.subject || "N/A";
        if (modalPlace) modalPlace.textContent = book.book_place || "N/A";
        if (modalPublisher) modalPublisher.textContent = book.book_publisher || "N/A";
        if (modalYear) modalYear.textContent = book.year || "N/A";
        if (modalEdition) modalEdition.textContent = book.book_edition || "N/A";
        if (modalSupplementary) modalSupplementary.textContent = book.book_supplementary || "N/A";
        if (modalCampus) modalCampus.textContent = book.campus_name || "Main";
        if (modalDescription) modalDescription.textContent = book.description || "No description.";
        
        if (modalStatus) {
            const availabilityText = (book.availability || "unknown").toUpperCase();
            modalStatus.textContent = availabilityText;
            modalStatus.className = `font-semibold text-xs ${availabilityText === "AVAILABLE" ? "text-green-600" : "text-orange-600"}`;
        }

        modal.classList.remove("hidden");
        requestAnimationFrame(() => {
            modal.classList.add("opacity-100");
            if (modalContent) modalContent.classList.add("scale-100");
        });
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove("opacity-100");
        if (modalContent) modalContent.classList.remove("scale-100");
        setTimeout(() => { modal.classList.add("hidden"); }, 300);
    }

    if (grid) {
        grid.addEventListener("click", e => {
            const c = e.target.closest(".book-card");
            if (!c) return;
            try { openModal(JSON.parse(c.dataset.book)); } catch (p) {}
        });
    }
    if (closeModalBtn) closeModalBtn.addEventListener("click", closeModal);
    if (modal) modal.addEventListener("click", e => { if (e.target === modal) closeModal(); });

    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener("input", e => {
            searchValue = e.target.value.trim();
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                try { sessionStorage.removeItem(`bookCatalogPage_${userRole}`); } catch(e){}
                loadBooks(1);
            }, 500);
        });
    }

    if (addToCartBtn) {
        addToCartBtn.addEventListener("click", () => {
            const id = addToCartBtn.dataset.id;
            if (!id || !currentBook) return;

            const homeCampusId = document.getElementById("userHomeCampusId")?.value;
            const bookCampusId = currentBook.campus_id;
            const bookCampusName = currentBook.campus_name || "another campus";

            if (homeCampusId && bookCampusId && String(homeCampusId) !== String(bookCampusId)) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: "Inter-campus Borrowing",
                        text: `Are you sure? This book belongs to ${bookCampusName}.`,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#f97316", // orange-500
                        cancelButtonColor: "#6b7280", // gray-500
                        confirmButtonText: "Yes, add to cart",
                        cancelButtonText: "Cancel",
                        customClass: {
                            popup: "!rounded-xl !shadow-md !border-2 !border-orange-400 !p-6 !bg-gradient-to-b !from-[#fffdfb] !to-[#fff6ef] shadow-[0_0_8px_#ffb34770]",
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            addToCart(id);
                        }
                    });
                } else {
                    if (confirm(`Are you sure? This book belongs to ${bookCampusName}.`)) {
                        addToCart(id);
                    }
                }
            } else {
                addToCart(id);
            }
        });
    }

    loadAvailableCount();
    loadCart().then(() => {
        loadBooksInitial(currentPage); 
    });
});
