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

    <!-- Recent Returns Table (Management Style) -->
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
            <table class="w-full text-sm text-gray-700 table-fixed">
                <thead class="bg-orange-100 text-left text-gray-800">
                    <tr>
                        <th class="py-3 px-4 font-medium w-[25%]">Borrower</th>
                        <th class="py-3 px-4 font-medium w-[15%] text-center">Year/Section</th>
                        <th class="py-3 px-4 font-medium w-[15%] text-center">Accession</th>
                        <th class="py-3 px-4 font-medium w-[25%]">Item Title</th>
                        <th class="py-3 px-4 font-medium text-right w-[20%]">Timestamp</th>
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
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 border border-orange-200">
        <div class="flex justify-between items-center mb-4 border-b border-orange-100 pb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Item Scanned Successfully</h2>
                <p class="text-sm text-gray-500">Verify details before processing return.</p>
            </div>
            <button id="modal-close-button" class="text-gray-500 hover:text-red-600 transition">
                <i class="ph ph-x text-2xl"></i>
            </button>
        </div>

        <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-2">
            <div class="bg-orange-50/50 border border-orange-100 rounded-lg p-4">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="text-[10px] font-bold text-orange-500 uppercase tracking-widest mb-1">Item Title</p>
                        <h4 id="modal-book-title" class="font-bold text-gray-800 text-lg leading-tight"></h4>
                        <p id="modal-book-author" class="text-sm text-gray-600 mt-0.5 book-only-field italic"></p>
                    </div>
                    <span id="modal-book-status" class="bg-orange-200 text-orange-800 text-[10px] font-black px-2 py-1 rounded uppercase">BORROWED</span>
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-orange-100/50">
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Accession No.</p>
                        <p id="modal-book-accessionnumber" class="text-sm font-bold text-gray-700 font-mono"></p>
                    </div>
                    <div class="book-only-field">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Call Number</p>
                        <p id="modal-book-callnumber" class="text-sm font-bold text-gray-700 font-mono"></p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4">Borrower Information</h5>
                <div class="grid grid-cols-2 gap-y-4">
                    <div>
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Name</p>
                        <p id="modal-borrower-name" class="text-sm font-bold text-gray-800"></p>
                    </div>
                    <div>
                        <p id="modal-student-id-label" class="text-[10px] text-gray-400 font-bold uppercase">ID Number</p>
                        <p id="modal-student-id" class="text-sm font-bold text-gray-800 font-mono"></p>
                    </div>
                    <div class="col-span-2">
                        <p id="modal-borrower-course-label" class="text-[10px] text-gray-400 font-bold uppercase">Course / Department</p>
                        <p id="modal-borrower-course" class="text-sm font-medium text-gray-700"></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-red-400 font-bold uppercase">Due Date</p>
                        <p id="modal-due-date" class="text-sm font-black text-red-600"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-6 border-t border-gray-100">
            <button id="modal-cancel-button" class="px-6 py-2.5 border border-orange-200 text-gray-800 font-medium rounded-lg hover:bg-orange-50 transition">Cancel</button>
            <button id="modal-return-button" class="flex-1 bg-orange-600 text-white font-medium px-6 py-2.5 rounded-lg hover:bg-orange-700 transition shadow-lg shadow-orange-500/20 flex items-center justify-center gap-2">
                <i class="ph ph-check-circle text-lg"></i>
                Mark as Returned
            </button>
        </div>
    </div>
</div>

<!-- Available Book Modal -->
<div id="available-book-modal" class="fixed inset-0 backdrop-blur-sm bg-black/30 flex items-center justify-center z-50 hidden px-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 border-2 border-green-500">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Book Information</h3>
            <button id="available-modal-close-button" class="text-gray-400 hover:text-gray-600"><i class="ph ph-x text-2xl"></i></button>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <h4 id="available-modal-title" class="font-bold text-gray-800 text-lg"></h4>
            <p id="available-modal-author" class="text-sm text-gray-600 italic"></p>
            <span class="mt-2 inline-block bg-green-200 text-green-800 text-[10px] font-black px-2 py-0.5 rounded tracking-widest uppercase">AVAILABLE</span>
        </div>
        <div class="flex justify-end">
            <button id="available-modal-close-action" class="px-8 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">Close</button>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/js/admin/returning.js" defer></script>
