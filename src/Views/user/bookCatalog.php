<div class="min-h-screen">
    <div class="mb-6">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Book Catalog</h2>
        <p class="text-gray-700">Search and browse available books in our library.</p>
    </div>
    <div class="bg-[var(--color-card)] shadow-md border border-[var(--color-border)] rounded-xl p-6 mb-6">
        <h3 class="text-lg font-semibold mb-2 flex items-center gap-2 text-gray-800">
            <i class="ph ph-magnifying-glass text-[var(--color-primary)]"></i>
            Search & Discover
        </h3>
        <p class="text-gray-600 mb-4">Find exactly what you're looking for with our advanced search tools</p>

        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input id="searchInput" type="text" placeholder="Search"
                    class="w-full pl-10 pr-4 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-input-background)] focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition text-sm" />
            </div>

            <!-- Campus Filter -->
            <div class="relative">
                <input type="hidden" id="userRole" value="<?= htmlspecialchars($_SESSION['role'] ?? 'user') ?>">
                <input type="hidden" id="userHomeCampusId" value="<?= $currentCampusId ?>">
                <input type="hidden" id="userHomeCampusName" value="<?= htmlspecialchars($currentCampusName) ?>">
                <button id="campusDropdownBtn"
                    class="w-full sm:w-48 flex items-center justify-between px-4 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-input-background)] hover:bg-[var(--color-orange-50)] transition text-sm">
                    <span class="flex items-center gap-2 text-gray-700 pointer-events-none">
                        <i class="ph ph-buildings text-gray-500"></i>
                        <span id="campusDropdownValue"><?= htmlspecialchars($currentCampusName) ?></span>
                    </span>
                    <i class="ph ph-caret-down text-gray-500 pointer-events-none"></i>
                </button>
                <div id="campusDropdownMenu"
                    class="absolute mt-1 w-full sm:w-48 bg-[var(--color-card)] border border-[var(--color-border)] rounded-lg shadow-lg hidden z-[100] text-sm">
                    <ul class="py-1">
                        <li><button class="campus-item w-full text-left px-4 py-2 hover:bg-[var(--color-orange-100)]"
                                onclick="selectCampus(this,'all', 'All Campuses')">All Campuses</button></li>
                        <?php if (isset($campuses)): ?>
                            <?php foreach ($campuses as $campus): ?>
                                <li><button class="campus-item w-full text-left px-4 py-2 hover:bg-[var(--color-orange-100)]"
                                        onclick="selectCampus(this,'<?= $campus['campus_id'] ?>', '<?= htmlspecialchars($campus['campus_name']) ?>')"><?= htmlspecialchars($campus['campus_name']) ?></button></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <!-- Sort Filter -->
            <div class="relative">
                <button id="sortDropdownBtn"
                    class="w-full sm:w-48 flex items-center justify-between px-4 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-input-background)] hover:bg-[var(--color-orange-50)] transition text-sm">
                    <span class="flex items-center gap-2 text-gray-700 pointer-events-none">
                        <i class="ph ph-sort-ascending text-gray-500"></i>
                        <span id="sortDropdownValue">Default Order</span>
                    </span>
                    <i class="ph ph-caret-down text-gray-500 pointer-events-none"></i>
                </button>
                <div id="sortDropdownMenu"
                    class="absolute mt-1 w-full sm:w-48 bg-[var(--color-card)] border border-[var(--color-border)] rounded-lg shadow-lg hidden z-[100] text-sm">
                    <ul class="py-1">
                        <li><button class="sort-item w-full text-left px-4 py-2 hover:bg-[var(--color-orange-100)]"
                                onclick="selectSort(this,'default', 'Default Order')">Default Order</button></li>
                        <li><button class="sort-item w-full text-left px-4 py-2 hover:bg-[var(--color-orange-100)]"
                                onclick="selectSort(this,'title_asc', 'Title (A-Z)')">Title (A-Z)</button></li>
                        <li><button class="sort-item w-full text-left px-4 py-2 hover:bg-[var(--color-orange-100)]"
                                onclick="selectSort(this,'title_desc', 'Title (Z-A)')">Title (Z-A)</button></li>
                        <li><button class="sort-item w-full text-left px-4 py-2 hover:bg-[var(--color-orange-100)]"
                                onclick="selectSort(this,'year_asc', 'Year (Oldest)')">Year (Oldest)</button></li>
                        <li><button class="sort-item w-full text-left px-4 py-2 hover:bg-[var(--color-orange-100)]"
                                onclick="selectSort(this,'year_desc', 'Year (Newest)')">Year (Newest)</button></li>
                    </ul>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="relative">
                <button id="statusDropdownBtn"
                    class="w-full sm:w-40 flex items-center justify-between px-4 py-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-input-background)] hover:bg-[var(--color-orange-50)] transition text-sm">
                    <span class="flex items-center gap-2 text-gray-700 pointer-events-none">
                        <i class="ph ph-check-circle text-gray-500"></i>
                        <span id="statusDropdownValue">All Status</span>
                    </span>
                    <i class="ph ph-caret-down text-gray-500 pointer-events-none"></i>
                </button>
                <div id="statusDropdownMenu"
                    class="absolute mt-1 w-full sm:w-40 bg-[var(--color-card)] border border-[var(--color-border)] rounded-lg shadow-lg hidden z-[100] text-sm">
                    <ul class="py-1">
                        <li><button class="status-item w-full text-left px-4 py-2 hover:bg-[var(--color-orange-100)]"
                                onclick="selectStatus(this,'All Status')">All Status</button></li>
                        <li><button class="status-item w-full text-left px-4 py-2 hover:bg-[var(--color-orange-100)]"
                                onclick="selectStatus(this,'Available')">Available</button></li>
                        <li><button class="status-item w-full text-left px-4 py-2 hover:bg-[var(--color-orange-100)]"
                                onclick="selectStatus(this,'Borrowed')">Borrowed</button></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-2">
        <p id="resultsIndicator" class="font-medium text-gray-700 text-sm">
            Loading results...
        </p>
        <div class="flex gap-3 items-center">
            <span id="availableCount"
                class="bg-[var(--color-green-100)] text-[var(--color-green-700)] px-3 py-1 rounded-full text-sm font-medium flex items-center gap-1 whitespace-nowrap">
                <i class="ph ph-check-circle"></i>
                Available: Loading...
            </span>
            <span id="cart-count"
                class="bg-[var(--color-orange-100)] text-[var(--color-orange-700)] px-3 py-1 rounded-full text-sm font-medium flex items-center gap-1 whitespace-nowrap">
                <i class="ph ph-shopping-cart text-xs"></i> 0 total items
            </span>
        </div>
    </div>

    <div id="booksGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4"></div>

    <p id="noBooksFound" class="text-gray-500 text-center w-full hidden py-10">
        <i class="ph ph-books text-4xl block mb-2 text-gray-400"></i>
        No books found matching your criteria.
    </p>

    <div id="loadingSkeletons" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 w-full">
        <?php for ($i = 0; $i < 12; $i++): ?>
            <div class="animate-pulse bg-[var(--color-card)] shadow-sm rounded-xl overflow-hidden border border-[var(--color-border)]">
                <div class="w-full aspect-[2/3] bg-gray-200"></div>
                <div class="p-2 space-y-2">
                    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                    <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <nav id="paginationControls" aria-label="Page navigation" class="flex justify-center mt-8">
        <ul id="paginationList" class="flex items-center -space-x-px h-10 text-sm"> </ul>
    </nav>


    <div id="bookModal"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[1000] hidden opacity-0 transition-opacity duration-300 ease-out">
        <div id="bookModalContent"
            class="bg-[var(--color-card)] w-full max-w-lg rounded-2xl shadow-lg overflow-hidden transform scale-95 transition-transform duration-300 ease-out">
            <div
                class="bg-gradient-to-r from-orange-500 to-amber-500 p-4 text-white flex-shrink-0 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <img id="modalImg" src="" alt="Book Cover"
                        class="w-12 h-16 object-cover rounded-md bg-white hidden" />
                    <div>
                        <h2 id="modalTitle" class="text-lg font-bold text-white">Book Title</h2>
                        <p id="modalAuthor" class="text-sm">by Author</p>
                    </div>
                </div>
                <button id="closeModal" class="text-white text-3xl hover:text-red-500 transition-colors duration-200">
                    <i class="ph ph-x-circle"></i>
                </button>
            </div>

            <div class="p-4 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-3 shadow-md border border-orange-200 bg-orange-50 rounded flex flex-col items-start">
                        <p class="text-sm text-orange-500 font-semibold">STATUS</p>
                        <p id="modalStatus" class="font-semibold text-xs text-green-600">AVAILABLE</p>
                    </div>

                    <div class="p-3 shadow-md border border-orange-200 bg-orange-50 rounded flex flex-col items-start">
                        <p class="text-sm text-orange-500 font-semibold">CALL NUMBER</p>
                        <p id="modalCallNumber" class="text-xs font-semibold">N/A</p>
                    </div>
                </div>

                <div class="text-sm bg-white rounded-lg border border-gray-200 p-3 space-y-1">
                    <p class="font-semibold text-foreground text-sm">Book Information</p>
                    <p><span class="text-amber-700">Accession Number:</span> <span id="modalAccessionNumber"
                            class="font-mono text-sm font-semibold text-orange-600"></span></p>
                    <p><span class="text-amber-700">ISBN:</span> <span id="modalIsbn"></span></p>
                    <p><span class="text-amber-700">Subject:</span> <span id="modalSubject"></span></p>
                    <p><span class="text-amber-700">Book Place:</span> <span id="modalPlace"></span></p>
                    <p><span class="text-amber-700">Book Publisher:</span> <span id="modalPublisher"></span></p>
                    <p><span class="text-amber-700">Year:</span> <span id="modalYear"></span></p>
                    <p><span class="text-amber-700">Book Edition:</span> <span id="modalEdition"></span></p>
                    <p><span class="text-amber-700">Book Supplementary:</span> <span id="modalSupplementary"></span></p>
                    <p><span class="text-amber-700">Campus:</span> <span id="modalCampus" class="font-semibold text-orange-600"></span></p>
                </div>

                <div class="bg-gradient-to-r from-orange-50 to-amber-50 rounded-lg p-3 border border-orange-200">
                    <p class="font-semibold text-orange-900 mb-2 text-sm">Description</p>
                    <p class="text-amber-700 text-sm" id="modalDescription"></p>
                </div>
            </div>

            <div class="px-3 py-4 bg-gray-50">
                <button id="addToCartBtn" data-id=""
                    class="inline-flex items-center justify-center whitespace-nowrap text-sm disabled:pointer-events-none disabled:opacity-50 w-full gap-3 h-11 bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 text-white shadow-lg hover:shadow-xl transition-all duration-200 rounded-xl font-semibold">
                    <span><i class="ph ph-shopping-cart-simple"></i></span> Add to Cart
                </button>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/js/user/bookCatalog.js" defer></script>
    </div>