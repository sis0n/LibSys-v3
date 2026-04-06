<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold tracking-wider text-gray-900 uppercase">Book Management</h2>
    </div>
    <div class="flex gap-2 text-sm">
        <button
            class="inline-flex items-center bg-white font-medium border border-orange-200 justify-center px-4 py-2 rounded-full hover:bg-orange-50 gap-2 shadow-sm transition-all"
            id="bulkImportBtn">
            <i class="ph ph-upload-simple"></i>
            Bulk Import
        </button>
        <div id="importModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
            <div
                class="bg-[var(--color-card)] rounded-xl shadow-lg border border-[var(--color-border)] w-full max-w-md p-6 animate-fadeIn">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-lg font-semibold">Bulk Import Books</h2>
                    <button id="closeImportModal" class="text-gray-500 hover:text-red-700 transition">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Import multiple books from a CSV file.
                </p>
                <form id="bulkImportForm" enctype="multipart/form-data">
                    <label for="csvFile"
                        class="block border-2 border-dashed border-[var(--color-border)] rounded-lg p-8 text-center cursor-pointer hover:border-[var(--color-ring)]/60 transition">
                        <i class="ph ph-upload text-[var(--color-ring)] text-3xl mb-2 block"></i>
                        <p class="font-medium text-[var(--color-ring)]">Drop CSV file here or click to browse</p>
                        <p class="text-xs text-gray-500 mt-1">Expected format: accession_number,call_number,title,author,place,publisher,year,edition,desc,isbn,supp,subj,campus_id</p>
                        <input type="file" id="csvFile" name="csv_file" accept=".csv" class="hidden" />
                    </label>
                </form>
                <div class="text-center mt-4">
                    <button id="cancelImport"
                        class="mt-2 border border-[var(--color-border)] px-4 py-2 rounded-md text-gray-700 hover:bg-gray-100 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
        <button
            class="px-4 py-2 bg-orange-500 text-white font-medium rounded-full border hover:bg-orange-600 gap-2 inline-flex items-center shadow-sm"
            id="openAddBookBtn">
            <i class="ph ph-plus"></i>
            Add New Book
        </button>
        <div id="addBookModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
            <div
                class="bg-[var(--color-card)] rounded-xl shadow-lg border border-[var(--color-border)] w-full max-w-md h-[85vh] flex flex-col animate-fadeIn mx-4">
                <div class="flex justify-between items-start p-6 border-b border-[var(--color-border)] flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Add New Book</h2>
                        <p class="text-sm text-gray-500 mt-1">Add a new book to the library catalog.</p>
                    </div>
                    <button id="closeAddBookModal" class="text-gray-500 hover:text-red-700 transition">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>
                <form id="addBookForm" class="flex-1 overflow-y-auto px-6 py-4 space-y-3 custom-scrollbar">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Accession Number <span class="text-red-500">*</span> </label>
                        <input type="text" name="accession_number" required class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Call Number <span class="text-red-500">*</span> </label>
                        <input type="text" name="call_number" required class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Title <span class="text-red-500">*</span> </label>
                        <input type="text" name="title" required class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Author <span class="text-red-500">*</span> </label>
                        <input type="text" name="author" required class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Campus <span class="text-red-500">*</span> </label>
                        <select name="campus_id" required class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                            <option value="">Select Campus</option>
                            <!-- Options loaded by JS -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Borrow Duration Override (Days) </label>
                        <input type="number" name="borrowing_duration_override" min="0" placeholder="Default from policy" class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> ISBN </label>
                        <input type="text" name="book_isbn" class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Place of Publication </label>
                        <input type="text" name="book_place" class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Publisher </label>
                        <input type="text" name="book_publisher" class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Year Published </label>
                        <input type="number" name="year" min="0" class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Edition </label>
                        <input type="text" name="book_edition" class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Supplementary Info </label>
                        <input type="text" name="book_supplementary" class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Subject </label>
                        <input type="text" name="subject" class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1"> Description </label>
                        <textarea name="description" rows="3" class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm"></textarea>
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Book Image</label>
                        <label for="book_image" class="cursor-pointer flex items-center justify-center gap-2 w-full text-orange-700 border border-orange-200 rounded-md px-3 py-2 text-sm font-medium hover:bg-orange-100 transition shadow-sm">
                            <i class="ph ph-image-square text-lg"></i>
                            <span id="uploadText">Upload Image</span>
                        </label>
                        <input type="file" id="book_image" name="book_image" accept="image/*" class="hidden">
                        <div id="previewContainer" class="mt-2 hidden text-center">
                            <img id="previewImage" class="w-32 h-48 object-cover rounded-lg border border-orange-200 shadow-md inline-block" />
                        </div>
                    </div>
                </form>
                <div class="flex justify-end gap-3 p-6 border-t border-[var(--color-border)] flex-shrink-0">
                    <button type="submit" form="addBookForm" class="flex-1 bg-orange-600 text-white font-medium px-4 py-2.5 text-sm rounded-md hover:bg-orange-700 transition shadow-sm">
                        Add Book
                    </button>
                    <button type="button" id="cancelAddBook" class="border border-orange-200 text-gray-800 font-medium px-4 py-2.5 text-sm rounded-md hover:bg-orange-50 transition shadow-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-6">
    <div class="flex flex-wrap items-center gap-3 text-sm mb-4">
        <div class="relative w-[300px]">
            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-orange-500"></i>
            <input type="text" id="bookSearchInput" placeholder="Search by title, author, or ISBN..."
                class="bg-white border border-gray-200 rounded-full pl-10 pr-4 py-2.5 outline-none transition text-sm w-full shadow-sm focus:ring-2 focus:ring-orange-200">
        </div>
        
        <div class="relative inline-block text-left">
            <button id="campusDropdownBtn"
                class="border border-gray-200 bg-white rounded-full px-4 py-2.5 text-sm text-gray-700 flex items-center justify-between gap-2 w-44 hover:bg-orange-50 transition shadow-sm">
                <span class="flex items-center gap-2 text-gray-700">
                    <i class="ph ph-buildings text-gray-500"></i>
                    <span id="campusDropdownValue">All Campuses</span>
                </span>
                <i class="ph ph-caret-down text-xs"></i>
            </button>
            <div id="campusDropdownMenu"
                class="absolute mt-2 w-full bg-white border border-orange-200 rounded-xl shadow-md hidden z-20">
                <div class="campus-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectCampus(this, 0, 'All Campuses')">All Campuses</div>
                <!-- Campuses will be loaded here via JS -->
            </div>
        </div>

        <div class="relative inline-block text-left">
            <button id="sortDropdownBtn"
                class="border border-gray-200 bg-white rounded-full px-4 py-2.5 text-sm text-gray-700 flex items-center justify-between gap-2 w-44 hover:bg-orange-50 transition shadow-sm">
                <span class="flex items-center gap-2 text-gray-700">
                    <i class="ph ph-sort-ascending text-gray-500"></i>
                    <span id="sortDropdownValue">Default Order</span>
                </span>
                <i class="ph ph-caret-down text-xs"></i>
            </button>
            <div id="sortDropdownMenu"
                class="absolute mt-2 w-full bg-white border border-orange-200 rounded-xl shadow-md hidden z-20">
                <div class="sort-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectSort(this, 'default')">Default Order</div>
                <div class="sort-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectSort(this, 'title_asc')">Title (A-Z)</div>
                <div class="sort-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectSort(this, 'title_desc')">Title (Z-A)</div>
                <div class="sort-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectSort(this, 'year_desc')">Year (newest)</div>
                <div class="sort-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectSort(this, 'year_asc')">Year (oldest)</div>
            </div>
        </div>
        <div class="relative inline-block text-left">
            <button id="statusDropdownBtn"
                class="border border-gray-200 bg-white rounded-full px-4 py-2.5 text-sm text-gray-700 flex items-center justify-between gap-2 w-36 hover:bg-orange-50 transition shadow-sm">
                <span>
                    <i class="ph ph-check-circle text-gray-500"></i>
                    <span id="statusDropdownValue">All Status</span>
                </span>
                <i class="ph ph-caret-down text-xs"></i>
            </button>
            <div id="statusDropdownMenu"
                class="absolute mt-2 w-full bg-white border border-orange-200 rounded-xl shadow-md hidden z-20">
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectStatus(this, 'All Status')">All Status</div>
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectStatus(this, 'Available')">Available</div>
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectStatus(this, 'Borrowed')">Borrowed</div>
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectStatus(this, 'Damaged')">Damaged</div>
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectStatus(this, 'Lost')">Lost</div>
                <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectStatus(this, 'Inactive')">Inactive</div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between my-4">
        <h4 id="resultsIndicator" class="text-sm text-gray-600 font-medium">
            Loading...
        </h4>

        <div class="inline-flex items-center gap-2">
            <div id="multiSelectActions" class="hidden items-center gap-2">
                <button id="multiDeleteBtn" title="Delete selected books"
                    class="hidden items-center gap-2 bg-red-600 text-white rounded-lg px-3 py-2 text-sm font-medium hover:bg-red-700 transition shadow-sm">
                    <i class="ph ph-trash text-base"></i>
                    Delete (<span id="selectionCount">0</span>)
                </button>
                <div class="h-6 border-l border-gray-300 mx-2"></div>
                <button id="selectAllBtn" title="Select-all"
                    class="inline-flex items-center gap-2 border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-orange-50 transition shadow-sm">
                    <i class="ph ph-check-square-offset text-base"></i>
                    Select All
                </button>
                <button id="cancelSelectionBtn" title="Cancel multi-select"
                    class="inline-flex items-center gap-2 border border-gray-300 text-gray-700 rounded-lg px-3 py-2 text-sm font-medium hover:bg-gray-100 transition shadow-sm">
                    <i class="ph ph-x text-base"></i>
                    Cancel
                </button>
            </div>
            <button id="multiSelectBtn" title="Multi-select"
                class="inline-flex items-center gap-2 border border-orange-200 rounded-lg px-3 py-2 text-sm text-gray-700 hover:bg-orange-50 transition shadow-sm">
                <i class="ph ph-list-checks text-base"></i>
                Multiple Select
            </button>
        </div>
    </div>

    <div class="overflow-hidden border border-orange-200 rounded-2xl bg-white shadow-sm">
        <table class="min-w-full text-sm text-gray-700">
            <thead class="bg-orange-100 text-orange-700 uppercase text-xs tracking-wider sticky top-0 z-0 border-b border-orange-200">
                <tr>
                    <th id="multi-select-header" class="py-3 px-4 font-semibold hidden w-10"></th>
                    <th class="py-3 px-4 font-semibold">Book Title</th>
                    <th class="py-3 px-4 font-semibold text-center">Campus</th>
                    <th class="py-3 px-4 font-semibold">Author</th>
                    <th class="py-3 px-4 font-semibold text-center">Status</th>
                    <th class="py-3 px-4 font-semibold text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="bookTableBody" class="divide-y divide-orange-100 bg-white">
                <tr data-placeholder="true">
                    <td colspan="6" class="py-10 text-center text-gray-500">
                        <i class="ph ph-spinner animate-spin text-2xl"></i>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <nav id="paginationControls" aria-label="Page navigation"
        class="flex items-center justify-center bg-white border border-gray-200 rounded-full shadow-md px-4 py-2 mt-6 w-fit mx-auto gap-3 hidden">
        <ul id="paginationList" class="flex items-center h-9 text-sm gap-3">
        </ul>
    </nav>
</div>

<div id="editBookModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
    <div
        class="bg-[var(--color-card)] rounded-xl shadow-lg border border-[var(--color-border)] w-full max-md h-[85vh] flex flex-col animate-fadeIn mx-4">
        <div class="flex justify-between items-start p-6 border-b border-[var(--color-border)] flex-shrink-0">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Edit Book Details</h2>
                <p class="text-sm text-gray-500 mt-1">Modify book information below.</p>
            </div>
            <button id="closeEditBookModal" class="text-gray-500 hover:text-red-600 transition">
                <i class="ph ph-x text-2xl"></i>
            </button>
        </div>
        <form id="editBookForm" class="flex-1 overflow-y-auto px-6 py-4 space-y-3 custom-scrollbar">
            <input type="hidden" id="edit_book_id" name="book_id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Accession Number <span class="text-red-500">*</span></label>
                <input type="text" id="edit_accession_number" name="accession_number" required
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Call Number <span class="text-red-500">*</span></label>
                <input type="text" id="edit_call_number" name="call_number" required
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" id="edit_title" name="title" required
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Author <span class="text-red-500">*</span></label>
                <input type="text" id="edit_author" name="author" required
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select id="edit_availability" name="availability" required
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    <option value="available">Available</option>
                    <option value="damaged">Damaged</option>
                    <option value="lost">Lost</option>
                    <option value="borrowed" disabled>Borrowed (System Managed)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campus <span class="text-red-500">*</span></label>
                <select id="edit_campus_id" name="campus_id" required
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
                    <option value="">Select Campus</option>
                    <!-- Options loaded by JS -->
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Borrow Duration Override (Days)</label>
                <input type="number" id="edit_borrowing_duration_override" name="borrowing_duration_override" min="0" placeholder="Default from policy"
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ISBN</label>
                <input type="text" id="edit_book_isbn" name="book_isbn"
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Place of Publication</label>
                <input type="text" id="edit_book_place" name="book_place"
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Publisher</label>
                <input type="text" id="edit_book_publisher" name="book_publisher"
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year Published</label>
                <input type="number" id="edit_year" name="year" min="0"
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Edition</label>
                <input type="text" id="edit_book_edition" name="book_edition"
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Supplementary Info</label>
                <input type="text" id="edit_book_supplementary" name="book_supplementary"
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                <input type="text" id="edit_subject" name="subject"
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="edit_description" name="description" rows="3"
                    class="w-full bg-[var(--color-input)] border border-[var(--color-border)] rounded-md px-3 py-2 focus:ring-2 focus:ring-[var(--color-ring)] outline-none transition shadow-sm"></textarea>
            </div>
            <div class="flex flex-col">
                <label class="block text-sm font-medium text-gray-700 mb-1">Book Image</label>
                <input type="hidden" id="edit_remove_image" name="remove_image" value="0">
                <div class="flex gap-2">
                    <label for="edit_book_image"
                        class="cursor-pointer flex items-center justify-center gap-2 flex-grow text-orange-700 border border-orange-200 rounded-md px-3 py-2 text-sm font-medium hover:bg-orange-100 transition shadow-sm">
                        <i class="ph ph-image-square text-lg"></i>
                        <span id="editUploadText">Change Image</span>
                    </label>
                    <button type="button" id="removeImageBtn" class="hidden px-3 py-2 text-red-600 border border-red-200 rounded-md hover:bg-red-50 transition shadow-sm" title="Remove current image">
                        <i class="ph ph-trash text-lg"></i>
                    </button>
                </div>
                <input type="file" id="edit_book_image" name="book_image" accept="image/*" class="hidden">
                <div id="editPreviewContainer" class="mt-2 hidden text-center">
                    <img id="editPreviewImage"
                        class="w-32 h-48 object-cover rounded-lg border border-orange-200 shadow-md inline-block" />
                </div>
            </div>
        </form>
        <div class="flex justify-end gap-3 p-6 border-t border-[var(--color-border)] flex-shrink-0">
            <button type="submit" form="editBookForm"
                class="flex-1 bg-orange-600 text-white font-medium px-4 py-2.5 text-sm rounded-md hover:bg-orange-700 transition shadow-sm">
                Save Changes
            </button>
            <button type="button" id="cancelEditBook"
                class="border border-orange-200 text-gray-800 font-medium px-4 py-2.5 text-sm rounded-md hover:bg-orange-50 transition shadow-sm">
                Cancel
            </button>
        </div>
    </div>
</div>

<div id="viewBookModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300 ease-out p-4">
    <div id="viewBookModalContent" class="bg-[var(--color-card)] w-full max-w-lg rounded-2xl shadow-lg overflow-hidden transform scale-95 transition-transform duration-300 ease-out max-h-[90vh] flex flex-col">
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 p-4 text-white flex-shrink-0 flex justify-between items-center rounded-t-xl">
            <div class="flex items-center gap-3 overflow-hidden text-white">
                <img id="viewModalImg" src="" alt="Book Cover" class="w-12 h-16 object-cover rounded-md bg-white flex-shrink-0 hidden shadow-sm" />
                <div class="overflow-hidden">
                    <h2 id="viewModalTitle" class="text-lg font-bold text-white truncate">Book Title</h2>
                    <p id="viewModalAuthor" class="text-sm truncate text-orange-50">by Author</p>
                </div>
            </div>
            <button id="closeViewModal" class="text-white text-3xl hover:text-red-200 transition-colors duration-200 flex-shrink-0 ml-2">
                <i class="ph ph-x-circle"></i>
            </button>
        </div>
        <div class="p-4 space-y-4 overflow-y-auto bg-gray-50/30">
            <div class="grid grid-cols-2 gap-4">
                <div class="p-3 shadow-sm border border-orange-100 bg-white rounded flex flex-col items-start">
                    <p class="text-xs text-orange-500 font-semibold mb-1 uppercase tracking-wider">Status</p>
                    <p id="viewModalStatus" class="font-bold text-sm text-gray-800 uppercase">AVAILABLE</p>
                </div>
                <div class="p-3 shadow-sm border border-orange-100 bg-white rounded flex flex-col items-start">
                    <p class="text-xs text-orange-500 font-semibold mb-1 uppercase tracking-wider">Campus</p>
                    <p id="viewModalCampus" class="font-bold text-sm text-gray-800 uppercase">N/A</p>
                </div>
            </div>
            <div class="text-sm bg-white rounded-xl border border-orange-100 p-4 space-y-2 shadow-sm">
                <p class="font-bold text-gray-700 text-sm mb-2 border-b border-orange-50 pb-1">Book Information</p>
                <p><span class="text-gray-500 w-28 inline-block flex-shrink-0">Accession #:</span> <span id="viewModalAccessionNumber" class="font-mono text-sm font-semibold text-orange-600 break-words"></span></p>
                <p><span class="text-gray-500 w-28 inline-block flex-shrink-0">Call Number:</span> <span id="viewModalCallNumber" class="text-gray-800 font-semibold"></span></p>
                <p><span class="text-gray-500 w-28 inline-block flex-shrink-0">ISBN:</span> <span id="viewModalIsbn" class="break-words text-gray-800"></span></p>
                <p><span class="text-gray-500 w-28 inline-block flex-shrink-0">Subject:</span> <span id="viewModalSubject" class="break-words text-gray-800"></span></p>
                <p><span class="text-gray-500 w-28 inline-block flex-shrink-0">Place:</span> <span id="viewModalPlace" class="break-words text-gray-800"></span></p>
                <p><span class="text-gray-500 w-28 inline-block flex-shrink-0">Publisher:</span> <span id="viewModalPublisher" class="break-words text-gray-800"></span></p>
                <p><span class="text-gray-500 w-28 inline-block flex-shrink-0">Year:</span> <span id="viewModalYear" class="break-words text-gray-800"></span></p>
                <p><span class="text-gray-500 w-28 inline-block flex-shrink-0">Edition:</span> <span id="viewModalEdition" class="break-words text-gray-800"></span></p>
                <p><span class="text-gray-500 w-28 inline-block flex-shrink-0">Supplementary:</span> <span id="viewModalSupplementary" class="break-words text-gray-800"></span></p>
            </div>
            <div class="bg-orange-50/30 rounded-xl p-4 border border-orange-100 shadow-sm">
                <p class="font-bold text-orange-800 mb-1 text-sm">Description</p>
                <p class="text-gray-700 text-sm leading-relaxed" id="viewModalDescription"></p>
            </div>
        </div>
        <div class="flex justify-end gap-3 p-4 bg-white border-t border-orange-50 mt-auto flex-shrink-0 rounded-b-xl">
            <button type="button" id="closeViewModalBtn" class="bg-gray-100 border border-gray-200 text-gray-700 px-6 py-2 rounded-lg text-sm font-bold hover:bg-gray-200 transition shadow-sm">
                Close
            </button>
        </div>
    </div>
</div>

<div id="historyModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 hidden p-4">
    <div class="bg-[var(--color-card)] rounded-xl shadow-lg border border-[var(--color-border)] w-full max-w-2xl max-h-[85vh] flex flex-col animate-fadeIn overflow-hidden">
        <div class="bg-gradient-to-r from-orange-500 to-amber-500 p-5 text-white flex justify-between items-center flex-shrink-0">
            <h2 class="text-xl font-bold flex items-center gap-2 text-white">
                <i class="ph ph-clock-counter-clockwise text-2xl"></i>
                Borrowing History
            </h2>
            <button id="closeHistoryModal" class="text-white hover:text-red-200 transition">
                <i class="ph ph-x text-3xl"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6 custom-scrollbar bg-gray-50/30">
            <div id="historyTableContainer" class="overflow-hidden border border-orange-100 rounded-lg shadow-sm bg-white">
                <table class="min-w-full text-base text-gray-700">
                    <thead class="bg-orange-50 text-left text-gray-800 sticky top-0 z-10 border-b border-orange-100">
                        <tr>
                            <th class="py-4 px-5 font-bold">Borrower</th>
                            <th class="py-4 px-5 font-bold">ID / Role</th>
                            <th class="py-4 px-5 font-bold">Borrowed Date</th>
                            <th class="py-4 px-5 font-bold">Returned Date</th>
                            <th class="py-4 px-5 font-bold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody" class="divide-y divide-orange-100 bg-white">
                        <!-- Rows injected here -->
                    </tbody>
                </table>
            </div>
            <div id="historyEmptyState" class="hidden py-16 text-center text-gray-500">
                <i class="ph ph-scroll text-6xl mb-4 text-orange-200"></i>
                <p class="font-bold text-lg text-gray-700">No borrowing history found</p>
            </div>
        </div>
        <div class="p-5 border-t flex justify-end bg-white flex-shrink-0">
            <button id="closeHistoryBtn" class="px-8 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition font-bold text-sm shadow-sm">Close</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="<?= BASE_URL ?>/js/admin/bookManagement.js" defer></script>

