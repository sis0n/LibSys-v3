<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold mb-4">Book Management</h2>
        <p class="text-gray-700">Manage library books, availability, and inventory.</p>
    </div>
    <div class="flex gap-2 text-sm">
        <button
            class="inline-flex items-center bg-white font-medium border border-orange-200 justify-center px-4 py-2 rounded-lg hover:bg-gray-100 px-4 gap-2 shadow-sm transition-all"
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
                        <p class="text-xs text-gray-500 mt-1">Expected format: accession_number,call_number,title</p>
                        <input type="file" id="csvFile" accept=".csv" class="hidden" />
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
            class="px-4 py-2 bg-orange-500 text-white font-medium rounded-lg border hover:bg-orange-600 gap-2 inline-flex items-center shadow-sm"
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

<div class="bg-[var(--color-card)] border border-orange-200 rounded-xl shadow-sm p-6 mt-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Book Catalog</h3>
            <p class="text-sm text-gray-600">Registered Books in the system</p>
        </div>
        <div class="flex items-center text-sm">
            <div class="relative w-[330px]">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-500"></i>
                <input type="text" id="bookSearchInput" placeholder="Search by title, author, isbn..."
                    class="bg-orange-50 border border-orange-200 rounded-lg pl-9 pr-3 py-2 outline-none transition text-sm w-full focus:ring-1 focus:ring-orange-300 shadow-sm">
            </div>
            <div class="relative inline-block text-left ml-3">
                <button id="sortDropdownBtn"
                    class="border border-orange-200 bg-white rounded-lg px-3 py-2 text-sm text-gray-700 flex items-center justify-between gap-2 w-44 hover:bg-orange-50 transition shadow-sm">
                    <span class="flex items-center gap-2 text-gray-700">
                        <i class="ph ph-sort-ascending text-gray-500"></i>
                        <span id="sortDropdownValue">Default Order</span>
                    </span>
                    <i class="ph ph-caret-down text-xs"></i>
                </button>
                <div id="sortDropdownMenu"
                    class="absolute mt-1 w-full bg-white border border-orange-200 rounded-lg shadow-md hidden z-20">
                    <div class="sort-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectSort(this, 'default')">Default Order</div>
                    <div class="sort-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectSort(this, 'title_asc')">Title (A-Z)</div>
                    <div class="sort-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectSort(this, 'title_desc')">Title (Z-A)</div>
                    <div class="sort-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectSort(this, 'year_desc')">Year (newest)</div>
                    <div class="sort-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectSort(this, 'year_asc')">Year (oldest)</div>
                </div>
            </div>
            <div class="relative inline-block text-left ml-3">
                <button id="statusDropdownBtn"
                    class="border border-orange-200 bg-white rounded-lg px-3 py-2 text-sm text-gray-700 flex items-center justify-between gap-2 w-36 hover:bg-orange-50 transition shadow-sm">
                    <span>
                        <i class="ph ph-check-circle text-gray-500"></i>
                        <span id="statusDropdownValue">All Status</span>
                    </span>
                    <i class="ph ph-caret-down text-xs"></i>
                </button>
                <div id="statusDropdownMenu"
                    class="absolute mt-1 w-full bg-white border border-orange-200 rounded-lg shadow-md hidden z-20">
                    <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectStatus(this, 'All Status')">All Status</div>
                    <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectStatus(this, 'Available')">Available</div>
                    <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectStatus(this, 'Borrowed')">Borrowed</div>
                    <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectStatus(this, 'Damaged')">Damaged</div>
                    <div class="status-item px-3 py-2 hover:bg-orange-100 cursor-pointer text-sm" onclick="selectStatus(this, 'Lost')">Lost</div>
                </div>
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

    <div class="overflow-hidden border border-orange-200 rounded-lg shadow-sm bg-white">
        <table class="min-w-full text-sm text-gray-700">
            <thead class="bg-orange-100 text-left text-gray-800 sticky top-0 z-0 border-b border-orange-200">
                <tr>
                    <th id="multi-select-header" class="py-3 px-4 font-medium hidden w-10"></th>
                    <th class="py-3 px-4 font-medium">Book Title</th>
                    <th class="py-3 px-4 font-medium">Author</th>
                    <th class="py-3 px-4 font-medium">Accession Number</th>
                    <th class="py-3 px-4 font-medium">Call Number</th>
                    <th class="py-3 px-4 font-medium">ISBN</th>
                    <th class="py-3 px-4 font-medium text-center">Status</th>
                    <th class="py-3 px-4 font-medium text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="bookTableBody" class="divide-y divide-orange-100 bg-white">
                <tr data-placeholder="true">
                    <td colspan="8" class="py-10 text-center text-gray-500">
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

