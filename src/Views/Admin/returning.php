<div class="min-h-screen">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Returning</h2>
        <p class="text-gray-500 mt-4">Scan item barcode or enter identifier to process returns</p>
    </div>

    <!-- Main Scanning Card -->
    <div class="bg-white rounded-lg p-6 shadow-sm max-w-7xl mx-auto border border-orange-200">
        <div class="flex items-center gap-3">
            <i class="ph ph-barcode text-2xl text-gray-700"></i>
            <h3 class="text-xl font-semibold text-gray-800">Manual Input / Scan</h3>
        </div>
        <p class="text-gray-600 mt-1 mb-6 ml-9">
            Enter or scan the item's identifier to view details and process return
        </p>

        <div class="flex justify-center mb-6">
            <div class="w-full max-w-4xl bg-white border border-orange-200 rounded-lg p-8 shadow-sm text-center">
                <div class="flex items-center justify-center gap-3 mb-6">
                    <i class="ph ph-qr-code text-3xl text-orange-600"></i>
                    <h4 class="text-xl font-semibold text-gray-800">QR Scanner (Books Only)</h4>
                </div>
                <p class="text-gray-600 mb-6">
                    Scan the book’s barcode or QR code to retrieve its details automatically.
                </p>
                <form id="scanner-form" class="inline-block">
                    <div id="qrBox"
                        class="w-40 h-40 sm:w-48 sm:h-48 mx-auto border-2 border-dashed border-orange-300 rounded-lg flex items-center justify-center bg-orange-50 cursor-pointer">
                        <i class="ph ph-qr-code text-orange-500 text-8xl sm:text-9xl"></i>
                    </div>
                    <input type="text" id="qrCodeValue" name="qrCodeValue" class="absolute opacity-0 pointer-events-none"
                        autocomplete="off">
                </form>
            </div>
        </div>

        <div class="flex justify-center mt-6">
            <div class="flex items-center w-full max-w-4xl gap-3">
                <input type="text" id="accession-input" placeholder="Enter Accession No., Equipment Name, or Asset Tag"
                    class="flex-grow p-3 border border-orange-200/80 rounded-md bg-orange-50/40 placeholder-orange-800/40 text-gray-800 outline-none focus:ring-1 focus:ring-orange-300">
                <button id="scan-button"
                    class="bg-orange-500 text-white px-5 py-3 rounded-md flex items-center gap-2 hover:bg-orange-600 border border-orange-500 transition shadow-sm">
                    <i class="ph ph-magnifying-glass"></i>
                    <span>Find Item</span>
                </button>
            </div>
        </div>
        <p class="text-sm text-gray-500 mt-3 text-center">
            <i class="ph ph-info"></i>
            Use a barcode scanner or manually enter the Accession Number, Equipment Name, or Asset Tag
        </p>
    </div>

    <!-- Recent Returns Table -->
    <div class="bg-white border border-orange-200 rounded-xl shadow-sm p-6 mt-8 max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Recent Returns History</h3>
                <p class="text-sm text-gray-600">Processed returns in the current session</p>
            </div>
            <a href="transactionHistory" class="text-sm font-bold text-orange-600 hover:underline flex items-center gap-1">
                View Full History <i class="ph ph-arrow-right"></i>
            </a>
        </div>

        <div class="overflow-hidden border border-orange-200 rounded-lg shadow-sm">
            <table class="w-full text-base text-gray-700 table-fixed">
                <thead class="bg-orange-100 text-left text-gray-800">
                    <tr>
                        <th class="py-4 px-5 font-bold w-[25%] text-sm uppercase tracking-wider">Borrower</th>
                        <th class="py-4 px-5 font-bold w-[15%] text-center text-sm uppercase tracking-wider">Year/Section</th>
                        <th class="py-4 px-5 font-bold w-[15%] text-center text-sm uppercase tracking-wider">Accession</th>
                        <th class="py-4 px-5 font-bold w-[25%] text-sm uppercase tracking-wider">Item Title</th>
                        <th class="py-4 px-5 font-bold text-right w-[20%] text-sm uppercase tracking-wider">Timestamp</th>
                    </tr>
                </thead>
                <tbody id="recent-returns-feed" class="divide-y divide-orange-100 bg-white">
                    <tr>
                        <td colspan="5" class="py-10 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="ph ph-clock-counter-clockwise text-4xl mb-2 text-orange-200"></i>
                                <p>No returns processed yet</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Return Modal -->
<div id="return-modal" class="fixed inset-0 backdrop-blur-sm bg-black/30 flex items-center justify-center z-50 hidden px-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-lg p-6 mx-4 border-2 border-orange-500">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-xl font-bold text-gray-800" id="return-modal-title">Item Scanned Successfully</h3>
                <p class="text-gray-500 text-sm" id="return-modal-subtitle">Item information retrieved from the system</p>
            </div>
            <button id="modal-close-button" class="text-gray-400 hover:text-gray-600 transition">
                <i class="ph ph-x text-2xl"></i>
            </button>
        </div>

        <div class="bg-stone-50 border border-stone-200 rounded-lg p-4 mb-4">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <p class="text-xs text-gray-500" id="modal-item-type-label">Item Title</p>
                    <h4 id="modal-book-title" class="font-bold text-gray-800 text-lg leading-tight"></h4>
                    <p id="modal-book-author" class="text-sm text-gray-600 mt-0.5 book-only-field italic"></p>
                </div>
                <span id="modal-book-status" class="bg-orange-200 text-orange-800 text-xs font-semibold px-3 py-1 rounded-full uppercase">BORROWED</span>
            </div>
            <div class="grid grid-cols-3 gap-4 mt-3">
                <div class="book-only-field">
                    <p class="text-[10px] text-gray-500 font-bold uppercase">ISBN</p>
                    <p id="modal-book-isbn" class="text-sm font-medium text-gray-700 font-mono"></p>
                </div>
                <div>
                    <p class="text-[10px] text-gray-500 font-bold uppercase" id="modal-item-identifier-label">Accession No.</p>
                    <p id="modal-book-accessionnumber" class="text-sm font-medium text-gray-700 font-mono"></p>
                </div>
                <div class="book-only-field">
                    <p class="text-[10px] text-gray-500 font-bold uppercase">Call Number</p>
                    <p id="modal-book-callnumber" class="text-sm font-medium text-gray-700 font-mono"></p>
                </div>
                <div id="modal-equipment-asset-tag-container" style="display: none;">
                    <p class="text-[10px] text-gray-500 font-bold uppercase">Asset Tag</p>
                    <p id="modal-equipment-asset-tag" class="text-sm font-medium text-gray-700 font-mono"></p>
                </div>
            </div>
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm mb-6">
            <div class="flex items-center gap-2 mb-3 text-orange-800 font-semibold">
                <i class="ph ph-user"></i>
                <h5>Borrower Information</h5>
            </div>
            <div class="grid grid-cols-2 gap-x-6 gap-y-3">
                <div><p class="text-[10px] text-gray-500 uppercase font-bold">Name</p><p id="modal-borrower-name" class="text-sm font-medium text-gray-700"></p></div>
                <div><p id="modal-student-id-label" class="text-[10px] text-gray-500 uppercase font-bold">ID Number</p><p id="modal-student-id" class="text-sm font-medium text-gray-700 font-mono"></p></div>
                <div class="col-span-2"><p id="modal-borrower-course-label" class="text-[10px] text-gray-500 uppercase font-bold">Course / Department</p><p id="modal-borrower-course" class="text-sm font-medium text-gray-700"></p></div>
                <div><p id="modal-year-section-label" class="text-[10px] text-gray-500 uppercase font-bold">Year & Section</p><p id="modal-borrower-year-section" class="text-sm font-medium text-gray-700"></p></div>
                <div><p class="text-[10px] text-red-500 uppercase font-bold">Due Date</p><p id="modal-due-date" class="text-sm font-bold text-red-600"></p></div>
                <p id="modal-borrower-email" class="hidden"></p>
                <p id="modal-borrower-contact" class="hidden"></p>
                <p id="modal-borrow-date" class="hidden"></p>
            </div>
        </div>

        <div class="flex justify-end items-center gap-3 mt-6 pt-4 border-t border-gray-100">
            <button id="modal-cancel-button" class="text-xs px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-100 transition">Cancel</button>
            <button id="modal-extend-button" class="text-xs px-6 py-2.5 border border-orange-200 rounded-lg text-orange-700 font-semibold hover:bg-orange-50 flex items-center gap-2"><i class="ph ph-calendar"></i> Extend Due Date</button>
            <button id="modal-return-button" class="text-xs px-6 py-2.5 bg-orange-500 text-white rounded-lg font-semibold hover:bg-orange-600 flex items-center gap-2 shadow-lg shadow-orange-500/20 transition active:scale-95"><i class="ph ph-check-circle"></i> Mark as Returned</button>
        </div>
    </div>
</div>

<!-- Available Modal (Boxy & Exhaustive Details) -->
<div id="available-book-modal" class="fixed inset-0 backdrop-blur-sm bg-black/30 flex items-center justify-center z-50 hidden px-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl h-[85vh] flex flex-col mx-4 border-2 border-green-500 overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b border-gray-100 flex justify-between items-start flex-shrink-0">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Item Scanned Successfully</h3>
                <p class="text-gray-500 text-sm">Item is currently available in the system</p>
            </div>
            <button id="available-modal-close-button" class="text-gray-400 hover:text-gray-600 transition">
                <i class="ph ph-x text-2xl"></i>
            </button>
        </div>

        <!-- Scrollable Body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <!-- Top Section -->
            <div class="flex flex-col md:flex-row gap-6">
                <div class="flex-shrink-0 mx-auto md:mx-0">
                    <img id="available-modal-img" src="" alt="Book Cover" class="w-32 h-48 object-cover rounded-lg border border-gray-200 shadow-sm bg-gray-50 hidden">
                    <div id="available-modal-img-placeholder" class="w-32 h-48 bg-gray-100 rounded-lg border border-gray-200 flex flex-col items-center justify-center text-gray-400">
                        <i class="ph ph-image-square text-4xl"></i>
                        <span class="text-[10px] font-bold mt-2 uppercase tracking-widest">No Cover</span>
                    </div>
                </div>
                <div class="flex-grow space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Item Title</p>
                            <h4 id="available-modal-title" class="font-bold text-gray-800 text-xl leading-tight"></h4>
                            <p id="available-modal-author" class="text-gray-600 mt-1 italic"></p>
                        </div>
                        <span id="available-modal-status" class="bg-green-200 text-green-800 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">AVAILABLE</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-50">
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Accession No.</p>
                            <p id="available-modal-accession" class="text-sm font-bold text-gray-700 font-mono"></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Call Number</p>
                            <p id="available-modal-call-number" class="text-sm font-bold text-gray-700 font-mono"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exhaustive Information Grid -->
            <div class="bg-stone-50 border border-stone-200 rounded-xl p-5 space-y-6">
                <h5 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Complete Item Specifications</h5>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-6 text-sm">
                    <div class="book-only-field">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">ISBN</p>
                        <p id="available-modal-isbn" class="font-medium text-gray-700"></p>
                    </div>
                    <div class="book-only-field">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Place of Publication</p>
                        <p id="available-modal-place" class="font-medium text-gray-700"></p>
                    </div>
                    <div class="book-only-field">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Publisher</p>
                        <p id="available-modal-publisher" class="font-medium text-gray-700"></p>
                    </div>
                    <div class="book-only-field">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Year Published</p>
                        <p id="available-modal-year" class="font-medium text-gray-700"></p>
                    </div>
                    <div class="book-only-field">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Edition</p>
                        <p id="available-modal-edition" class="font-medium text-gray-700"></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase" id="available-modal-type-label">Subject</p>
                        <p id="available-modal-subject" class="font-medium text-gray-700"></p>
                    </div>
                    <div class="col-span-2 book-only-field">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Supplementary Info</p>
                        <p id="available-modal-supplementary" class="font-medium text-gray-700"></p>
                    </div>
                    <div id="available-modal-asset-tag-container" style="display: none;">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Asset Tag</p>
                        <p id="available-modal-asset-tag" class="font-medium text-gray-700 font-mono"></p>
                    </div>
                </div>

                <div class="pt-4 border-t border-stone-200">
                    <p class="text-[10px] text-gray-400 font-bold uppercase mb-2">Description / Abstract</p>
                    <p id="available-modal-description" class="text-sm text-gray-600 leading-relaxed italic"></p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end flex-shrink-0">
            <button id="available-modal-close-action" class="px-8 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold text-xs hover:bg-gray-100 transition shadow-sm uppercase tracking-widest">Close Record</button>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/js/admin/returning.js" defer></script>
